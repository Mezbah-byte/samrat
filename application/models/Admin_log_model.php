<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_log_model extends MY_Model {

	protected $table = 'admin_logs';

	public function paginate_with_admin($limit, $offset, $search = '')
	{
		$build = function () use ($search) {
			$this->db->from('admin_logs l')->join('admins a', 'a.id = l.admin_id', 'left');
			if ($search !== '')
			{
				$this->db->group_start()
					->like('l.action', $search)
					->or_like('l.module', $search)
					->or_like('a.username', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('l.*, a.username AS admin_username')
			->order_by('l.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}
}
