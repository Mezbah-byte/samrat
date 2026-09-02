<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Deposit_model extends MY_Model {

	protected $table = 'deposits';

	public function txid_exists($txid, $ignore_id = NULL)
	{
		$this->db->where('txid', $txid);
		if ($ignore_id)
		{
			$this->db->where('id !=', (int) $ignore_id);
		}
		return (int) $this->db->count_all_results($this->table) > 0;
	}

	public function for_user($user_id, $limit, $offset = 0)
	{
		$this->db->select('d.*, p.name AS package_name')
			->from('deposits d')->join('packages p', 'p.id = d.package_id', 'left')
			->where('d.user_id', (int) $user_id)
			->order_by('d.id', 'DESC')->limit((int) $limit, (int) $offset);
		$rows  = $this->db->get()->result();
		$total = (int) $this->db->where('user_id', (int) $user_id)->count_all_results($this->table);

		return array('rows' => $rows, 'total' => $total);
	}

	public function find_detailed($id)
	{
		return $this->db->select('d.*, p.name AS package_name, p.price AS package_price,
				u.username, u.full_name, u.email, m.name AS method_name, m.wallet_address')
			->from('deposits d')
			->join('packages p', 'p.id = d.package_id', 'left')
			->join('users u', 'u.id = d.user_id', 'left')
			->join('deposit_methods m', 'm.id = d.deposit_method_id', 'left')
			->where('d.id', (int) $id)->get()->row();
	}

	public function paginate_admin($limit, $offset, $status = '', $search = '')
	{
		$build = function () use ($status, $search) {
			$this->db->from('deposits d')
				->join('users u', 'u.id = d.user_id', 'left')
				->join('packages p', 'p.id = d.package_id', 'left');
			if ($status !== '')
			{
				$this->db->where('d.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('u.username', $search)
					->or_like('u.email', $search)
					->or_like('d.txid', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('d.*, u.username, u.full_name, p.name AS package_name')
			->order_by('d.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	/**
	 * The admin listing narrowed to one set of users - the agent panel's team
	 * scope. An empty id set means an empty result, never the whole platform.
	 */
	public function paginate_for_users($ids, $limit, $offset, $status = '', $search = '')
	{
		if (empty($ids))
		{
			return array('rows' => array(), 'total' => 0);
		}

		$build = function () use ($ids, $status, $search) {
			$this->db->from('deposits d')
				->join('users u', 'u.id = d.user_id', 'left')
				->join('packages p', 'p.id = d.package_id', 'left')
				->where_in('d.user_id', $ids);

			if ($status !== '')
			{
				$this->db->where('d.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('u.username', $search)
					->or_like('u.email', $search)
					->or_like('d.txid', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('d.*, u.username, u.full_name, p.name AS package_name')
			->order_by('d.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	/** Pending / recommended counters for one team. */
	public function team_stats($ids)
	{
		if (empty($ids))
		{
			return array('pending_count' => 0, 'approved_total' => 0.0, 'awaiting_review' => 0);
		}

		$approved = $this->db->select_sum('amount', 'total')
			->where_in('user_id', $ids)->where('status', 'approved')->get($this->table)->row();

		return array(
			'pending_count'  => (int) $this->db->where_in('user_id', $ids)
				->where('status', 'pending')->count_all_results($this->table),
			'approved_total' => (float) ($approved->total ?: 0),
			'awaiting_review' => (int) $this->db->where_in('user_id', $ids)
				->where('status', 'pending')->where('agent_recommendation', NULL)
				->count_all_results($this->table),
		);
	}

	public function stats()
	{
		$approved = $this->db->select_sum('amount', 'total')->where('status', 'approved')->get($this->table)->row();
		return array(
			'pending_count'  => (int) $this->db->where('status', 'pending')->count_all_results($this->table),
			'approved_total' => (float) ($approved->total ?: 0),
		);
	}
}
