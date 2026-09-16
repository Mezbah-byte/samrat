<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Float purchases: an agent buys deposit balance from the admin.
 *
 * Shaped after Deposit_model because it is the same transaction from the
 * other side - an off-platform USDT transfer, a hash, and an admin who
 * verifies it before any balance moves.
 */
class Agent_float_order_model extends MY_Model {

	protected $table = 'agent_float_orders';

	public function txid_exists($txid, $ignore_id = NULL)
	{
		$this->db->where('txid', $txid);
		if ($ignore_id)
		{
			$this->db->where('id !=', (int) $ignore_id);
		}
		return (int) $this->db->count_all_results($this->table) > 0;
	}

	/** One order, but only if it belongs to this agent. */
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
			$this->db->from('agent_float_orders o')
				->join('deposit_methods m', 'm.id = o.deposit_method_id', 'left')
				->where('o.agent_id', (int) $agent_id);

			if ($status !== '')
			{
				$this->db->where('o.status', $status);
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('o.*, m.name AS method_name, m.wallet_address')
			->order_by('o.id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	public function find_detailed($id)
	{
		return $this->db->select('o.*, a.name AS agent_name, a.username AS agent_username,
				a.email AS agent_email, a.deposit_balance,
				m.name AS method_name, m.wallet_address, m.network AS method_network')
			->from('agent_float_orders o')
			->join('agents a', 'a.id = o.agent_id', 'left')
			->join('deposit_methods m', 'm.id = o.deposit_method_id', 'left')
			->where('o.id', (int) $id)->get()->row();
	}

	public function paginate_admin($limit, $offset, $status = '', $search = '')
	{
		$build = function () use ($status, $search) {
			$this->db->from('agent_float_orders o')
				->join('agents a', 'a.id = o.agent_id', 'left');

			if ($status !== '')
			{
				$this->db->where('o.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('a.username', $search)
					->or_like('a.email', $search)
					->or_like('o.txid', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('o.*, a.name AS agent_name, a.username AS agent_username')
			->order_by('o.id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result();

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
		$approved = $this->db->select_sum('amount', 'total')
			->where('status', 'approved')->get($this->table)->row();

		return array(
			'pending_count'  => (int) $this->db->where('status', 'pending')->count_all_results($this->table),
			'approved_total' => (float) ($approved->total ?: 0),
		);
	}
}
