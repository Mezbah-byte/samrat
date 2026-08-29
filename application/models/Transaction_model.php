<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transaction_model extends MY_Model {

	protected $table = 'transactions';

	public function for_user($user_id, $limit, $offset = 0, $type = '')
	{
		$where = array('user_id' => (int) $user_id);
		if ($type !== '')
		{
			$where['type'] = $type;
		}
		return $this->paginate($limit, $offset, $where);
	}

	/** Ledger sum for one user; used to prove balance integrity. */
	public function ledger_sum($user_id)
	{
		$row = $this->db->select_sum('amount', 'total')
			->where('user_id', (int) $user_id)->get($this->table)->row();
		return (float) ($row->total ?: 0);
	}

	public function total_by_type($type, $since = NULL)
	{
		$this->db->select_sum('amount', 'total')->where('type', $type);
		if ($since)
		{
			$this->db->where('created_at >=', $since);
		}
		$row = $this->db->get($this->table)->row();
		return (float) ($row->total ?: 0);
	}

	public function paginate_with_user($limit, $offset, $type = '', $search = '')
	{
		$build = function () use ($type, $search) {
			$this->db->from('transactions t')->join('users u', 'u.id = t.user_id', 'left');
			if ($type !== '')
			{
				$this->db->where('t.type', $type);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('u.username', $search)
					->or_like('u.email', $search)
					->or_like('t.description', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('t.*, u.username, u.full_name')
			->order_by('t.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}
}
