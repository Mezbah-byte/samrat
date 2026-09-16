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
	 * Pending counters for the agent sidebar.
	 *
	 * Two different scopes live here and must not be confused. The team
	 * counters are the review queue and are scoped by the agent's downline -
	 * an empty id set yields zeroes rather than a platform-wide count. The
	 * float counters are scoped by agent_id instead: a request routed to this
	 * agent is theirs to settle whether or not the user is in their team.
	 */
	public function sidebar_badges($team_ids, $agent_id = NULL)
	{
		$badges = array(
			'deposits' => 0, 'withdrawals' => 0,
			'req_deposits' => 0, 'req_withdrawals' => 0,
			'float' => 0, 'payouts' => 0,
		);

		if ( ! empty($team_ids))
		{
			$badges['deposits'] = (int) $this->db->where_in('user_id', $team_ids)
				->where('status', 'pending')->count_all_results('deposits');
			$badges['withdrawals'] = (int) $this->db->where_in('user_id', $team_ids)
				->where('status', 'pending')->count_all_results('withdrawals');
		}

		if ($agent_id)
		{
			$badges['req_deposits'] = (int) $this->db->where('agent_id', (int) $agent_id)
				->where('agent_status', 'pending')->count_all_results('deposits');
			$badges['req_withdrawals'] = (int) $this->db->where('agent_id', (int) $agent_id)
				->where('agent_status', 'pending')->count_all_results('withdrawals');
			$badges['float'] = (int) $this->db->where('agent_id', (int) $agent_id)
				->where('status', 'pending')->count_all_results('agent_float_orders');
			$badges['payouts'] = (int) $this->db->where('agent_id', (int) $agent_id)
				->where('status', 'pending')->count_all_results('agent_payouts');
		}

		return $badges;
	}

	public function stats()
	{
		// `float` is unspent float in agent hands and `owed` is what the
		// platform still has to pay agents out - both are liabilities, so the
		// admin list shows them next to the headcount rather than buried.
		$row = $this->db->select('COUNT(*) AS total', FALSE)
			->select_sum('total_commission', 'paid')
			->select_sum('deposit_balance', 'float')
			->select('SUM(withdraw_balance + commission_balance) AS owed', FALSE)
			->get($this->table)->row();

		return array(
			'total'   => (int) $row->total,
			'active'  => (int) $this->db->where('status', 'active')->count_all_results($this->table),
			'blocked' => (int) $this->db->where('status', 'blocked')->count_all_results($this->table),
			'paid'    => (float) ($row->paid ?: 0),
			'float'   => (float) ($row->float ?: 0),
			'owed'    => (float) ($row->owed ?: 0),
		);
	}

	/**
	 * Agents a user may pick to route a deposit through.
	 *
	 * Three conditions, all of which the accept action re-checks: active,
	 * taking deposit work, and holding enough float to settle this exact
	 * amount. `agent_min_float` raises the bar further so an agent scraping
	 * the bottom of their float is not offered at all.
	 *
	 * @param float $amount the deposit that has to be settled
	 * @return object[]
	 */
	public function available_for_deposit($amount, $min_float = 0)
	{
		$needed = max(round((float) $amount, MONEY_SCALE), (float) $min_float);

		return $this->db->select('id, name, username, deposit_balance')
			->where('status', 'active')
			->where('accepting_deposits', 1)
			->where('deposit_balance >=', money_raw($needed))
			->order_by('deposit_balance', 'DESC')
			->get($this->table)->result();
	}

	/**
	 * Agents a user may pick to be paid by.
	 *
	 * No float test here: the agent pays a withdrawal out of their own pocket
	 * and collects into their withdraw wallet, so holding float is irrelevant.
	 *
	 * @return object[]
	 */
	public function available_for_withdraw()
	{
		return $this->db->select('id, name, username')
			->where('status', 'active')
			->where('accepting_withdrawals', 1)
			->order_by('username', 'ASC')
			->get($this->table)->result();
	}

	/** Bumped inside Agent_lib's transaction, alongside the ledger insert. */
	public function add_commission($agent_id, $amount)
	{
		return $this->db->set('total_commission', 'total_commission + '.$this->db->escape(money_raw($amount)), FALSE)
			->where('id', (int) $agent_id)
			->update($this->table);
	}
}
