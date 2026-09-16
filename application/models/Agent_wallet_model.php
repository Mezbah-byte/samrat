<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The receive addresses an agent publishes to the users who pay them.
 *
 * Every lookup here is scoped by agent_id, including the single-row ones: an
 * agent editing a wallet, and a user paying one, must both be unable to reach
 * a row belonging to someone else by putting another id in the URL.
 */
class Agent_wallet_model extends MY_Model {

	protected $table     = 'agent_wallets';
	protected $order_by  = 'sort_order';
	protected $order_dir = 'ASC';

	/** Every wallet an agent owns, active or not - the agent's own listing. */
	public function for_agent($agent_id)
	{
		return $this->db->where('agent_id', (int) $agent_id)
			->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
			->get($this->table)->result();
	}

	/** The ones a user may actually be shown. */
	public function active_for_agent($agent_id)
	{
		return $this->db->where('agent_id', (int) $agent_id)
			->where('status', 'active')
			->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
			->get($this->table)->result();
	}

	/**
	 * One wallet, but only if it belongs to this agent.
	 *
	 * @return object|null
	 */
	public function find_for_agent($id, $agent_id)
	{
		return $this->db->get_where($this->table, array(
			'id'       => (int) $id,
			'agent_id' => (int) $agent_id,
		), 1)->row();
	}

	/** Same, narrowed to a wallet a user is allowed to pay into. */
	public function find_active_for_agent($id, $agent_id)
	{
		return $this->db->get_where($this->table, array(
			'id'       => (int) $id,
			'agent_id' => (int) $agent_id,
			'status'   => 'active',
		), 1)->row();
	}

	public function active_count($agent_id)
	{
		return (int) $this->db->where('agent_id', (int) $agent_id)
			->where('status', 'active')->count_all_results($this->table);
	}

	/** Bulk fetch for the user-facing agent picker, one query for the page. */
	public function active_for_agents($agent_ids)
	{
		if (empty($agent_ids))
		{
			return array();
		}

		$rows = $this->db->where_in('agent_id', $agent_ids)
			->where('status', 'active')
			->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
			->get($this->table)->result();

		$grouped = array();

		foreach ($rows as $row)
		{
			$grouped[(int) $row->agent_id][] = $row;
		}

		return $grouped;
	}
}
