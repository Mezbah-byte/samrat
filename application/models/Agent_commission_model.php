<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent_commission_model extends MY_Model {

	protected $table = 'agent_commissions';

	/**
	 * Has this agent already been paid for this event? The unique index on
	 * (agent_id, source, reference_id) is the real guard - this is the cheap
	 * check that avoids a failed insert, mirroring Referral_model.
	 */
	public function paid_for($agent_id, $source, $reference_id)
	{
		return (int) $this->db->where('agent_id', (int) $agent_id)
			->where('source', $source)
			->where('reference_id', (int) $reference_id)
			->count_all_results($this->table) > 0;
	}

	public function for_agent($agent_id, $limit, $offset = 0, $source = '')
	{
		$build = function () use ($agent_id, $source) {
			$this->db->from('agent_commissions c')
				->join('users u', 'u.id = c.user_id', 'left')
				->where('c.agent_id', (int) $agent_id);

			if ($source !== '')
			{
				$this->db->where('c.source', $source);
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('c.*, u.username AS member_username, u.full_name AS member_name')
			->order_by('c.id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	public function earned_total($agent_id)
	{
		$row = $this->db->select_sum('amount', 'total')
			->where('agent_id', (int) $agent_id)->get($this->table)->row();

		return (float) ($row->total ?: 0);
	}

	/** Earned since a datetime, for the "this month" tile. */
	public function earned_since($agent_id, $since)
	{
		$row = $this->db->select_sum('amount', 'total')
			->where('agent_id', (int) $agent_id)
			->where('created_at >=', $since)
			->get($this->table)->row();

		return (float) ($row->total ?: 0);
	}

	/** Split by where the money came from, for the earnings screen. */
	public function totals_by_source($agent_id)
	{
		$out = array(
			'deposit'      => array('deals' => 0, 'earned' => 0.0),
			'daily_profit' => array('deals' => 0, 'earned' => 0.0),
		);

		$rows = $this->db->select('source, COUNT(*) AS deals, SUM(amount) AS earned', FALSE)
			->where('agent_id', (int) $agent_id)
			->group_by('source')->get($this->table)->result();

		foreach ($rows as $r)
		{
			$out[$r->source] = array('deals' => (int) $r->deals, 'earned' => (float) $r->earned);
		}

		return $out;
	}

	/** Accrued but never credited to a wallet - an unlinked agent's balance. */
	public function unsettled_total($agent_id)
	{
		$row = $this->db->select_sum('amount', 'total')
			->where('agent_id', (int) $agent_id)->where('settled', 0)
			->get($this->table)->row();

		return (float) ($row->total ?: 0);
	}
}
