<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Investment_model extends MY_Model {

	protected $table = 'investments';

	public function active_for_user($user_id)
	{
		return $this->db->select('i.*, p.name AS package_name')
			->from('investments i')->join('packages p', 'p.id = i.package_id', 'left')
			->where('i.user_id', (int) $user_id)->where('i.status', 'active')
			->order_by('i.id', 'DESC')->get()->result();
	}

	public function for_user($user_id, $limit, $offset = 0)
	{
		$this->db->select('i.*, p.name AS package_name')
			->from('investments i')->join('packages p', 'p.id = i.package_id', 'left')
			->where('i.user_id', (int) $user_id)
			->order_by('i.id', 'DESC')->limit((int) $limit, (int) $offset);
		$rows  = $this->db->get()->result();
		$total = (int) $this->db->where('user_id', (int) $user_id)->count_all_results($this->table);

		return array('rows' => $rows, 'total' => $total);
	}

	/** All investments the daily cron still has work to do on. */
	public function due_for_processing()
	{
		return $this->db->where('status', 'active')->order_by('id', 'ASC')->get($this->table)->result();
	}

	/**
	 * Highest min_withdraw among the user's active packages. This is the floor
	 * the withdrawal form validates against.
	 */
	public function withdraw_floor($user_id)
	{
		$row = $this->db->select_max('p.min_withdraw', 'floor_amount')
			->from('investments i')->join('packages p', 'p.id = i.package_id')
			->where('i.user_id', (int) $user_id)->where('i.status', 'active')
			->get()->row();
		return (float) ($row && $row->floor_amount !== NULL ? $row->floor_amount : 0);
	}

	/** Ads the user must watch today = highest requirement among active plans. */
	public function daily_ads_required($user_id)
	{
		$row = $this->db->select_max('daily_ads', 'ads')
			->where('user_id', (int) $user_id)->where('status', 'active')
			->get($this->table)->row();
		return (int) ($row && $row->ads !== NULL ? $row->ads : 0);
	}

	public function paginate_admin($limit, $offset, $status = '', $search = '')
	{
		$build = function () use ($status, $search) {
			$this->db->from('investments i')
				->join('users u', 'u.id = i.user_id', 'left')
				->join('packages p', 'p.id = i.package_id', 'left');
			if ($status !== '')
			{
				$this->db->where('i.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('u.username', $search)
					->or_like('u.email', $search)
					->or_like('p.name', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('i.*, u.username, u.full_name, p.name AS package_name')
			->order_by('i.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	public function stats()
	{
		$row = $this->db->select_sum('amount', 'invested')->select_sum('total_earned', 'paid')
			->where('status', 'active')->get($this->table)->row();
		return array(
			'active_count'    => (int) $this->db->where('status', 'active')->count_all_results($this->table),
			'completed_count' => (int) $this->db->where('status', 'completed')->count_all_results($this->table),
			'active_invested' => (float) ($row->invested ?: 0),
			'active_paid'     => (float) ($row->paid ?: 0),
		);
	}
}
