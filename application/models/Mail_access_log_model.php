<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The audit trail for mailbox access.
 *
 * Kept apart from `admin_logs` because the question it answers is different:
 * not "what happened on the platform" but "who has been in this mailbox".
 * The mailbox screen shows it inline, so it has to be cheap to pull per
 * account.
 */
class Mail_access_log_model extends MY_Model {

	protected $table       = 'mail_access_logs';
	protected $primary_key = 'id';
	protected $order_by    = 'created_at';
	protected $order_dir   = 'DESC';

	/** Recent entries for one mailbox, with the admin name resolved. */
	public function for_account($account_id, $limit = 20)
	{
		return $this->db
			->select('l.*, ad.name AS admin_name', FALSE)
			->from($this->table.' l')
			->join('admins ad', 'ad.id = l.admin_id', 'left')
			->where('l.account_id', (int) $account_id)
			->order_by('l.created_at', 'DESC')
			->limit((int) $limit)
			->get()->result();
	}

	/** Paged whole-trail view, newest first. */
	public function listing($limit, $offset, $account_id = NULL, $admin_id = NULL)
	{
		$build = function () use ($account_id, $admin_id) {
			$this->db->from($this->table.' l')
				->join('admins ad', 'ad.id = l.admin_id', 'left')
				->join('mail_accounts a', 'a.id = l.account_id', 'left');

			if ($account_id)
			{
				$this->db->where('l.account_id', (int) $account_id);
			}

			if ($admin_id)
			{
				$this->db->where('l.admin_id', (int) $admin_id);
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db
			->select('l.*, ad.name AS admin_name, a.email AS account_email', FALSE)
			->order_by('l.created_at', 'DESC')
			->limit((int) $limit, (int) $offset)
			->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}
}
