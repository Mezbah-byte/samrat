<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ads extends User_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('ad_model', 'investment_model', 'daily_earning_model'));
	}

	public function index()
	{
		$this->investment_lib->ensure_today_rows($this->user->id);

		$progress = $this->investment_lib->today_progress($this->user->id);
		$required = $progress['required'];

		// No active plan means no quota, and register_ad_view would reject every
		// view anyway - so there is nothing to offer. Listing ads here only ever
		// invited people to watch them for nothing.
		$ads = $required > 0
			? $this->ad_model->daily_task_ads($required + 5)
			: array();

		$watched = $this->ad_model->watched_ids($this->user->id);

		$this->render('user/ads', array(
			'page_title'    => 'Daily Ads',
			'active_menu'   => 'ads',
			'ads'           => $ads,
			'watched_ids'   => $watched,
			'progress'      => $progress,
			'ads_remaining' => $progress['remaining'],
			'today_rows'    => $this->daily_earning_model->today_rows($this->user->id),
		));
	}

	/**
	 * Credits one view. POST only — a GET would let a page preload burn the
	 * user's daily quota.
	 */
	public function complete($ad_id)
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$result = $this->investment_lib->register_ad_view($this->user->id, $ad_id);

		$this->session->set_flashdata($result['ok'] ? 'success' : 'error', $result['message']);
		redirect('ads');
	}

	/** Kept so a stale bookmark lands somewhere sensible. */
	public function watch($ad_id)
	{
		redirect('ads');
	}
}
