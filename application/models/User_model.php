<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends MY_Model {

	protected $table = 'users';

	public function by_login($identity)
	{
		return $this->db->group_start()
				->where('username', $identity)
				->or_where('email', $identity)
			->group_end()
			->get($this->table, 1)
			->row();
	}

	public function by_referral_code($code)
	{
		return $this->db->get_where($this->table, array('referral_code' => $code), 1)->row();
	}

	/** Collision-safe referral code. */
	public function generate_referral_code($length = 8)
	{
		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I/O/0/1
		do
		{
			$code = '';
			for ($i = 0; $i < $length; $i++)
			{
				$code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
			}
			$taken = (int) $this->db->where('referral_code', $code)->count_all_results($this->table);
		}
		while ($taken > 0);

		return $code;
	}

	public function direct_referrals($user_id, $limit = NULL, $offset = 0)
	{
		$this->db->select('id, full_name, username, email, country, status, total_deposit, created_at')
			->where('referred_by', (int) $user_id)
			->order_by('id', 'DESC');
		if ($limit)
		{
			$this->db->limit((int) $limit, (int) $offset);
		}
		return $this->db->get($this->table)->result();
	}

	public function referral_count($user_id)
	{
		return (int) $this->db->where('referred_by', (int) $user_id)->count_all_results($this->table);
	}

	public function paginate_users($limit, $offset, $status = '', $search = '')
	{
		$where = array();
		if ($status !== '')
		{
			$where['status'] = $status;
		}
		return $this->paginate($limit, $offset, $where, $search,
			array('full_name', 'username', 'email', 'mobile', 'referral_code'));
	}

	public function platform_stats()
	{
		$row = $this->db->select('COUNT(*) AS total_users', FALSE)
			->select_sum('balance', 'total_balance')
			->select_sum('total_deposit', 'total_deposit')
			->select_sum('total_earned', 'total_earned')
			->select_sum('total_withdrawn', 'total_withdrawn')
			->get($this->table)->row();

		return array(
			'total_users'     => (int) $row->total_users,
			'active_users'    => (int) $this->db->where('status', 'active')->count_all_results($this->table),
			'blocked_users'   => (int) $this->db->where('status', 'blocked')->count_all_results($this->table),
			'total_balance'   => (float) ($row->total_balance ?: 0),
			'total_deposit'   => (float) ($row->total_deposit ?: 0),
			'total_earned'    => (float) ($row->total_earned ?: 0),
			'total_withdrawn' => (float) ($row->total_withdrawn ?: 0),
		);
	}

	/** Registrations per day for the admin dashboard chart. */
	public function signups_last_days($days = 14)
	{
		$rows = $this->db->select("DATE(created_at) AS d, COUNT(*) AS c", FALSE)
			->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-'.((int) $days - 1).' days')))
			->group_by('d')->order_by('d', 'ASC')->get($this->table)->result();

		$series = array();
		for ($i = $days - 1; $i >= 0; $i--)
		{
			$series[date('Y-m-d', strtotime('-'.$i.' days'))] = 0;
		}
		foreach ($rows as $r)
		{
			$series[$r->d] = (int) $r->c;
		}
		return $series;
	}
}
