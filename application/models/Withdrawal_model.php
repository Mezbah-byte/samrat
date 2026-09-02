<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Withdrawal_model extends MY_Model {

	protected $table = 'withdrawals';

	public function for_user($user_id, $limit, $offset = 0)
	{
		return $this->paginate($limit, $offset, array('user_id' => (int) $user_id));
	}

	public function pending_count_for_user($user_id)
	{
		return (int) $this->db->where('user_id', (int) $user_id)->where('status', 'pending')
			->count_all_results($this->table);
	}

	public function find_detailed($id)
	{
		return $this->db->select('w.*, u.username, u.full_name, u.email, u.balance')
			->from('withdrawals w')->join('users u', 'u.id = w.user_id', 'left')
			->where('w.id', (int) $id)->get()->row();
	}

	public function paginate_admin($limit, $offset, $status = '', $search = '')
	{
		$build = function () use ($status, $search) {
			$this->db->from('withdrawals w')->join('users u', 'u.id = w.user_id', 'left');
			if ($status !== '')
			{
				$this->db->where('w.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('u.username', $search)
					->or_like('u.email', $search)
					->or_like('w.wallet_address', $search)
					->or_like('w.txid', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('w.*, u.username, u.full_name')
			->order_by('w.id', 'DESC')->limit($limit, $offset)->get()->result();

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
			$this->db->from('withdrawals w')->join('users u', 'u.id = w.user_id', 'left')
				->where_in('w.user_id', $ids);

			if ($status !== '')
			{
				$this->db->where('w.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('u.username', $search)
					->or_like('u.email', $search)
					->or_like('w.wallet_address', $search)
					->or_like('w.txid', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('w.*, u.username, u.full_name')
			->order_by('w.id', 'DESC')->limit($limit, $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	/** Pending / recommended counters for one team. */
	public function team_stats($ids)
	{
		if (empty($ids))
		{
			return array('pending_count' => 0, 'paid_total' => 0.0, 'awaiting_review' => 0);
		}

		$paid = $this->db->select_sum('net_amount', 'total')
			->where_in('user_id', $ids)->where('status', 'paid')->get($this->table)->row();

		return array(
			'pending_count' => (int) $this->db->where_in('user_id', $ids)
				->where('status', 'pending')->count_all_results($this->table),
			'paid_total'    => (float) ($paid->total ?: 0),
			'awaiting_review' => (int) $this->db->where_in('user_id', $ids)
				->where('status', 'pending')->where('agent_recommendation', NULL)
				->count_all_results($this->table),
		);
	}

	public function stats()
	{
		$paid = $this->db->select_sum('net_amount', 'total')->where('status', 'paid')->get($this->table)->row();
		$fees = $this->db->select_sum('fee', 'total')->where_in('status', array('approved', 'paid'))->get($this->table)->row();
		return array(
			'pending_count' => (int) $this->db->where('status', 'pending')->count_all_results($this->table),
			'paid_total'    => (float) ($paid->total ?: 0),
			'fee_total'     => (float) ($fees->total ?: 0),
		);
	}
}
