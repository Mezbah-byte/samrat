<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Float orders: an agent buying deposit balance from the platform.
 *
 * Approving one mints float out of an off-platform USDT transfer, so it is
 * the same act as approving a user deposit and carries the same weight. The
 * balance moves in exactly one place - Agent_wallet_lib - and the ledger row
 * it writes is what makes a double-submitted approval a no-op.
 */
class Agent_float extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('agent_float_order_model', 'agent_model', 'agent_ledger_model', 'notification_model'));
		$this->load->library('agent_wallet_lib');
	}

	public function index()
	{
		$this->require_perm('agent_float.view');

		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->agent_float_order_model->paginate_admin($per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('admin/agent_float', array(
			'page_title'  => 'Agent Float Orders',
			'active_menu' => 'agent_float',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->agent_float_order_model->stats(),
		));
	}

	public function view($id)
	{
		$this->require_perm('agent_float.view');

		$order = $this->agent_float_order_model->find_detailed($id);

		if ( ! $order)
		{
			show_404();
		}

		$this->render('admin/agent_float_view', array(
			'page_title'  => 'Float Order #'.$order->id,
			'active_menu' => 'agent_float',
			'order'       => $order,
		));
	}

	/** Credits the agent's deposit wallet at par. */
	public function approve($id)
	{
		$this->require_perm('agent_float.approve');

		$order = $this->guard($id);
		$note  = $this->input->post('admin_note', TRUE);

		$this->db->trans_start();

		// Re-read inside the transaction: two admins on the same order would
		// otherwise both pass the guard above and both credit the float.
		$fresh = $this->db->query('SELECT status FROM agent_float_orders WHERE id = ? FOR UPDATE', array((int) $id))->row();

		if ( ! $fresh || $fresh->status !== 'pending')
		{
			$this->db->trans_complete();
			$this->session->set_flashdata('error', 'This order is already '.($fresh ? $fresh->status : 'gone').'.');
			redirect('admin/agent-float/view/'.$id);
		}

		$this->agent_float_order_model->update($id, array(
			'status'      => 'approved',
			'admin_note'  => $note,
			'reviewed_by' => $this->admin->id,
			'reviewed_at' => date('Y-m-d H:i:s'),
		));

		$credited = $this->agent_wallet_lib->credit(
			$order->agent_id, 'deposit', $order->amount, 'float_purchase',
			'agent_float_orders', $order->id, 'Float purchase #'.$order->id
		);

		$this->db->trans_complete();

		if ($credited === FALSE || $this->db->trans_status() === FALSE)
		{
			$this->session->set_flashdata('error', 'Could not credit the float. Nothing was changed.');
			redirect('admin/agent-float/view/'.$id);
		}

		$this->log_action('Approved float order', 'agent_float_orders', $id, money($order->amount));
		$this->notify($order, 'Float approved',
			money($order->amount).' has been added to your deposit wallet.');

		$this->session->set_flashdata('success', money($order->amount).' credited to the agent deposit wallet.');
		redirect('admin/agent-float/view/'.$id);
	}

	public function reject($id)
	{
		$this->require_perm('agent_float.reject');

		$order = $this->guard($id);
		$note  = $this->input->post('admin_note', TRUE);

		if ( ! $note)
		{
			$this->session->set_flashdata('error', 'Add a note explaining the rejection.');
			redirect('admin/agent-float/view/'.$id);
		}

		$this->agent_float_order_model->update($id, array(
			'status'      => 'rejected',
			'admin_note'  => $note,
			'reviewed_by' => $this->admin->id,
			'reviewed_at' => date('Y-m-d H:i:s'),
		));

		$this->log_action('Rejected float order', 'agent_float_orders', $id, $note);
		$this->notify($order, 'Float order rejected',
			'Your '.money($order->amount).' float order was rejected. Reason: '.$note);

		$this->session->set_flashdata('success', 'Float order rejected. No balance was credited.');
		redirect('admin/agent-float/view/'.$id);
	}

	/** Shared POST + state check. */
	protected function guard($id)
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$order = $this->agent_float_order_model->find($id);

		if ( ! $order)
		{
			show_404();
		}

		if ($order->status !== 'pending')
		{
			$this->session->set_flashdata('error', 'This order is already '.$order->status.'.');
			redirect('admin/agent-float/view/'.$id);
		}

		return $order;
	}

	/**
	 * Agents have no notification inbox of their own; the one they read is
	 * their linked user account's. A standalone agent gets nothing, which is
	 * why the admin note is the record that matters.
	 */
	protected function notify($order, $title, $body)
	{
		$agent = $this->agent_model->find($order->agent_id);

		if ($agent && $agent->user_id)
		{
			$this->notification_model->push($agent->user_id, $title, $body, 'agent/float');
		}
	}
}
