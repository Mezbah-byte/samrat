<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Referrals extends Admin_Controller {

	public function index()
	{
		$this->load->model(array('referral_model', 'referral_level_model'));

		$per_page = 25;
		$page     = max(1, (int) $this->input->get('page'));
		$search   = $this->input->get('q', TRUE) ?: '';
		$level    = max(0, (int) $this->input->get('level'));

		$result = $this->referral_model->paginate_admin($per_page, ($page - 1) * $per_page, $search, $level);

		$paid = $this->db->select_sum('amount', 'total')->get('referral_commissions')->row();

		$this->render('admin/referrals', array(
			'page_title'  => 'Referrals',
			'active_menu' => 'referrals',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'search'      => $search,
			'level'       => $level,
			'paid_total'  => (float) ($paid->total ?: 0),
			'ladder'      => $this->referral_level_model->ladder(),
			'total_pct'   => $this->referral_level_model->total_percent(),
			'paid_levels' => $this->referral_model->totals_by_level(),
			'leaderboard' => $this->referral_model->leaderboard(10),
		));
	}
}
