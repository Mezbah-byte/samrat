<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Referrals extends Admin_Controller {

	public function index()
	{
		$this->load->model('referral_model');

		$per_page = 25;
		$page     = max(1, (int) $this->input->get('page'));
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->referral_model->paginate_admin($per_page, ($page - 1) * $per_page, $search);

		$paid = $this->db->select_sum('amount', 'total')->get('referral_commissions')->row();

		$this->render('admin/referrals', array(
			'page_title'  => 'Referrals',
			'active_menu' => 'referrals',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'search'      => $search,
			'paid_total'  => (float) ($paid->total ?: 0),
			'percent'     => (float) $this->setting_model->get('referral_percent', 5),
			'leaderboard' => $this->referral_model->leaderboard(10),
		));
	}
}
