<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Deposit_model extends MY_Model {

	protected $table = 'deposits';

	public function txid_exists($txid, $ignore_id = NULL)
	{
		$this->db->where('txid', $txid);
		if ($ignore_id)
		{
			$this->db->where('id !=', (int) $ignore_id);
		}
		return (int) $this->db->count_all_results($this->table) > 0;
	}

	public function for_user($user_id, $limit, $offset = 0)
	{
		$this->db->select('d.*, p.name AS package_name, g.username AS agent_username')
			->from('deposits d')->join('packages p', 'p.id = d.package_id', 'left')
			->join('agents g', 'g.id = d.agent_id', 'left')
			->where('d.user_id', (int) $user_id)
			->order_by('d.id', 'DESC')->limit((int) $limit, (int) $offset);
		$rows  = $this->db->get()->result();
		$total = (int) $this->db->where('user_id', (int) $user_id)->count_all_results($this->table);

		return array('rows' => $rows, 'total' => $total);
	}

	public function find_detailed($id)
	{
		return $this->db->select('d.*, p.name AS package_name, p.price AS package_price,
				u.username, u.full_name, u.email, m.name AS method_name, m.wallet_address,
				g.username AS agent_username, g.name AS agent_name, g.deposit_balance AS agent_float,
				aw.label AS agent_wallet_label, aw.wallet_address AS agent_wallet_address')
			->from('deposits d')
			->join('packages p', 'p.id = d.package_id', 'left')
			->join('users u', 'u.id = d.user_id', 'left')
			->join('deposit_methods m', 'm.id = d.deposit_method_id', 'left')
			->join('agents g', 'g.id = d.agent_id', 'left')
			->join('agent_wallets aw', 'aw.id = d.agent_wallet_id', 'left')
			->where('d.id', (int) $id)->get()->row();
	}

	public function paginate_admin($limit, $offset, $status = '', $search = '')
	{
		$build = function () use ($status, $search) {
			$this->db->from('deposits d')
				->join('users u', 'u.id = d.user_id', 'left')
				->join('packages p', 'p.id = d.package_id', 'left');
			if ($status !== '')
			{
				$this->db->where('d.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('u.username', $search)
					->or_like('u.email', $search)
					->or_like('d.txid', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('d.*, u.username, u.full_name, p.name AS package_name')
			->order_by('d.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	/**
	 * The admin listing narrowed to one set of users - the agent panel's team
	 * scope. An empty id set means an empty result, never the whole platform.
	 */
	public function paginate_for_users($ids, $limit, $offset, $status = '', $search = '')
	{
		if (empty($ids))
		{
			return array('rows' => array(), 'total' => 0);
		}

		$build = function () use ($ids, $status, $search) {
			$this->db->from('deposits d')
				->join('users u', 'u.id = d.user_id', 'left')
				->join('packages p', 'p.id = d.package_id', 'left')
				->where_in('d.user_id', $ids);

			if ($status !== '')
			{
				$this->db->where('d.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('u.username', $search)
					->or_like('u.email', $search)
					->or_like('d.txid', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('d.*, u.username, u.full_name, p.name AS package_name')
			->order_by('d.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	/**
	 * Deposits routed to one agent to settle.
	 *
	 * Scoped by agent_id, not by team: under the float system a user picks any
	 * agent with enough float, so the request is the agent's to settle whether
	 * or not the user sits in their downline.
	 */
	public function paginate_for_agent($agent_id, $limit, $offset, $agent_status = '', $search = '')
	{
		$build = function () use ($agent_id, $agent_status, $search) {
			$this->db->from('deposits d')
				->join('users u', 'u.id = d.user_id', 'left')
				->join('packages p', 'p.id = d.package_id', 'left')
				->where('d.agent_id', (int) $agent_id)
				->where('d.agent_status !=', 'none');

			if ($agent_status !== '')
			{
				$this->db->where('d.agent_status', $agent_status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('u.username', $search)
					->or_like('u.email', $search)
					->or_like('d.txid', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('d.*, u.username, u.full_name, p.name AS package_name')
			->order_by('d.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	/** One deposit, but only if it was routed to this agent. */
	public function find_for_agent($id, $agent_id)
	{
		return $this->db->select('d.*, p.name AS package_name, p.price AS package_price,
				u.username, u.full_name, u.email,
				w.label AS agent_wallet_label, w.wallet_address AS agent_wallet_address, w.network AS agent_wallet_network')
			->from('deposits d')
			->join('packages p', 'p.id = d.package_id', 'left')
			->join('users u', 'u.id = d.user_id', 'left')
			->join('agent_wallets w', 'w.id = d.agent_wallet_id', 'left')
			->where('d.id', (int) $id)
			->where('d.agent_id', (int) $agent_id)
			->get()->row();
	}

	/** Counters for the agent's own deposit-request screen. */
	public function agent_request_stats($agent_id)
	{
		$settled = $this->db->select_sum('amount', 'total')
			->where('agent_id', (int) $agent_id)->where('agent_status', 'accepted')
			->get($this->table)->row();

		$earned = $this->db->select_sum('agent_commission', 'total')
			->where('agent_id', (int) $agent_id)->where('agent_status', 'accepted')
			->get($this->table)->row();

		return array(
			'pending_count' => (int) $this->db->where('agent_id', (int) $agent_id)
				->where('agent_status', 'pending')->count_all_results($this->table),
			'settled_total' => (float) ($settled->total ?: 0),
			'earned_total'  => (float) ($earned->total ?: 0),
		);
	}

	/**
	 * Requests an agent has left sitting past the timeout.
	 *
	 * Returned rather than updated so the caller can notify per row; the cron
	 * is the only caller.
	 */
	public function stale_agent_requests($cutoff, $limit = 200)
	{
		return $this->db->where('agent_status', 'pending')
			->where('status', 'pending')
			->where('created_at <', $cutoff)
			->order_by('id', 'ASC')->limit((int) $limit)
			->get($this->table)->result();
	}

	/** Pending / recommended counters for one team. */
	public function team_stats($ids)
	{
		if (empty($ids))
		{
			return array('pending_count' => 0, 'approved_total' => 0.0, 'awaiting_review' => 0);
		}

		$approved = $this->db->select_sum('amount', 'total')
			->where_in('user_id', $ids)->where('status', 'approved')->get($this->table)->row();

		return array(
			'pending_count'  => (int) $this->db->where_in('user_id', $ids)
				->where('status', 'pending')->count_all_results($this->table),
			'approved_total' => (float) ($approved->total ?: 0),
			'awaiting_review' => (int) $this->db->where_in('user_id', $ids)
				->where('status', 'pending')->where('agent_recommendation', NULL)
				->count_all_results($this->table),
		);
	}

	public function stats()
	{
		$approved = $this->db->select_sum('amount', 'total')->where('status', 'approved')->get($this->table)->row();
		return array(
			'pending_count'  => (int) $this->db->where('status', 'pending')->count_all_results($this->table),
			'approved_total' => (float) ($approved->total ?: 0),
		);
	}
}
