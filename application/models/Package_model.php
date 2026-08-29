<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Package_model extends MY_Model {

	protected $table     = 'packages';
	protected $order_by  = 'sort_order';
	protected $order_dir = 'ASC';

	public function active()
	{
		return $this->db->where('status', 'active')->order_by('sort_order', 'ASC')->get($this->table)->result();
	}

	public function by_slug($slug)
	{
		return $this->db->get_where($this->table, array('slug' => $slug), 1)->row();
	}

	/** Unique slug generator that tolerates renames. */
	public function unique_slug($name, $ignore_id = NULL)
	{
		$CI =& get_instance();
		$CI->load->helper('url');
		$base = url_title($name, 'dash', TRUE);
		$base = $base !== '' ? $base : 'package';
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
