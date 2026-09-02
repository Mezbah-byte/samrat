<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Team withdrawals, review only.
 *
 * Same rule as agent/Deposits.php: Wallet_lib is never loaded here. An agent
 * recommends; the admin approves, pays and refunds.
 */
class Withdrawals extends Agent_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('withdrawal_model');
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$team   = $this->team_ids();
		$result = $this->withdrawal_model->paginate_for_users($team, $per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('agent/withdrawals', array(
			'page_title'  => 'Team Withdrawals',
			'active_menu' => 'withdrawals',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->withdrawal_model->team_stats($team),
		));
	}

	public function view($id)
	{
		$withdrawal = $this->withdrawal_model->find_detailed($id);

		if ( ! $withdrawal)
		{
			show_404();
		}

		$this->require_team_member($withdrawal->user_id);

		$this->render('agent/withdrawal_view', array(
			'page_title'  => 'Withdrawal #'.$withdrawal->id,
			'active_menu' => 'withdrawals',
			'withdrawal'  => $withdrawal,
		));
	}

	/** Writes the four recommendation columns. Touches nothing else. */
	public function recommend($id)
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

		$this->require_team_member($row->user_id);

		if ($row->status !== 'pending')
		{
			$this->session->set_flashdata('error', 'This request is already '.$row->status.'.');
			redirect('agent/withdrawals/view/'.$id);
		}

		$choice = $this->input->post('recommendation', TRUE);

		if ( ! in_array($choice, array('approve', 'reject'), TRUE))
		{
			$this->session->set_flashdata('error', 'Choose approve or reject.');
			redirect('agent/withdrawals/view/'.$id);
		}

		$note = $this->input->post('agent_note', TRUE);

		if ($choice === 'reject' && ! $note)
		{
			$this->session->set_flashdata('error', 'Add a note explaining the rejection.');
			redirect('agent/withdrawals/view/'.$id);
		}

		$this->withdrawal_model->update($id, array(
			'agent_id'             => $this->agent->id,
			'agent_recommendation' => $choice,
			'agent_note'           => $note,
			'agent_reviewed_at'    => date('Y-m-d H:i:s'),
		));

		$this->log_action('Recommended '.$choice, 'withdrawals', $id, $note);

		$this->session->set_flashdata('success', 'Recommendation recorded. An admin makes the final decision.');
		redirect('agent/withdrawals/view/'.$id);
	}
}
