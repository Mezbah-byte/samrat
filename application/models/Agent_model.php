<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent_model extends MY_Model {

	protected $table = 'agents';

	public function by_login($identity)
	{
		return $this->db->group_start()
				->where('username', $identity)
				->or_where('email', $identity)
			->group_end()
			->get($this->table, 1)
			->row();
	}

	/** The agent account promoted from this user, if there is one. */
	public function by_user($user_id)
	{
		if ( ! $user_id)
		{
			return NULL;
		}
		return $this->db->get_where($this->table, array('user_id' => (int) $user_id), 1)->row();
	}

	/** Only an active agent may log in or earn. */
	public function active_by_user($user_id)
	{
		if ( ! $user_id)
		{
			return NULL;
		}
		return $this->db->get_where($this->table,
			array('user_id' => (int) $user_id, 'status' => 'active'), 1)->row();
	}

	public function paginate_admin($limit, $offset, $status = '', $search = '')
	{
		$where = array();
		if ($status !== '')
		{
			$where['status'] = $status;
		}
		return $this->paginate($limit, $offset, $where, $search,
			array('name', 'username', 'email', 'nid_number'));
	}

	/**
	 * Pending counters for the agent sidebar. Scoped to the agent's own team,
	 * so an empty id set must yield zeroes rather than a platform-wide count.
	 */
	public function sidebar_badges($team_ids)
	{
		if (empty($team_ids))
		{
			return array('deposits' => 0, 'withdrawals' => 0);
		}

		return array(
			'deposits' => (int) $this->db->where_in('user_id', $team_ids)
				->where('status', 'pending')->count_all_results('deposits'),
			'withdrawals' => (int) $this->db->where_in('user_id', $team_ids)
				->where('status', 'pending')->count_all_results('withdrawals'),
		);
	}

	public function stats()
	{
		$row = $this->db->select('COUNT(*) AS total', FALSE)
			->select_sum('total_commission', 'paid')
			->get($this->table)->row();

		return array(
			'total'   => (int) $row->total,
			'active'  => (int) $this->db->where('status', 'active')->count_all_results($this->table),
			'blocked' => (int) $this->db->where('status', 'blocked')->count_all_results($this->table),
			'paid'    => (float) ($row->paid ?: 0),
		);
	}

	/** Bumped inside Agent_lib's transaction, alongside the ledger insert. */
	public function add_commission($agent_id, $amount)
	{
		return $this->db->set('total_commission', 'total_commission + '.$this->db->escape(money_raw($amount)), FALSE)
			->where('id', (int) $agent_id)
			->update($this->table);
	}
}
