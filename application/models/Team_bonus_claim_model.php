<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One row per user per tier, written the moment a target is met and flipped to
 * 'claimed' when the user takes the money.
 *
 * The unique index on (user_id, tier_id) is what actually prevents a second
 * payout - these methods only decide what to show.
 */
class Team_bonus_claim_model extends MY_Model {

	protected $table     = 'team_bonus_claims';
	protected $order_by  = 'unlocked_at';
	protected $order_dir = 'DESC';

	/** Every claim row this user holds, keyed by tier for a quick lookup. */
	public function map_for_user($user_id)
	{
		$map = array();

		foreach ($this->db->where('user_id', (int) $user_id)->get($this->table)->result() as $row)
		{
			$map[(int) $row->tier_id] = $row;
		}

		return $map;
	}

	public function for_user_tier($user_id, $tier_id)
	{
		return $this->find_by(array('user_id' => (int) $user_id, 'tier_id' => (int) $tier_id));
	}

	/** Unlocked but not yet taken - drives the sidebar badge. */
	public function claimable_count($user_id)
	{
		return (int) $this->db->where('user_id', (int) $user_id)
			->where('status', 'unlocked')->count_all_results($this->table);
	}

	public function claimable_total($user_id)
	{
		$row = $this->db->select_sum('bonus_amount', 'total')
			->where('user_id', (int) $user_id)->where('status', 'unlocked')
			->get($this->table)->row();
		return (float) ($row->total ?: 0);
	}

	/** Lifetime team bonus actually paid to this user. */
	public function claimed_total($user_id)
	{
		$row = $this->db->select_sum('bonus_amount', 'total')
			->where('user_id', (int) $user_id)->where('status', 'claimed')
			->get($this->table)->row();
		return (float) ($row->total ?: 0);
	}

	/** History for the user page, newest first. */
	public function claimed_for_user($user_id, $limit = 20)
	{
		return $this->db->select('c.*, t.name AS tier_name')
			->from($this->table.' c')
			->join('team_bonus_tiers t', 't.id = c.tier_id', 'left')
			->where('c.user_id', (int) $user_id)->where('c.status', 'claimed')
			->order_by('c.claimed_at', 'DESC')->limit((int) $limit)
			->get()->result();
	}

	/**
	 * Per-tier payout totals for the admin ladder screen: how many users have
	 * unlocked each tier and how much has actually gone out.
	 *
	 * @return array<int,array{unlocked:int,claimed:int,paid:float}>
	 */
	public function totals_by_tier()
	{
		$out = array();

		$rows = $this->db->select('tier_id, status, COUNT(*) AS n, SUM(bonus_amount) AS amount')
			->group_by(array('tier_id', 'status'))->get($this->table)->result();

		foreach ($rows as $r)
		{
			$tier = (int) $r->tier_id;

			if ( ! isset($out[$tier]))
			{
				$out[$tier] = array('unlocked' => 0, 'claimed' => 0, 'paid' => 0.0);
			}

			$out[$tier][$r->status] = (int) $r->n;

			if ($r->status === 'claimed')
			{
				$out[$tier]['paid'] = (float) $r->amount;
			}
		}

		return $out;
	}

	/** How many claim rows point at this tier - the delete guard in admin. */
	public function count_for_tier($tier_id)
	{
		return (int) $this->db->where('tier_id', (int) $tier_id)->count_all_results($this->table);
	}

	/** Platform-wide total paid out, for the admin header. */
	public function paid_total()
	{
		$row = $this->db->select_sum('bonus_amount', 'total')
			->where('status', 'claimed')->get($this->table)->row();
		return (float) ($row->total ?: 0);
	}
}
