<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends MY_Model {

	protected $table       = 'settings';
	protected $primary_key = 'id';
	protected $order_by    = 'sort_order';
	protected $order_dir   = 'ASC';

	/** @var array|null key => value cache for the current request */
	private $cache = NULL;

	public function all_keyed()
	{
		if ($this->cache === NULL)
		{
			$this->cache = array();
			foreach ($this->db->get($this->table)->result() as $row)
			{
				$this->cache[$row->key] = $row->value;
			}
		}
		return $this->cache;
	}

	public function get($key, $default = NULL)
	{
		$all = $this->all_keyed();
		return (isset($all[$key]) && $all[$key] !== '') ? $all[$key] : $default;
	}

	public function set($key, $value)
	{
		$exists = $this->db->get_where($this->table, array('key' => $key), 1)->row();
		if ($exists)
		{
			$this->db->where('key', $key)->update($this->table, array('value' => $value));
		}
		else
		{
			$this->db->insert($this->table, array('key' => $key, 'value' => $value));
		}
		$this->cache = NULL;
	}

	/** All rows for one settings tab, ordered for the admin form. */
	public function by_group($group)
	{
		return $this->db->where('group', $group)->order_by('sort_order', 'ASC')->get($this->table)->result();
	}

	public function groups()
	{
		$rows = $this->db->distinct()->select('group')->order_by('group', 'ASC')->get($this->table)->result();
		return array_map(function ($r) { return $r->group; }, $rows);
	}
}
