<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Withdraw extends User_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('withdrawal_model', 'investment_model'));
	}

	public function index()
	{
		$fee_percent = (float) $this->setting_model->get('withdrawal_fee_percent', 5);
		$floor       = $this->investment_model->withdraw_floor($this->user->id);
		$route       = $this->route();
		$agents      = in_array($route, array('agent', 'both'), TRUE) ? $this->available_agents() : array();

		if ($this->input->method() === 'post')
		{
			$this->handle_request($fee_percent, $floor, $route, $agents);
		}

		$this->render('user/withdraw', array(
			'page_title'   => 'Withdraw',
			'active_menu'  => 'withdraw',
			'fee_percent'  => $fee_percent,
			'floor'        => $floor,
			'enabled'      => $this->setting_model->get('withdrawal_enabled', '1') === '1',
			'pending'      => $this->withdrawal_model->pending_count_for_user($this->user->id),
			'recent'       => $this->withdrawal_model->for_user($this->user->id, 5)['rows'],
			'route'        => $route,
			'agents'       => $agents,
		));
	}

	/**
	 * Places the request and holds the funds.
	 *
	 * Two routes, chosen by the `withdraw_route` setting: an admin pays from
	 * the platform, or a chosen agent pays out of their own pocket and
	 * collects the amount into their withdraw wallet afterwards. The hold on
	 * the user's balance is identical either way - it happens here, at request
	 * time, so the same balance cannot be requested twice.
	 */
	protected function handle_request($fee_percent, $floor, $route, $agents)
	{
		if ($this->setting_model->get('withdrawal_enabled', '1') !== '1')
		{
			$this->session->set_flashdata('error', 'Withdrawals are temporarily disabled.');
			redirect('withdraw');
		}

		$this->form_validation->set_rules('amount', 'Amount', 'required|numeric|greater_than[0]');
		$this->form_validation->set_rules('binance_id', 'Binance ID', 'required|trim|numeric|min_length[6]|max_length[32]');

		if ( ! $this->form_validation->run())
		{
			return;
		}

		$amount = round((float) $this->input->post('amount'), MONEY_SCALE);
		$wallet = $this->input->post('binance_id', TRUE);

		// Which route this request takes. As on the deposit side, the setting
		// decides what is on offer - not the posted field.
		$via   = $this->input->post('pay_via', TRUE);
		$agent = NULL;

		if ($route === 'agent' || ($route === 'both' && $via === 'agent'))
		{
			if (empty($agents))
			{
				$this->session->set_flashdata('error', 'No agent is accepting withdrawals right now. Please try again shortly.');
				redirect('withdraw');
			}

			$agent_id = (int) $this->input->post('agent_id');
			$agent    = isset($agents[$agent_id]) ? $agents[$agent_id] : NULL;

			if ( ! $agent)
			{
				$this->session->set_flashdata('error', 'Pick an agent to pay you.');
				redirect('withdraw');
			}
		}

		if ($floor <= 0)
		{
			$this->session->set_flashdata('error', 'You need an active package before you can withdraw.');
			redirect('withdraw');
		}

		if ($amount < $floor)
		{
			$this->session->set_flashdata('error', 'Minimum withdrawal for your package is '.money($floor).'.');
			redirect('withdraw');
		}

		// Re-read the balance rather than trusting the copy loaded at boot.
		$balance = $this->wallet_lib->balance($this->user->id);

		if ($amount > $balance)
		{
			$this->session->set_flashdata('error', 'Amount exceeds your available balance of '.money($balance).'.');
			redirect('withdraw');
		}

		$fee = round($amount * $fee_percent / 100, MONEY_SCALE);
		$net = round($amount - $fee, MONEY_SCALE);

		$this->db->trans_start();

		$withdrawal_id = $this->withdrawal_model->insert(array(
			'user_id'        => $this->user->id,
			'amount'         => money_raw($amount),
			'fee_percent'    => $fee_percent,
			'fee'            => money_raw($fee),
			'net_amount'     => money_raw($net),
			'network'        => WITHDRAW_NETWORK,
			'wallet_address' => $wallet,
			'status'         => 'pending',
			'agent_id'       => $agent ? $agent->id : NULL,
			'agent_status'   => $agent ? 'pending' : 'none',
		));

		// Funds are held the moment the request is made, so the same balance
		// cannot be requested twice while it is reviewed.
		$this->wallet_lib->debit(
			$this->user->id, $net, 'withdrawal',
			'withdrawals', $withdrawal_id, 'Withdrawal request #'.$withdrawal_id
		);

		if ($fee > 0)
		{
			$this->wallet_lib->debit(
				$this->user->id, $fee, 'withdrawal_fee',
				'withdrawals', $withdrawal_id, $fee_percent.'% withdrawal fee on request #'.$withdrawal_id
			);
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE)
		{
			$this->session->set_flashdata('error', 'Could not place the request. Your balance was not changed.');
			redirect('withdraw');
		}

		if ($agent)
		{
			$this->notify_agent($agent, 'New withdrawal request',
				money($net).' is waiting for you to send.', 'agent/requests/withdrawal/'.$withdrawal_id);

			$this->session->set_flashdata('success',
				'Withdrawal requested from '.$agent->username.'. '.money($net).' will be sent once they pay it'
				.' ('.money($fee).' fee deducted).');
		}
		else
		{
			$this->session->set_flashdata('success',
				'Withdrawal requested. '.money($net).' will be sent after admin approval ('.money($fee).' fee deducted).');
		}

		redirect('withdraw/history');
	}

	/* ---------------------------------------------------------------- */

	/** Which routes are on offer right now. */
	protected function route()
	{
		if ($this->setting_model->get('agent_float_enabled', '0') !== '1')
		{
			return 'admin';
		}

		$route = $this->setting_model->get('withdraw_route', 'admin');

		return in_array($route, array('admin', 'agent', 'both'), TRUE) ? $route : 'admin';
	}

	/**
	 * Agents taking withdrawal work, keyed by id.
	 *
	 * No float requirement: an agent pays a withdrawal from their own pocket
	 * and collects it afterwards, so holding float has nothing to do with it.
	 */
	protected function available_agents()
	{
		$this->load->model('agent_model');

		$out = array();

		foreach ($this->agent_model->available_for_withdraw() as $agent)
		{
			$out[(int) $agent->id] = $agent;
		}

		return $out;
	}

	/** Agents read notifications through their linked user account, if any. */
	protected function notify_agent($agent, $title, $body, $link)
	{
		$full = $this->agent_model->find($agent->id);

		if ($full && $full->user_id)
		{
			$this->notification_model->push($full->user_id, $title, $body, $link);
		}
	}

	public function history()
	{
		$per_page = 15;
		$page     = max(1, (int) $this->input->get('page'));
		$result   = $this->withdrawal_model->for_user($this->user->id, $per_page, ($page - 1) * $per_page);

		$this->render('user/withdraw_history', array(
			'page_title'  => 'Withdrawal History',
			'active_menu' => 'withdraw',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
		));
	}
}
