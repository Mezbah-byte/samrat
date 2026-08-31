<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Referral_model extends MY_Model {

	protected $table = 'referral_commissions';

	/**
	 * Has this deposit already paid out? With generations there is one row per
	 * level, so pass a level to ask about that generation alone. The unique
	 * index on (deposit_id, level) is the real guard - this is the cheap check.
	 */
	public function paid_for_deposit($deposit_id, $level = NULL)
	{
		$this->db->where('deposit_id', (int) $deposit_id);

		if ($level !== NULL)
		{
			$this->db->where('level', (int) $level);
		}

		return (int) $this->db->count_all_results($this->table) > 0;
	}

	/** Commission this user earned, split by the generation it came from. */
	public function earned_by_level($user_id)
	{
		$rows = $this->db->select('level, COUNT(*) AS deals, SUM(amount) AS earned', FALSE)
			->where('referrer_id', (int) $user_id)
			->group_by('level')->order_by('level', 'ASC')->get($this->table)->result();

		$out = array();
		foreach ($rows as $r)
		{
			$out[(int) $r->level] = array('deals' => (int) $r->deals, 'earned' => (float) $r->earned);
		}

		return $out;
	}

	public function for_referrer($user_id, $limit, $offset = 0)
	{
		$this->db->select('c.*, u.username AS referred_username, u.full_name AS referred_name')
			->from('referral_commissions c')
			->join('users u', 'u.id = c.referred_id', 'left')
			->where('c.referrer_id', (int) $user_id)
			->order_by('c.id', 'DESC')->limit((int) $limit, (int) $offset);
		$rows  = $this->db->get()->result();
		$total = (int) $this->db->where('referrer_id', (int) $user_id)->count_all_results($this->table);

		return array('rows' => $rows, 'total' => $total);
	}

	public function earned_total($user_id)
	{
		$row = $this->db->select_sum('amount', 'total')
			->where('referrer_id', (int) $user_id)->get($this->table)->row();
		return (float) ($row->total ?: 0);
	}

	public function paginate_admin($limit, $offset, $search = '', $level = 0)
	{
		$build = function () use ($search, $level) {
			$this->db->from('referral_commissions c')
				->join('users r', 'r.id = c.referrer_id', 'left')
				->join('users d', 'd.id = c.referred_id', 'left');
			if ($search !== '')
			{
				$this->db->group_start()
					->like('r.username', $search)
					->or_like('d.username', $search)
				->group_end();
			}
			if ($level > 0)
			{
				$this->db->where('c.level', (int) $level);
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('c.*, r.username AS referrer_username, d.username AS referred_username')
			->order_by('c.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	/** Paid-out totals per generation, for the admin screen. */
	public function totals_by_level()
	{
		$rows = $this->db->select('level, COUNT(*) AS deals, SUM(amount) AS paid', FALSE)
			->group_by('level')->order_by('level', 'ASC')->get($this->table)->result();

		$out = array();
		foreach ($rows as $r)
		{
			$out[(int) $r->level] = array('deals' => (int) $r->deals, 'paid' => (float) $r->paid);
		}

		return $out;
	}

	/** Top referrers by total commission, for the admin dashboard. */
	public function leaderboard($limit = 10)
	{
		return $this->db->select('c.referrer_id, u.username, u.full_name,
				COUNT(*) AS deals, SUM(c.amount) AS earned', FALSE)
			->from('referral_commissions c')
			->join('users u', 'u.id = c.referrer_id', 'left')
			->group_by('c.referrer_id')
			->order_by('earned', 'DESC')->limit((int) $limit)->get()->result();
	}
}
