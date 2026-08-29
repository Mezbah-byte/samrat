<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Thin base model. Table name and primary key are declared by the child.
 */
class MY_Model extends CI_Model {

	protected $table       = '';
	protected $primary_key = 'id';
	protected $order_by    = 'id';
	protected $order_dir   = 'DESC';

	public function __construct()
	{
		parent::__construct();
	}

	public function table()
	{
		return $this->table;
	}

	public function find($id)
	{
		return $this->db->get_where($this->table, array($this->primary_key => (int) $id))->row();
	}

	public function find_by($where)
	{
		return $this->db->get_where($this->table, $where, 1)->row();
	}

	public function all($where = array(), $limit = NULL, $offset = 0, $order_by = NULL, $order_dir = NULL)
	{
		if ( ! empty($where))
		{
			$this->db->where($where);
		}
		$this->db->order_by($order_by ?: $this->order_by, $order_dir ?: $this->order_dir);
		if ($limit !== NULL)
		{
			$this->db->limit((int) $limit, (int) $offset);
		}
		return $this->db->get($this->table)->result();
	}

	public function count_by($where = array())
	{
		if ( ! empty($where))
		{
			$this->db->where($where);
		}
		return (int) $this->db->count_all_results($this->table);
	}

	public function insert($data)
	{
		$this->db->insert($this->table, $data);
		return (int) $this->db->insert_id();
	}

	public function update($id, $data)
	{
		$this->db->where($this->primary_key, (int) $id);
		return $this->db->update($this->table, $data);
	}

	public function delete($id)
	{
		$this->db->where($this->primary_key, (int) $id);
		return $this->db->delete($this->table);
	}

	/**
	 * Paged listing with an optional LIKE search across the given columns.
	 * Returns array('rows' => [], 'total' => int).
	 */
	public function paginate($limit, $offset, $where = array(), $search = '', $search_cols = array(), $order_by = NULL, $order_dir = NULL)
	{
		$build = function () use ($where, $search, $search_cols) {
			if ( ! empty($where))
			{
				$this->db->where($where);
			}
			if ($search !== '' && ! empty($search_cols))
			{
				$first = TRUE;
				$this->db->group_start();
				foreach ($search_cols as $col)
				{
					$first ? $this->db->like($col, $search) : $this->db->or_like($col, $search);
					$first = FALSE;
				}
				$this->db->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results($this->table);

		$build();
		$this->db->order_by($order_by ?: $this->order_by, $order_dir ?: $this->order_dir);
		$this->db->limit((int) $limit, (int) $offset);
		$rows = $this->db->get($this->table)->result();

		return array('rows' => $rows, 'total' => $total);
	}
}
