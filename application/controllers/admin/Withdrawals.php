<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Withdrawals extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('withdrawal_model', 'notification_model'));
		$this->load->library('wallet_lib');
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->withdrawal_model->paginate_admin($per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('admin/withdrawals', array(
			'page_title'  => 'Withdrawals',
			'active_menu' => 'withdrawals',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->withdrawal_model->stats(),
		));
	}

	public function view($id)
	{
		$withdrawal = $this->withdrawal_model->find_detailed($id);

		if ( ! $withdrawal)
		{
			show_404();
		}

		$this->render('admin/withdrawal_view', array(
			'page_title'  => 'Withdrawal #'.$withdrawal->id,
			'active_menu' => 'withdrawals',
			'withdrawal'  => $withdrawal,
		));
	}

	/** Approve = cleared for payout. The money was already held on request. */
	public function approve($id)
	{
		$row = $this->guard($id, array('pending'));

		$this->withdrawal_model->update($id, array(
			'status'       => 'approved',
			'admin_note'   => $this->input->post('admin_note', TRUE),
			'processed_by' => $this->admin->id,
			'processed_at' => date('Y-m-d H:i:s'),
		));

		$this->log_action('Approved withdrawal', 'withdrawals', $id);
		$this->notification_model->push($row->user_id, 'Withdrawal approved',
			'Your withdrawal of '.money($row->net_amount).' has been approved and is queued for payout.', 'withdraw/history');

		$this->session->set_flashdata('success', 'Withdrawal approved. Send the payout, then mark it as paid.');
		redirect('admin/withdrawals/view/'.$id);
	}

	/** Records the on-chain TXID and closes the request. */
	public function mark_paid($id)
	{
		$row = $this->guard($id, array('pending', 'approved'));

		$txid = $this->input->post('txid', TRUE);

		if ( ! $txid)
		{
			$this->session->set_flashdata('error', 'Enter the payout transaction hash before marking it paid.');
			redirect('admin/withdrawals/view/'.$id);
		}

		$this->withdrawal_model->update($id, array(
			'status'       => 'paid',
			'txid'         => $txid,
			'admin_note'   => $this->input->post('admin_note', TRUE),
			'processed_by' => $this->admin->id,
			'processed_at' => date('Y-m-d H:i:s'),
		));

		$this->log_action('Marked withdrawal paid', 'withdrawals', $id, $txid);
		$this->notification_model->push($row->user_id, 'Withdrawal paid',
			money($row->net_amount).' has been sent to your wallet. TXID: '.$txid, 'withdraw/history');

		$this->session->set_flashdata('success', 'Withdrawal marked as paid.');
		redirect('admin/withdrawals/view/'.$id);
	}

	/**
	 * Rejecting returns the full amount — the net payout and the fee both go
	 * back, since neither was ever actually sent.
	 */
	public function reject($id)
	{
		$row  = $this->guard($id, array('pending', 'approved'));
		$note = $this->input->post('admin_note', TRUE);

		$this->db->trans_start();

		$this->withdrawal_model->update($id, array(
			'status'       => 'rejected',
			'admin_note'   => $note,
			'processed_by' => $this->admin->id,
			'processed_at' => date('Y-m-d H:i:s'),
		));

		// Split to stay symmetric with the request, which debited the net as
		// 'withdrawal' (counted in total_withdrawn) and the fee separately.
		// Refunding the gross as one 'refund' would over-subtract that total
		// by the fee. Together these still return the full amount.
		$this->wallet_lib->credit(
			$row->user_id, $row->net_amount, 'refund',
			'withdrawals', $id, 'Refund for rejected withdrawal #'.$id
		);

		if ((float) $row->fee > 0)
		{
			$this->wallet_lib->credit(
				$row->user_id, $row->fee, 'admin_credit',
				'withdrawals', $id, 'Fee returned for rejected withdrawal #'.$id
			);
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE)
		{
			$this->session->set_flashdata('error', 'Could not reject the request. Nothing was changed.');
			redirect('admin/withdrawals/view/'.$id);
		}

		$this->log_action('Rejected withdrawal', 'withdrawals', $id, $note);
		$this->notification_model->push($row->user_id, 'Withdrawal rejected',
			money($row->amount).' has been returned to your balance.'.($note ? ' Reason: '.$note : ''), 'withdraw/history');

		$this->session->set_flashdata('success', 'Withdrawal rejected and '.money($row->amount).' refunded.');
		redirect('admin/withdrawals/view/'.$id);
	}

	/** Shared POST + state check for the three actions above. */
	protected function guard($id, $allowed_statuses)
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->withdrawal_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		if ( ! in_array($row->status, $allowed_statuses, TRUE))
		{
			$this->session->set_flashdata('error', 'This request is already '.$row->status.'.');
			redirect('admin/withdrawals/view/'.$id);
		}

		return $row;
	}
}
