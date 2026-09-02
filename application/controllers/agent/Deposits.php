<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Team deposits, review only.
 *
 * This controller deliberately never loads Wallet_lib or Investment_lib. An
 * agent records a recommendation; the admin still presses approve and the
 * money still moves in exactly one place. That absence is the safety
 * property - do not "helpfully" add an approve action here.
 */
class Deposits extends Agent_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('deposit_model');
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$team   = $this->team_ids();
		$result = $this->deposit_model->paginate_for_users($team, $per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('agent/deposits', array(
			'page_title'  => 'Team Deposits',
			'active_menu' => 'deposits',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->deposit_model->team_stats($team),
		));
	}

	public function view($id)
	{
		$deposit = $this->deposit_model->find_detailed($id);

		if ( ! $deposit)
		{
			show_404();
		}

		$this->require_team_member($deposit->user_id);

		$this->render('agent/deposit_view', array(
			'page_title'  => 'Deposit #'.$deposit->id,
			'active_menu' => 'deposits',
			'deposit'     => $deposit,
		));
	}

	/** Writes the four recommendation columns. Touches nothing else. */
	public function recommend($id)
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$deposit = $this->deposit_model->find($id);

		if ( ! $deposit)
		{
			show_404();
		}

		$this->require_team_member($deposit->user_id);

		if ($deposit->status !== 'pending')
		{
			$this->session->set_flashdata('error', 'This deposit has already been '.$deposit->status.'.');
			redirect('agent/deposits/view/'.$id);
		}

		$choice = $this->input->post('recommendation', TRUE);

		if ( ! in_array($choice, array('approve', 'reject'), TRUE))
		{
			$this->session->set_flashdata('error', 'Choose approve or reject.');
			redirect('agent/deposits/view/'.$id);
		}

		$note = $this->input->post('agent_note', TRUE);

		if ($choice === 'reject' && ! $note)
		{
			$this->session->set_flashdata('error', 'Add a note explaining the rejection.');
			redirect('agent/deposits/view/'.$id);
		}

		$this->deposit_model->update($id, array(
			'agent_id'             => $this->agent->id,
			'agent_recommendation' => $choice,
			'agent_note'           => $note,
			'agent_reviewed_at'    => date('Y-m-d H:i:s'),
		));

		$this->log_action('Recommended '.$choice, 'deposits', $id, $note);

		$this->session->set_flashdata('success', 'Recommendation recorded. An admin makes the final decision.');
		redirect('agent/deposits/view/'.$id);
	}
}
