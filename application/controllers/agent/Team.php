<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only view of the agent's downline. No block, no approve, no balance
 * edit - an agent observes their team, an admin manages it.
 */
class Team extends Agent_Controller {

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$team   = $this->team_ids();
		$result = $this->user_model->paginate_downline($team, $per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('agent/team', array(
			'page_title'  => 'My Team',
			'active_menu' => 'team',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->user_model->team_stats($team),
		));
	}

	public function view($id)
	{
		// Membership is re-checked here rather than inferred from the listing:
		// a guessed id must bounce, not render.
		$this->require_team_member($id);

		$member = $this->user_model->find($id);

		if ( ! $member)
		{
			show_404();
		}

		$this->load->model(array('deposit_model', 'withdrawal_model', 'investment_model'));

		$this->render('agent/team_view', array(
			'page_title'  => 'Member #'.$member->id,
			'active_menu' => 'team',
			'member'      => $member,
			'deposits'    => $this->deposit_model->paginate_for_users(array((int) $member->id), 10, 0)['rows'],
			'withdrawals' => $this->withdrawal_model->paginate_for_users(array((int) $member->id), 10, 0)['rows'],
			'investments' => $this->investment_model->active_for_user($member->id),
			'direct'      => $this->user_model->referral_count($member->id),
		));
	}
}
