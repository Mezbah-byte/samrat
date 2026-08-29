<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends MY_Model {

	protected $table = 'admins';

	public function by_login($identity)
	{
		return $this->db->group_start()
				->where('username', $identity)
				->or_where('email', $identity)
			->group_end()
			->get($this->table, 1)
			->row();
	}

	/** Pending counters shown next to the admin sidebar links. */
	public function sidebar_badges()
	{
		return array(
			'deposits'    => (int) $this->db->where('status', 'pending')->count_all_results('deposits'),
			'withdrawals' => (int) $this->db->where('status', 'pending')->count_all_results('withdrawals'),
		);
	}
}
