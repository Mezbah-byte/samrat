<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent_log_model extends MY_Model {

	protected $table = 'agent_logs';

	public function paginate_with_agent($limit, $offset, $search = '', $agent_id = 0)
	{
		$build = function () use ($search, $agent_id) {
			$this->db->from('agent_logs l')->join('agents a', 'a.id = l.agent_id', 'left');

			if ($agent_id > 0)
			{
				$this->db->where('l.agent_id', (int) $agent_id);
			}
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
		$rows = $this->db->select('l.*, a.username AS agent_username')
			->order_by('l.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}
}
