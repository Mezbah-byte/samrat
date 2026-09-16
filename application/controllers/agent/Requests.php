<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The float system's working screens: deposits to settle and withdrawals to
 * pay, both scoped to this agent by agent_id.
 *
 * Unlike agent/Deposits.php and agent/Withdrawals.php - which are review-only
 * and never load a money library - this controller does move money. It does it
 * in exactly one place though: every action delegates to Agent_settle_lib,
 * which owns the locking, the ordering and the idempotency.
 */
class Requests extends Agent_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->require_float();
		$this->load->model(array('deposit_model', 'withdrawal_model'));
		$this->load->library('agent_settle_lib');
	}

	/* ----------------------------------------------------------------
	 * Deposits
	 * ---------------------------------------------------------------- */

	public function deposits()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->agent_status_filter();
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->deposit_model->paginate_for_agent(
			$this->agent->id, $per_page, ($page - 1) * $per_page, $status, $search
		);

		$this->render('agent/request_deposits', array(
			'page_title'  => 'Deposit Requests',
			'active_menu' => 'req_deposits',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->deposit_model->agent_request_stats($this->agent->id),
			'balance'     => (float) $this->agent->deposit_balance,
		));
	}

	public function deposit($id)
	{
		$deposit = $this->deposit_model->find_for_agent($id, $this->agent->id);

		if ( ! $deposit)
		{
			show_404();
		}

		$this->render('agent/request_deposit_view', array(
			'page_title'  => 'Deposit Request #'.$deposit->id,
			'active_menu' => 'req_deposits',
			'deposit'     => $deposit,
			'balance'     => (float) $this->agent->deposit_balance,
			'percent'     => $this->agent->commission_settle_percent !== NULL
				? $this->agent->commission_settle_percent
				: setting('agent_deposit_commission_percent', '1'),
		));
	}

	/** Money moves here: float out, user credited, plan active. */
	public function accept($id)
	{
		$this->post_only();

		$result = $this->agent_settle_lib->settle_deposit($id, $this->agent->id);

		if ($result['ok'])
		{
			$this->log_action('Settled deposit', 'deposits', $id, $result['message']);
			$this->session->set_flashdata('success', $result['message']);
		}
		else
		{
			$this->session->set_flashdata('error', $result['message']);
		}

		redirect('agent/requests/deposit/'.$id);
	}

	public function decline($id)
	{
		$this->post_only();

		$note = trim((string) $this->input->post('agent_note', TRUE));

		if ($note === '')
		{
			$this->session->set_flashdata('error', 'Add a note explaining what went wrong.');
			redirect('agent/requests/deposit/'.$id);
		}

		$result = $this->agent_settle_lib->decline_deposit($id, $this->agent->id, $note);

		if ($result['ok'])
		{
			$this->log_action('Declined deposit', 'deposits', $id, $note);
			$this->session->set_flashdata('success', $result['message']);
		}
		else
		{
			$this->session->set_flashdata('error', $result['message']);
		}

		redirect('agent/requests/deposit/'.$id);
	}

	/* ----------------------------------------------------------------
	 * Withdrawals
	 * ---------------------------------------------------------------- */

	public function withdrawals()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->agent_status_filter();
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->withdrawal_model->paginate_for_agent(
			$this->agent->id, $per_page, ($page - 1) * $per_page, $status, $search
		);

		$this->render('agent/request_withdrawals', array(
			'page_title'  => 'Withdraw Requests',
			'active_menu' => 'req_withdrawals',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->withdrawal_model->agent_request_stats($this->agent->id),
		));
	}

	public function withdrawal($id)
	{
		$row = $this->withdrawal_model->find_for_agent($id, $this->agent->id);

		if ( ! $row)
		{
			show_404();
		}

		$this->render('agent/request_withdrawal_view', array(
			'page_title'  => 'Withdraw Request #'.$row->id,
			'active_menu' => 'req_withdrawals',
			'withdrawal'  => $row,
			'percent'     => $this->agent->commission_withdraw_percent !== NULL
				? $this->agent->commission_withdraw_percent
				: setting('agent_withdraw_commission_percent', '1'),
		));
	}

	/** The agent confirms they have already sent the user their money. */
	public function pay($id)
	{
		$this->post_only();

		$txid = trim((string) $this->input->post('txid', TRUE));

		if ($txid === '')
		{
			$this->session->set_flashdata('error', 'Enter the hash of the transfer you sent before confirming.');
			redirect('agent/requests/withdrawal/'.$id);
		}

		$result = $this->agent_settle_lib->pay_withdrawal($id, $this->agent->id, $txid);

		if ($result['ok'])
		{
			$this->log_action('Paid withdrawal', 'withdrawals', $id, $txid);
			$this->session->set_flashdata('success', $result['message']);
		}
		else
		{
			$this->session->set_flashdata('error', $result['message']);
		}

		redirect('agent/requests/withdrawal/'.$id);
	}

	public function decline_withdrawal($id)
	{
		$this->post_only();

		$note = trim((string) $this->input->post('agent_note', TRUE));

		if ($note === '')
		{
			$this->session->set_flashdata('error', 'Add a note explaining why you cannot pay this.');
			redirect('agent/requests/withdrawal/'.$id);
		}

		$result = $this->agent_settle_lib->decline_withdrawal($id, $this->agent->id, $note);

		if ($result['ok'])
		{
			$this->log_action('Declined withdrawal', 'withdrawals', $id, $note);
			$this->session->set_flashdata('success', $result['message']);
		}
		else
		{
			$this->session->set_flashdata('error', $result['message']);
		}

		redirect('agent/requests/withdrawal/'.$id);
	}

	/* ---------------------------------------------------------------- */

	protected function agent_status_filter()
	{
		$status = $this->input->get('status', TRUE) ?: '';

		return in_array($status, array('pending', 'accepted', 'rejected', 'expired'), TRUE) ? $status : '';
	}

	protected function post_only()
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}
	}
}
