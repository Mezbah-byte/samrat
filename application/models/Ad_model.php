<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ad_model extends MY_Model {

	protected $table     = 'ads';
	protected $order_by  = 'sort_order';
	protected $order_dir = 'ASC';

	/** Ads currently inside their schedule window. */
	private function live($placement)
	{
		$today = date('Y-m-d');
		return $this->db->where('status', 'active')->where('placement', $placement)
			->group_start()->where('starts_at <=', $today)->or_where('starts_at', NULL)->group_end()
			->group_start()->where('ends_at >=', $today)->or_where('ends_at', NULL)->group_end()
			->order_by('sort_order', 'ASC')->order_by('id', 'ASC');
	}

	public function global_ads($limit = 5)
	{
		$this->live('global');
		return $this->db->limit((int) $limit)->get($this->table)->result();
	}

	public function daily_task_ads($limit = NULL)
	{
		$this->live('daily_task');
		if ($limit)
		{
			$this->db->limit((int) $limit);
		}
		return $this->db->get($this->table)->result();
	}

	/** Ad ids this user has already watched on the given date. */
	public function watched_ids($user_id, $date = NULL)
	{
		$date = $date ?: date('Y-m-d');
		$rows = $this->db->select('ad_id')->where('user_id', (int) $user_id)->where('view_date', $date)
			->get('ad_views')->result();
		return array_map(function ($r) { return (int) $r->ad_id; }, $rows);
	}

	public function watched_count($user_id, $date = NULL)
	{
		$date = $date ?: date('Y-m-d');
		return (int) $this->db->where('user_id', (int) $user_id)->where('view_date', $date)
			->count_all_results('ad_views');
	}

	public function increment_views($ad_id)
	{
		$this->db->set('total_views', 'total_views + 1', FALSE)
			->where('id', (int) $ad_id)->update($this->table);
	}

	public function stats()
	{
		return array(
			'active'      => (int) $this->db->where('status', 'active')->count_all_results($this->table),
			'views_today' => (int) $this->db->where('view_date', date('Y-m-d'))->count_all_results('ad_views'),
		);
	}
}
