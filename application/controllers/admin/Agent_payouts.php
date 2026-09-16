<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Agent cash-outs. The mirror of admin/Withdrawals.php, against the agent's
 * own wallets instead of a user balance.
 *
 * The money was already held when the agent made the request, so approving
 * moves nothing: it only clears the payout for sending. Rejecting is the
 * only action here that touches a balance, and it returns the hold.
 */
class Agent_payouts extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('agent_payout_model', 'agent_model', 'notification_model'));
		$this->load->library('agent_wallet_lib');
	}

	public function index()
	{
		$this->require_perm('agent_payouts.view');

		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->agent_payout_model->paginate_admin($per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('admin/agent_payouts', array(
			'page_title'  => 'Agent Payouts',
			'active_menu' => 'agent_payouts',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->agent_payout_model->stats(),
		));
	}

	public function view($id)
	{
		$this->require_perm('agent_payouts.view');

		$payout = $this->agent_payout_model->find_detailed($id);

		if ( ! $payout)
		{
			show_404();
		}

		$this->render('admin/agent_payout_view', array(
			'page_title'  => 'Agent Payout #'.$payout->id,
			'active_menu' => 'agent_payouts',
			'payout'      => $payout,
		));
	}

	/** Cleared for sending. The wallet was debited when the request was made. */
	public function approve($id)
	{
		$this->require_perm('agent_payouts.approve');

		$row = $this->guard($id, array('pending'));

		$this->agent_payout_model->update($id, array(
			'status'       => 'approved',
			'admin_note'   => $this->input->post('admin_note', TRUE),
			'processed_by' => $this->admin->id,
			'processed_at' => date('Y-m-d H:i:s'),
		));

		$this->log_action('Approved agent payout', 'agent_payouts', $id);
		$this->notify($row, 'Payout approved',
			'Your '.money($row->net_amount).' payout has been approved and is queued for sending.');

		$this->session->set_flashdata('success', 'Payout approved. Send it, then mark it as paid.');
		redirect('admin/agent-payouts/view/'.$id);
	}

	public function mark_paid($id)
	{
		$this->require_perm('agent_payouts.mark_paid');

		$row  = $this->guard($id, array('pending', 'approved'));
		$txid = $this->input->post('txid', TRUE);

		if ( ! $txid)
		{
			$this->session->set_flashdata('error', 'Enter the payout transaction hash before marking it paid.');
			redirect('admin/agent-payouts/view/'.$id);
		}

		$this->agent_payout_model->update($id, array(
			'status'       => 'paid',
			'txid'         => $txid,
			'admin_note'   => $this->input->post('admin_note', TRUE),
			'processed_by' => $this->admin->id,
			'processed_at' => date('Y-m-d H:i:s'),
		));

		$this->log_action('Marked agent payout paid', 'agent_payouts', $id, $txid);
		$this->notify($row, 'Payout sent',
			money($row->net_amount).' has been sent to your wallet. TXID: '.$txid);

		$this->session->set_flashdata('success', 'Payout marked as paid.');
		redirect('admin/agent-payouts/view/'.$id);
	}

	/** Returns the full held amount to the wallet it came out of. */
	public function reject($id)
	{
		$this->require_perm('agent_payouts.reject');

		$row  = $this->guard($id, array('pending', 'approved'));
		$note = $this->input->post('admin_note', TRUE);

		$this->db->trans_start();

		// Re-read under lock: without it, two rejects on the same row would
		// each refund the hold.
		$fresh = $this->db->query('SELECT status FROM agent_payouts WHERE id = ? FOR UPDATE', array((int) $id))->row();

		if ( ! $fresh || ! in_array($fresh->status, array('pending', 'approved'), TRUE))
		{
			$this->db->trans_complete();
			$this->session->set_flashdata('error', 'This payout is already '.($fresh ? $fresh->status : 'gone').'.');
			redirect('admin/agent-payouts/view/'.$id);
		}

		$this->agent_payout_model->update($id, array(
			'status'       => 'rejected',
			'admin_note'   => $note,
			'processed_by' => $this->admin->id,
			'processed_at' => date('Y-m-d H:i:s'),
		));

		// The hold was the gross amount, so the gross amount is what returns -
		// the fee was never sent anywhere either.
		$this->agent_wallet_lib->credit(
			$row->agent_id, $row->source, $row->amount, 'payout_refund',
			'agent_payouts', $id, 'Refund for rejected payout #'.$id
		);

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE)
		{
			$this->session->set_flashdata('error', 'Could not reject the payout. Nothing was changed.');
			redirect('admin/agent-payouts/view/'.$id);
		}

		$this->log_action('Rejected agent payout', 'agent_payouts', $id, $note);
		$this->notify($row, 'Payout rejected',
			money($row->amount).' has been returned to your '.$row->source.' wallet.'.($note ? ' Reason: '.$note : ''));

		$this->session->set_flashdata('success', 'Payout rejected and '.money($row->amount).' returned.');
		redirect('admin/agent-payouts/view/'.$id);
	}

	/** Shared POST + state check for the three actions above. */
	protected function guard($id, $allowed_statuses)
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->agent_payout_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		if ( ! in_array($row->status, $allowed_statuses, TRUE))
		{
			$this->session->set_flashdata('error', 'This payout is already '.$row->status.'.');
			redirect('admin/agent-payouts/view/'.$id);
		}

		return $row;
	}

	/** Reaches the agent through their linked user account, when there is one. */
	protected function notify($row, $title, $body)
	{
		$agent = $this->agent_model->find($row->agent_id);

		if ($agent && $agent->user_id)
		{
			$this->notification_model->push($agent->user_id, $title, $body, 'agent/payouts');
		}
	}
}
