<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One row per referral generation: level 1 is the direct referrer, level 2 the
 * person who referred them, and so on. Rates are edited from
 * Admin -> Referral Levels, so nothing in the app hard-codes a percentage.
 */
class Referral_level_model extends MY_Model {

	protected $table     = 'referral_levels';
	protected $order_by  = 'level';
	protected $order_dir = 'ASC';

	/** Every generation, active or not, lowest first - for the admin screen. */
	public function ladder()
	{
		return $this->db->order_by('level', 'ASC')->get($this->table)->result();
	}

	/**
	 * The rates the payout walk actually uses: level => percent, active rows
	 * with a rate above zero only. A gap is legal - switching generation 2 off
	 * leaves 1 and 3 paying, and the walk still climbs past 2 to reach 3.
	 *
	 * @return array<int,float>
	 */
	public function active_map()
	{
		$map = array();

		foreach ($this->db->where('status', 'active')->where('percent >', 0)
			->order_by('level', 'ASC')->get($this->table)->result() as $row)
		{
			$map[(int) $row->level] = (float) $row->percent;
		}

		return $map;
	}

	/** How far up the tree the walk has to climb, active or not. */
	public function max_level()
	{
		$row = $this->db->select_max('level', 'top')->get($this->table)->row();
		return (int) ($row->top ?: 0);
	}

	public function highest_row()
	{
		return $this->db->order_by('level', 'DESC')->limit(1)->get($this->table)->row();
	}

	public function by_level($level)
	{
		return $this->find_by(array('level' => (int) $level));
	}

	/** Sum of the active rates - what one deposit costs the platform. */
	public function total_percent()
	{
		return array_sum($this->active_map());
	}

	/** Appends the next generation, so levels always stay contiguous. */
	public function append($percent = 1, $status = 'active')
	{
		return $this->insert(array(
			'level'   => $this->max_level() + 1,
			'percent' => (float) $percent,
			'status'  => $status,
		));
	}
}
