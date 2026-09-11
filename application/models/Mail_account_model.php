<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The mailboxes the panel can open.
 *
 * A row is a credential set, not a mailbox: creating one here does not create
 * anything on the mail server, and deleting one only makes the panel forget
 * how to log in. The mailbox itself is provisioned in cPanel.
 *
 * The password is written and read through Crypto_lib and never leaves this
 * model in ciphertext form - callers ask for `password_of()` and get cleartext
 * or NULL.
 */
class Mail_account_model extends MY_Model {

	protected $table       = 'mail_accounts';
	protected $primary_key = 'id';
	protected $order_by    = 'email';
	protected $order_dir   = 'ASC';

	public function __construct()
	{
		parent::__construct();
		$this->load->library('crypto_lib');
	}

	/**
	 * An account row joined to the endpoints it needs to connect.
	 *
	 * Imap_lib and Mailer_lib both want the account and its domain together,
	 * and every caller that opens a mailbox goes through here so neither has
	 * to know the join.
	 */
	public function with_domain($id)
	{
		return $this->db
			->select('a.*, d.domain, d.imap_host, d.imap_port, d.imap_encryption, d.imap_validate_cert,
			          d.smtp_host, d.smtp_port, d.smtp_encryption, d.sent_folder, d.trash_folder,
			          d.status AS domain_status', FALSE)
			->from($this->table.' a')
			->join('mail_domains d', 'd.id = a.domain_id')
			->where('a.id', (int) $id)
			->get()->row();
	}

	/** Paged list for the index screen, with the domain name alongside. */
	public function listing($limit, $offset, $domain_id = NULL, $status = NULL, $search = '')
	{
		$build = function () use ($domain_id, $status, $search) {
			$this->db->from($this->table.' a')->join('mail_domains d', 'd.id = a.domain_id');

			if ($domain_id)
			{
				$this->db->where('a.domain_id', (int) $domain_id);
			}

			if ($status)
			{
				$this->db->where('a.status', $status);
			}

			if ($search !== '')
			{
				$this->db->group_start()
					->like('a.email', $search)
					->or_like('a.display_name', $search)
					->group_end();
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db
			->select('a.*, d.domain', FALSE)
			->order_by('a.email', 'ASC')
			->limit((int) $limit, (int) $offset)
			->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	public function email_taken($email, $ignore_id = NULL)
	{
		$this->db->where('email', strtolower(trim($email)));

		if ($ignore_id)
		{
			$this->db->where('id !=', (int) $ignore_id);
		}

		return (int) $this->db->count_all_results($this->table) > 0;
	}

	/**
	 * Store a password, encrypted.
	 *
	 * @return bool FALSE when no usable key is configured, which the caller
	 *              must surface - silently storing an unreadable blob would
	 *              leave a mailbox that can never be opened.
	 */
	public function set_password($id, $plain)
	{
		$enc = $this->crypto_lib->encrypt($plain);

		if ($enc === FALSE)
		{
			return FALSE;
		}

		return (bool) $this->update($id, array('password_enc' => $enc));
	}

	/**
	 * Cleartext password for an account row.
	 *
	 * @param  object $account row carrying `password_enc`
	 * @return string|null NULL when the key is missing or the row is corrupt
	 */
	public function password_of($account)
	{
		if ( ! $account || empty($account->password_enc))
		{
			return NULL;
		}

		$plain = $this->crypto_lib->decrypt($account->password_enc);

		return $plain === FALSE ? NULL : $plain;
	}

	/** Record a successful connection, clearing whatever last failed. */
	public function mark_checked($id)
	{
		$this->update($id, array(
			'last_checked_at' => date('Y-m-d H:i:s'),
			'last_error'      => NULL,
		));
	}

	/** Record why a connection failed, so the list screen can show it. */
	public function mark_error($id, $message)
	{
		$this->update($id, array('last_error' => mb_substr((string) $message, 0, 255)));
	}

	public function active_count()
	{
		return (int) $this->db->where('status', 'active')->count_all_results($this->table);
	}
}
