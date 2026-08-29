<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Admin_Controller {

	public function index()
	{
		$this->load->model(array(
			'user_model', 'deposit_model', 'withdrawal_model', 'investment_model',
			'transaction_model', 'referral_model', 'ad_model',
		));

		$today = date('Y-m-d 00:00:00');

		$this->render('admin/dashboard', array(
			'page_title'   => 'Dashboard',
			'active_menu'  => 'dashboard',
			'users'        => $this->user_model->platform_stats(),
			'deposits'     => $this->deposit_model->stats(),
			'withdrawals'  => $this->withdrawal_model->stats(),
			'investments'  => $this->investment_model->stats(),
			'ads'          => $this->ad_model->stats(),
			'profit_today' => $this->transaction_model->total_by_type('daily_profit', $today),
			'signups'      => $this->user_model->signups_last_days(14),
			'leaderboard'  => $this->referral_model->leaderboard(5),
			'pending_deps' => $this->deposit_model->paginate_admin(5, 0, 'pending')['rows'],
			'pending_wds'  => $this->withdrawal_model->paginate_admin(5, 0, 'pending')['rows'],
			'cron_secret'  => $this->setting_model->get('cron_secret', ''),
		));
	}
}
