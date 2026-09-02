<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent_application_model extends MY_Model {

	protected $table = 'agent_applications';

	/**
	 * The application blocking a fresh one: pending (still being reviewed) or
	 * approved (already an agent). A rejected application does not block, so a
	 * user can fix what was wrong and apply again.
	 *
	 * MySQL cannot express this as a partial unique index, so this check is
	 * the only thing keeping duplicates out. Call it before every insert.
	 */
	public function open_for_user($user_id)
	{
		return $this->db->where('user_id', (int) $user_id)
			->where_in('status', array('pending', 'approved'))
			->order_by('id', 'DESC')
			->get($this->table, 1)->row();
	}

	public function latest_for_user($user_id)
	{
		return $this->db->where('user_id', (int) $user_id)
			->order_by('id', 'DESC')
			->get($this->table, 1)->row();
	}

	public function pending_count()
	{
		return (int) $this->db->where('status', 'pending')->count_all_results($this->table);
	}

	public function paginate_admin($limit, $offset, $status = '', $search = '')
	{
		$build = function () use ($status, $search) {
			$this->db->from('agent_applications a')
				->join('users u', 'u.id = a.user_id', 'left');

			if ($status !== '')
			{
				$this->db->where('a.status', $status);
			}
			if ($search !== '')
			{
				$this->db->group_start()
					->like('a.full_name', $search)
					->or_like('a.username', $search)
					->or_like('a.email', $search)
					->or_like('a.nid_number', $search)
				->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->select('a.*, u.username AS user_username, u.status AS user_status, u.referral_code')
			->order_by('a.id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	public function find_detailed($id)
	{
		return $this->db->select('a.*, u.username AS user_username, u.full_name AS user_full_name,
				u.status AS user_status, u.referral_code, u.balance, u.total_deposit, u.created_at AS user_joined,
				ad.name AS reviewer_name')
			->from('agent_applications a')
			->join('users u', 'u.id = a.user_id', 'left')
			->join('admins ad', 'ad.id = a.reviewed_by', 'left')
			->where('a.id', (int) $id)
			->get()->row();
	}
}
