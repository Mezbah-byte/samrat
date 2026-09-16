<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cashing out: the agent draining their withdraw or commission wallet back
 * out of the platform, plus the one internal move they are allowed to make -
 * commission into deposit float.
 *
 * A request holds the money immediately, the same way a user withdrawal does,
 * so the same balance cannot be requested twice while an admin reviews it.
 */
class Payouts extends Agent_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->require_float();
		$this->load->model('agent_payout_model');
		$this->load->library('agent_wallet_lib');
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';

		if ( ! in_array($status, array('', 'pending', 'approved', 'paid', 'rejected'), TRUE))
		{
			$status = '';
		}

		$result = $this->agent_payout_model->for_agent($this->agent->id, $per_page, ($page - 1) * $per_page, $status);

		$this->render('agent/payouts', array(
			'page_title'  => 'Cash Out',
			'active_menu' => 'payouts',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'balances'    => $this->agent_wallet_lib->balances($this->agent->id),
			'fee_percent' => (float) setting('agent_payout_fee_percent', 0),
		));
	}

	/** Places a cash-out request and holds the amount straight away. */
	public function create()
	{
		if ($this->input->method() !== 'post')
		{
			redirect('agent/payouts');
		}

		$this->form_validation->set_rules('source', 'Wallet', 'required|in_list[withdraw,commission]');
		$this->form_validation->set_rules('amount', 'Amount', 'required|numeric|greater_than[0]');
		$this->form_validation->set_rules('network', 'Network', 'required|trim|max_length[30]');
		$this->form_validation->set_rules('wallet_address', 'Wallet Address', 'required|trim|max_length[191]');

		if ( ! $this->form_validation->run())
		{
			$this->session->set_flashdata('error', validation_errors(' ', ' '));
			redirect('agent/payouts');
		}

		$source  = $this->input->post('source', TRUE);
		$amount  = round((float) $this->input->post('amount'), MONEY_SCALE);
		$address = $this->input->post('wallet_address', TRUE);
		$network = $this->input->post('network', TRUE);

		// Read the balance now rather than trusting the copy loaded at boot.
		$balance = $this->agent_wallet_lib->balance($this->agent->id, $source);

		if ($amount > $balance)
		{
			$this->session->set_flashdata('error', 'That is more than your '.$source.' wallet holds ('.money($balance).').');
			redirect('agent/payouts');
		}

		$fee_percent = (float) setting('agent_payout_fee_percent', 0);
		$fee         = round($amount * $fee_percent / 100, MONEY_SCALE);
		$net         = round($amount - $fee, MONEY_SCALE);

		if ($net <= 0)
		{
			$this->session->set_flashdata('error', 'The fee would consume the whole amount. Request more.');
			redirect('agent/payouts');
		}

		$this->db->trans_start();

		$payout_id = $this->agent_payout_model->insert(array(
			'agent_id'       => $this->agent->id,
			'source'         => $source,
			'amount'         => money_raw($amount),
			'fee_percent'    => $fee_percent,
			'fee'            => money_raw($fee),
			'net_amount'     => money_raw($net),
			'network'        => $network,
			'wallet_address' => $address,
			'status'         => 'pending',
		));

		// The hold is the gross amount: the fee is the platform's, but it
		// leaves the agent's wallet all the same.
		$held = $this->agent_wallet_lib->debit(
			$this->agent->id, $source, $amount, 'payout',
			'agent_payouts', $payout_id, 'Cash-out request #'.$payout_id
		);

		if ($held === FALSE)
		{
			$this->db->trans_rollback();
			$this->session->set_flashdata('error', 'Could not hold the amount. Nothing was changed.');
			redirect('agent/payouts');
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE)
		{
			$this->session->set_flashdata('error', 'Could not place the request. Your balance was not changed.');
			redirect('agent/payouts');
		}

		$this->log_action('Requested cash-out', 'agent_payouts', $payout_id, $source.' '.money($amount));
		$this->session->set_flashdata('success',
			'Cash-out requested. '.money($net).' will be sent after admin approval'
			.($fee > 0 ? ' ('.money($fee).' fee deducted).' : '.'));
		redirect('agent/payouts');
	}

	/**
	 * Commission into deposit float. The only wallet-to-wallet move an agent
	 * may make; Agent_wallet_lib refuses every other direction.
	 */
	public function transfer()
	{
		if ($this->input->method() !== 'post')
		{
			redirect('agent/payouts');
		}

		$amount = round((float) $this->input->post('amount'), MONEY_SCALE);

		if ($amount <= 0)
		{
			$this->session->set_flashdata('error', 'Enter an amount greater than zero.');
			redirect('agent/payouts');
		}

		$ok = $this->agent_wallet_lib->transfer($this->agent->id, 'commission', 'deposit', $amount);

		if ( ! $ok)
		{
			$this->session->set_flashdata('error', 'Transfer failed - check your commission balance.');
			redirect('agent/payouts');
		}

		$this->log_action('Moved commission into float', 'agents', $this->agent->id, money($amount));
		$this->session->set_flashdata('success', money($amount).' moved into your deposit float.');
		redirect('agent/payouts');
	}

	public function view($id)
	{
		$payout = $this->agent_payout_model->find_for_agent($id, $this->agent->id);

		if ( ! $payout)
		{
			show_404();
		}

		$this->render('agent/payout_view', array(
			'page_title'  => 'Cash-Out #'.$payout->id,
			'active_menu' => 'payouts',
			'payout'      => $payout,
		));
	}
}
