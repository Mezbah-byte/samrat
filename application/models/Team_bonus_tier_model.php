<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The team volume bonus ladder: one row per milestone.
 *
 * Ordered by sort_order rather than by target, so an admin can present the
 * tiers in whatever order reads best even when a 'single' tier sits between
 * two larger 'combined' ones.
 */
class Team_bonus_tier_model extends MY_Model {

	protected $table     = 'team_bonus_tiers';
	protected $order_by  = 'sort_order';
	protected $order_dir = 'ASC';

	/** Every tier, active or not - for the admin screen. */
	public function ladder()
	{
		return $this->db->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
			->get($this->table)->result();
	}

	/**
	 * The tiers that can actually unlock: switched on, with a target and a
	 * bonus worth paying. A tier at zero on either side is treated as off,
	 * the same way Referral_level_model::active_map() ignores a 0% generation.
	 */
	public function active_ladder()
	{
		return $this->db->where('status', 'active')
			->where('target_volume >', 0)->where('bonus_amount >', 0)
			->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
			->get($this->table)->result();
	}

	public function highest_row()
	{
		return $this->db->order_by('sort_order', 'DESC')->order_by('id', 'DESC')
			->limit(1)->get($this->table)->row();
	}

	public function next_sort_order()
	{
		$row = $this->db->select_max('sort_order', 'top')->get($this->table)->row();
		return (int) ($row->top ?: 0) + 1;
	}

	/** Appends a blank tier for the admin to fill in. */
	public function append()
	{
		return $this->insert(array(
			'name'          => 'New Tier',
			'target_volume' => 0,
			'bonus_amount'  => 0,
			'mode'          => 'combined',
			'min_referrals' => 0,
			'sort_order'    => $this->next_sort_order(),
			'status'        => 'inactive',
		));
	}

	/** What the whole ladder would cost one user who cleared every tier. */
	public function total_bonus()
	{
		$row = $this->db->select_sum('bonus_amount', 'total')
			->where('status', 'active')->get($this->table)->row();
		return (float) ($row->total ?: 0);
	}
}
