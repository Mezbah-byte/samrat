<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Referral_model extends MY_Model {

	protected $table = 'referral_commissions';

	public function paid_for_deposit($deposit_id)
	{
		return (int) $this->db->where('deposit_id', (int) $deposit_id)->count_all_results($this->table) > 0;
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

	public function paginate_admin($limit, $offset, $search = '')
	{
		$build = function () use ($search) {
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
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('c.*, r.username AS referrer_username, d.username AS referred_username')
			->order_by('c.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
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
