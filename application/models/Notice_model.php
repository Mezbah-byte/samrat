<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notice_model extends MY_Model {

	protected $table = 'notices';

	public function published($limit = NULL)
	{
		$this->db->where('status', 'published')
			->group_start()
				->where('published_at <=', date('Y-m-d H:i:s'))
				->or_where('published_at', NULL)
			->group_end()
			->order_by('is_pinned', 'DESC')
			->order_by('published_at', 'DESC');
		if ($limit)
		{
			$this->db->limit((int) $limit);
		}
		return $this->db->get($this->table)->result();
	}

	public function published_by_slug($slug)
	{
		return $this->db->get_where($this->table, array('slug' => $slug, 'status' => 'published'), 1)->row();
	}

	public function unique_slug($title, $ignore_id = NULL)
	{
		$CI =& get_instance();
		$CI->load->helper('url');
		$base = url_title($title, 'dash', TRUE);
		$base = $base !== '' ? $base : 'notice';
		$slug = $base;
		$i    = 2;
		while (TRUE)
		{
			$this->db->where('slug', $slug);
			if ($ignore_id)
			{
				$this->db->where('id !=', (int) $ignore_id);
			}
			if ((int) $this->db->count_all_results($this->table) === 0)
			{
				return $slug;
			}
			$slug = $base.'-'.$i++;
		}
	}
}
