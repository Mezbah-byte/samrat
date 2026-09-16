<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Agent_Controller {

	public function index()
	{
		$this->load->model(array('deposit_model', 'withdrawal_model', 'agent_commission_model'));

		$team = $this->team_ids();

		// Float-system figures. Resolved only when the feature is on, so a
		// platform still running the review-only panel loads no extra query.
		$float = array('on' => FALSE);

		if ($this->setting_model->get('agent_float_enabled', '0') === '1')
		{
			$this->load->library('agent_wallet_lib');
			$this->load->model(array('agent_wallet_model', 'agent_ledger_model'));

			$float = array(
				'on'           => TRUE,
				'balances'     => $this->agent_wallet_lib->balances($this->agent->id),
				'wallet_count' => $this->agent_wallet_model->active_count($this->agent->id),
				'recent'       => $this->agent_ledger_model->recent($this->agent->id, 8),
				'settled'      => abs($this->agent_ledger_model->sum_type($this->agent->id, 'deposit', 'deposit_settle')),
				'collected'    => $this->agent_ledger_model->sum_type($this->agent->id, 'withdraw', 'withdraw_settle'),
			);
		}

		$this->render('agent/dashboard', array(
			'page_title'   => 'Dashboard',
			'active_menu'  => 'dashboard',
			'float'        => $float,
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
