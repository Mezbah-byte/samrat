<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ad_model extends MY_Model {

	protected $table     = 'ads';
	protected $order_by  = 'sort_order';
	protected $order_dir = 'ASC';

	/**
	 * Ads currently inside their schedule window that actually have something
	 * to show.
	 *
	 * The creative check is not cosmetic: a row with no image, no file, no
	 * network tag and no body text renders as an empty box, and the user still
	 * has to sit through the countdown to clear a quota slot. Those rows stay
	 * out of every list rather than wasting a slot.
	 */
	private function live($placement)
	{
		$today = date('Y-m-d');

		$this->db->where('status', 'active')->where('placement', $placement)
			->group_start()->where('starts_at <=', $today)->or_where('starts_at', NULL)->group_end()
			->group_start()->where('ends_at >=', $today)->or_where('ends_at', NULL)->group_end()
			->group_start()
				->group_start()
					->where('source', 'vast')->where('vast_url IS NOT NULL')->where("vast_url <>", '')
				->group_end()
				->or_group_start()
					->where('source', 'embed')->where('embed_code IS NOT NULL')->where("embed_code <>", '')
				->group_end()
				->or_group_start()
					->where('source', 'upload')
					->group_start()
						->where('media IS NOT NULL')
						->or_group_start()->where('media_url IS NOT NULL')->where("media_url <>", '')->group_end()
						->or_group_start()->where('body IS NOT NULL')->where("body <>", '')->group_end()
					->group_end()
				->group_end()
			->group_end()
			->order_by('sort_order', 'ASC')->order_by('id', 'ASC');

		return $this->db;
	}

	/** Rows an admin has created but that can never be served - for warnings. */
	public function creativeless_count()
	{
		// The outer brackets matter: CodeIgniter drops a raw condition in as-is,
		// so without them the ORs would escape the status check and every
		// creativeless row would count, active or not.
		$raw = "((source = 'vast'  AND (vast_url IS NULL   OR vast_url = ''))"
			." OR (source = 'embed' AND (embed_code IS NULL OR embed_code = ''))"
			." OR (source = 'upload' AND media IS NULL"
			."     AND (media_url IS NULL OR media_url = '')"
			."     AND (body IS NULL OR body = '')))";

		return (int) $this->db->where('status', 'active')
			->where($raw, NULL, FALSE)
			->count_all_results($this->table);
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
