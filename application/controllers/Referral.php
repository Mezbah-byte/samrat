<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Referral extends User_Controller {

	public function index()
	{
		$this->load->model('referral_model');

		$per_page = 15;
		$page     = max(1, (int) $this->input->get('page'));
		$result   = $this->referral_model->for_referrer($this->user->id, $per_page, ($page - 1) * $per_page);

		$this->render('user/referral', array(
			'page_title'    => 'Referral',
			'active_menu'   => 'referral',
			'referral_link' => base_url('register/'.$this->user->referral_code),
			'downline'      => $this->user_model->direct_referrals($this->user->id, 50),
			'total_count'   => $this->user_model->referral_count($this->user->id),
			'earned_total'  => $this->referral_model->earned_total($this->user->id),
			'percent'       => (float) $this->setting_model->get('referral_percent', 5),
			'rows'          => $result['rows'],
			'total'         => $result['total'],
			'per_page'      => $per_page,
			'page'          => $page,
		));
	}
}
