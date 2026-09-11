<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The mail domains the panel knows about.
 *
 * A domain owns the IMAP and SMTP endpoints its mailboxes share, so adding the
 * second mailbox on a domain costs a local part and a password and nothing
 * else.
 */
class Mail_domain_model extends MY_Model {

	protected $table       = 'mail_domains';
	protected $primary_key = 'id';
	protected $order_by    = 'domain';
	protected $order_dir   = 'ASC';

	/** Domains an account may be created under. */
	public function active()
	{
		return $this->db->where('status', 'active')
			->order_by('domain', 'ASC')
			->get($this->table)->result();
	}

	public function by_domain($domain)
	{
		return $this->find_by(array('domain' => strtolower(trim($domain))));
	}

	/** Is this domain already registered by somebody other than $ignore_id? */
	public function domain_taken($domain, $ignore_id = NULL)
	{
		$this->db->where('domain', strtolower(trim($domain)));

		if ($ignore_id)
		{
			$this->db->where('id !=', (int) $ignore_id);
		}

		return (int) $this->db->count_all_results($this->table) > 0;
	}

	/**
	 * Every domain with the number of mailboxes hanging off it, for the list
	 * screen. A LEFT JOIN rather than a count per row.
	 */
	public function all_with_counts()
	{
		return $this->db
			->select('d.*, COUNT(a.id) AS account_count', FALSE)
			->from($this->table.' d')
			->join('mail_accounts a', 'a.domain_id = d.id', 'left')
			->group_by('d.id')
			->order_by('d.domain', 'ASC')
			->get()->result();
	}

	public function account_count($id)
	{
		return (int) $this->db->where('domain_id', (int) $id)->count_all_results('mail_accounts');
	}
}
