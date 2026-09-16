<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Agent cash-outs: the agent draining their withdraw or commission wallet
 * back out of the platform.
 *
 * Shaped after Withdrawal_model, and holds funds the same way: the wallet is
 * debited when the request is made, not when the admin pays, so the same
 * balance cannot be requested twice while a request sits pending.
 */
class Agent_payout_model extends MY_Model {

	protected $table = 'agent_payouts';

	/** One payout, but only if it belongs to this agent. */
	public function find_for_agent($id, $agent_id)
	{
		return $this->db->get_where($this->table, array(
			'id'       => (int) $id,
			'agent_id' => (int) $agent_id,
		), 1)->row();
	}

	public function for_agent($agent_id, $limit, $offset = 0, $status = '')
	{
		$build = function () use ($agent_id, $status) {
			$this->db->from($this->table)->where('agent_id', (int) $agent_id);

			if ($status !== '')
			{
				$this->db->where('status', $status);
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->order_by('id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	public function find_detailed($id)
	{
		return $this->db->select('p.*, a.name AS agent_name, a.username AS agent_username,
				a.email AS agent_email, a.withdraw_balance, a.commission_balance')
			->from('agent_payouts p')
			->join('agents a', 'a.id = p.agent_id', 'left')
			->where('p.id', (int) $id)->get()->row();
	}

	public function paginate_admin($limit, $offset, $status = '', $search = '')
	{
		$build = function () use ($status, $search) {
			$this->db->from('agent_payouts p')
				->join('agents a', 'a.id = p.agent_id', 'left');

			if ($status !== '')
			{
				$this->db->where('p.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('a.username', $search)
					->or_like('a.email', $search)
					->or_like('p.wallet_address', $search)
					->or_like('p.txid', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('p.*, a.name AS agent_name, a.username AS agent_username')
			->order_by('p.id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	public function pending_count($agent_id = NULL)
	{
		if ($agent_id !== NULL)
		{
			$this->db->where('agent_id', (int) $agent_id);
		}
		return (int) $this->db->where('status', 'pending')->count_all_results($this->table);
	}

	public function stats()
	{
		$paid = $this->db->select_sum('net_amount', 'total')
			->where('status', 'paid')->get($this->table)->row();

		return array(
			'pending_count' => (int) $this->db->where('status', 'pending')->count_all_results($this->table),
			'paid_total'    => (float) ($paid->total ?: 0),
		);
	}
}
