<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Agent_Controller {

	public function index()
	{
		$this->load->model(array('deposit_model', 'withdrawal_model', 'agent_commission_model'));

		$team = $this->team_ids();

		$this->render('agent/dashboard', array(
			'page_title'   => 'Dashboard',
			'active_menu'  => 'dashboard',
			'team'         => $this->user_model->team_stats($team),
			'deposits'     => $this->deposit_model->team_stats($team),
			'withdrawals'  => $this->withdrawal_model->team_stats($team),
			'earned_total' => $this->agent_commission_model->earned_total($this->agent->id),
			'earned_month' => $this->agent_commission_model->earned_since($this->agent->id, date('Y-m-01 00:00:00')),
			'unsettled'    => $this->agent_commission_model->unsettled_total($this->agent->id),
			'by_source'    => $this->agent_commission_model->totals_by_source($this->agent->id),
			'recent_members' => $this->user_model->paginate_downline($team, 8, 0)['rows'],
			'pending_deps'   => $this->deposit_model->paginate_for_users($team, 5, 0, 'pending')['rows'],
			'pending_wds'    => $this->withdrawal_model->paginate_for_users($team, 5, 0, 'pending')['rows'],
		));
	}
}
