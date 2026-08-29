<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends User_Controller {

	public function index()
	{
		$this->load->model(array(
			'investment_model', 'transaction_model', 'referral_model',
			'daily_earning_model', 'notice_model', 'ad_model',
		));

		// Make sure today's rows exist even if the cron has not run yet.
		$this->investment_lib->ensure_today_rows($this->user->id);

		$progress    = $this->investment_lib->today_progress($this->user->id);
		$investments = $this->investment_model->active_for_user($this->user->id);
		$history     = $this->daily_earning_model->for_user($this->user->id, 30);

		$this->render('user/dashboard', array(
			'page_title'     => 'Dashboard',
			'active_menu'    => 'dashboard',
			'use_charts'     => TRUE,
			'ads_remaining'  => $progress['remaining'],
			'progress'       => $progress,
			'investments'    => $investments,
			'referral_count' => $this->user_model->referral_count($this->user->id),
			'referral_total' => $this->referral_model->earned_total($this->user->id),
			'recent_tx'      => $this->transaction_model->all(array('user_id' => $this->user->id), 8),
			'recent_days'    => array('rows' => array_slice($history['rows'], 0, 7), 'total' => $history['total']),
			'earning_series' => $this->earning_series($history['rows']),
			'plan_split'     => $this->plan_split($investments),
			'notices'        => $this->notice_model->published(3),
			'global_ads'     => $this->ad_model->global_ads(2),
		));
	}

	/**
	 * Last 30 calendar days of credited earnings, oldest first, with the gaps
	 * filled in so the chart has one point per day rather than one per row.
	 */
	private function earning_series($rows)
	{
		$byDate = array();
		foreach ($rows as $r)
		{
			if ($r->status !== 'credited')
			{
				continue;
			}
			$key = substr($r->earn_date, 0, 10);
			$byDate[$key] = (isset($byDate[$key]) ? $byDate[$key] : 0) + (float) $r->amount;
		}

		$labels = array();
		$values = array();
		for ($i = 29; $i >= 0; $i--)
		{
			$day      = date('Y-m-d', strtotime('-'.$i.' days'));
			$labels[] = date('j M', strtotime($day));
			$values[] = round(isset($byDate[$day]) ? $byDate[$day] : 0, 2);
		}

		return array('labels' => $labels, 'values' => $values, 'label' => 'Daily profit', 'prefix' => currency());
	}

	/** How the user's money is split across the packages they hold. */
	private function plan_split($investments)
	{
		$totals = array();
		foreach ($investments as $inv)
		{
			$name = $inv->package_name;
			$totals[$name] = (isset($totals[$name]) ? $totals[$name] : 0) + (float) $inv->amount;
		}

		return array(
			'labels' => array_keys($totals),
			'values' => array_map(function ($v) { return round($v, 2); }, array_values($totals)),
			'prefix' => currency(),
		);
	}
}
