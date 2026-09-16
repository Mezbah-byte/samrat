<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Earnings extends Agent_Controller {

	public function index()
	{
		$this->load->model('agent_commission_model');

		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$source   = $this->input->get('source', TRUE) ?: '';
		$float_on = $this->setting_model->get('agent_float_enabled', '0') === '1';

		// The float sources only exist as a filter while the feature is on,
		// so an old bookmarked URL cannot produce a confusing empty screen.
		$sources = $float_on
			? array('', 'deposit', 'daily_profit', 'agent_deposit', 'agent_withdraw')
			: array('', 'deposit', 'daily_profit');

		if ( ! in_array($source, $sources, TRUE))
		{
			$source = '';
		}

		$result = $this->agent_commission_model->for_agent(
			$this->agent->id, $per_page, ($page - 1) * $per_page, $source
		);

		$this->render('agent/earnings', array(
			'page_title'   => 'Earnings',
			'active_menu'  => 'earnings',
			'rows'         => $result['rows'],
			'total'        => $result['total'],
			'per_page'     => $per_page,
			'page'         => $page,
			'source'       => $source,
			'float_on'     => $float_on,
			'earned_total' => $this->agent_commission_model->earned_total($this->agent->id),
			'earned_month' => $this->agent_commission_model->earned_since($this->agent->id, date('Y-m-01 00:00:00')),
			'unsettled'    => $this->agent_commission_model->unsettled_total($this->agent->id),
			'by_source'    => $this->agent_commission_model->totals_by_source($this->agent->id),
			'deposit_pct'  => $this->agent->commission_deposit_percent !== NULL
				? $this->agent->commission_deposit_percent
				: setting('agent_deposit_percent', '1'),
			'profit_pct'   => $this->agent->commission_profit_percent !== NULL
				? $this->agent->commission_profit_percent
				: setting('agent_profit_percent', '0.5'),
			'settle_pct'   => $this->agent->commission_settle_percent !== NULL
				? $this->agent->commission_settle_percent
				: setting('agent_deposit_commission_percent', '1'),
			'withdraw_pct' => $this->agent->commission_withdraw_percent !== NULL
				? $this->agent->commission_withdraw_percent
				: setting('agent_withdraw_commission_percent', '1'),
		));
	}
}
