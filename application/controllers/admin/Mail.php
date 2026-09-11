<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mailboxes, and the webmail client for them.
 *
 * Two jobs in one controller because they are two halves of the same thing:
 * the list screen manages the credentials, and everything from `open()` down
 * uses those credentials to talk to the mail server.
 *
 * Registering a mailbox here creates nothing. The mailbox is made in cPanel;
 * this stores the address and password the panel logs in with, and removing a
 * row only makes the panel forget them.
 *
 * The IMAP folder never travels in the URL path. Folder names carry dots,
 * spaces and non-ASCII characters, none of which survive `permitted_uri_chars`
 * or CI's segment splitting, so every screen takes it as `?f=`.
 */
class Mail extends Admin_Controller {

	/** Messages per page in the list. */
	const PER_PAGE = 25;

	/** Ceiling on one composed message's attachments, in bytes. */
	const MAX_ATTACH_BYTES = 10485760; // 10 MB

	/** @var object|null the account the current request is working in */
	protected $account = NULL;

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('mail_domain_model', 'mail_account_model', 'mail_access_log_model'));
	}

	/* ================================================================ */
	/*  Mailbox list and credentials                                    */
	/* ================================================================ */

	public function index()
	{
		$this->require_perm('mail.view');

		$page   = max(1, (int) $this->input->get('page'));
		$domain = (int) $this->input->get('domain') ?: NULL;
		$status = $this->input->get('status', TRUE);
		$search = trim((string) $this->input->get('q', TRUE));

		if ( ! in_array($status, array('active', 'suspended'), TRUE))
		{
			$status = NULL;
		}

		$per  = 20;
		$list = $this->mail_account_model->listing($per, ($page - 1) * $per, $domain, $status, $search);

		$this->load->library(array('imap_lib', 'crypto_lib'));

		$this->render('admin/mail_accounts', array(
			'page_title'   => 'Mailboxes',
			'active_menu'  => 'mail',
			'rows'         => $list['rows'],
			'total'        => $list['total'],
			'page'         => $page,
			'per_page'     => $per,
			'domains'      => $this->mail_domain_model->all(),
			'f_domain'     => $domain,
			'f_status'     => $status,
			'f_search'     => $search,
			'can_manage'   => $this->can('mail.manage'),
			'can_access'   => $this->can('mail.access'),
			// Both are environment problems rather than data problems, and
			// each one makes every mailbox unusable, so the screen says so
			// once at the top instead of failing per row.
			'imap_ready'   => $this->imap_lib->available(),
			'crypto_ready' => $this->crypto_lib->ready(),
		));
	}

	public function create()
	{
		$this->form($this->blank(), 'create');
	}

	public function edit($id)
	{
		$row = $this->mail_account_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$this->form($row, 'edit');
	}

	protected function form($row, $mode)
	{
		$this->require_perm('mail.manage');

		$domains = $this->mail_domain_model->active();

		if (empty($domains) && $mode === 'create')
		{
			$this->session->set_flashdata('error', 'Add a mail domain first - a mailbox has to live on one.');
			redirect('admin/mail-domains');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('domain_id', 'Domain', 'required|integer');
			$this->form_validation->set_rules('local_part', 'Mailbox name', 'required|trim|max_length[120]');
			$this->form_validation->set_rules('display_name', 'Display name', 'trim|max_length[120]');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,suspended]');
			$this->form_validation->set_rules('signature', 'Signature', 'trim|max_length[2000]');

			// On create the password is the whole point of the row. On edit a
			// blank field means "leave it alone", which is what an admin
			// editing a display name expects.
			$this->form_validation->set_rules('password', 'Password', $mode === 'create' ? 'required' : '');

			if ($this->form_validation->run())
			{
				$domain = $this->mail_domain_model->find($this->input->post('domain_id'));

				if ( ! $domain)
				{
					$this->session->set_flashdata('error', 'That domain no longer exists.');
					redirect($this->back_to($mode, $row));
				}

				$local = $this->normalise_local_part($this->input->post('local_part', TRUE));

				if ($local === '')
				{
					$this->session->set_flashdata('error', 'That is not a usable mailbox name. Use the part before the @, without spaces.');
					redirect($this->back_to($mode, $row));
				}

				$email = $local.'@'.$domain->domain;

				if ($this->mail_account_model->email_taken($email, $row->id))
				{
					$this->session->set_flashdata('error', $email.' is already registered.');
					redirect($this->back_to($mode, $row));
				}

				$data = array(
					'domain_id'    => (int) $domain->id,
					'local_part'   => $local,
					'email'        => $email,
					'display_name' => $this->input->post('display_name', TRUE) ?: NULL,
					'signature'    => $this->input->post('signature', TRUE) ?: NULL,
					'status'       => $this->input->post('status', TRUE),
				);

				$password = (string) $this->input->post('password', FALSE);

				if ($password !== '')
				{
					$enc = $this->crypto_lib_ready()->encrypt($password);

					if ($enc === FALSE)
					{
						$this->session->set_flashdata('error',
							'The mailbox password could not be encrypted. Set mail_crypt_key in application/config/secrets.php first.');
						redirect($this->back_to($mode, $row));
					}

					$data['password_enc'] = $enc;
					// Whatever went wrong last time was with the old password.
					$data['last_error']   = NULL;
				}

				if ($mode === 'edit')
				{
					$this->mail_account_model->update($row->id, $data);
					$this->log_action('Updated mailbox', 'mail', $row->id, $email);
					$this->log_access('update', $row->id, $email);
					$this->session->set_flashdata('success', 'Mailbox updated.');
					$new_id = $row->id;
				}
				else
				{
					$data['created_by'] = $this->admin->id;
					$new_id = $this->mail_account_model->insert($data);
					$this->log_action('Registered mailbox', 'mail', $new_id, $email);
					$this->log_access('create', $new_id, $email);
					$this->session->set_flashdata('success', 'Mailbox added. Run the connection test to confirm the credentials.');
				}

				redirect('admin/mail/test/'.$new_id);
			}
		}

		$this->render('admin/mail_account_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit Mailbox' : 'New Mailbox',
			'active_menu' => 'mail',
			'm'           => $row,
			'mode'        => $mode,
			'domains'     => $mode === 'edit' ? $this->mail_domain_model->all() : $domains,
		));
	}

	public function delete($id)
	{
		$this->require_perm('mail.manage');

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->mail_account_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$this->mail_account_model->delete($id);
		$this->log_action('Removed mailbox', 'mail', $id, $row->email);
		$this->log_access('remove', NULL, $row->email);

		$this->session->set_flashdata('success',
			'The panel has forgotten '.$row->email.'. The mailbox itself and everything in it are untouched on the server.');
		redirect('admin/mail');
	}

	/**
	 * Prove the stored credentials work, against both servers.
	 *
	 * Both are tested because they fail independently: a password change on the
	 * server breaks each of them, but a firewall usually only blocks one port,
	 * and being told which half is broken is most of the diagnosis.
	 */
	public function test($id)
	{
		$this->require_perm('mail.manage');

		$account = $this->mail_account_model->with_domain($id);

		if ( ! $account)
		{
			show_404();
		}

		$this->load->library(array('imap_lib', 'mailer_lib'));

		$password = $this->mail_account_model->password_of($account);
		$results  = array();

		if ($password === NULL)
		{
			$results['imap'] = array(FALSE, 'The stored password could not be decrypted. Check mail_crypt_key in secrets.php, then re-enter the password.');
			$results['smtp'] = $results['imap'];
		}
		else
		{
			$imap_ok = $this->imap_lib->open($account, $password);
			$results['imap'] = array($imap_ok, $imap_ok ? 'Connected and signed in.' : $this->imap_lib->error());

			$folders = $imap_ok ? $this->imap_lib->folders() : array();
			$this->imap_lib->close();

			$smtp_ok = $this->mailer_lib->test($account, $password);
			$results['smtp'] = array($smtp_ok, $smtp_ok ? 'Accepted the password.' : $this->mailer_lib->error());

			if ($imap_ok)
			{
				$this->mail_account_model->mark_checked($account->id);
			}
			else
			{
				$this->mail_account_model->mark_error($account->id, $this->imap_lib->error());
			}

			$results['folders'] = $folders;
		}

		$this->log_access('test', $account->id, ($results['imap'][0] ? 'imap ok' : 'imap failed').', '.($results['smtp'][0] ? 'smtp ok' : 'smtp failed'));

		$this->render('admin/mail_test', array(
			'page_title'  => 'Connection Test',
			'active_menu' => 'mail',
			'account'     => $account,
			'results'     => $results,
		));
	}

	/**
	 * The access trail.
	 *
	 * `mail.view` rather than `mail.access`: being able to see who has been
	 * reading the company mail is a supervisory question, and gating it behind
	 * the capability to read that mail yourself would put the audit trail in
	 * the hands of exactly the people it exists to record.
	 */
	public function logs($id = NULL)
	{
		$this->require_perm('mail.view');

		$account = NULL;

		if ($id !== NULL)
		{
			$account = $this->mail_account_model->find($id);

			if ( ! $account)
			{
				show_404();
			}
		}

		$page = max(1, (int) $this->input->get('page'));
		$per  = 40;

		$list = $this->mail_access_log_model->listing($per, ($page - 1) * $per, $account ? $account->id : NULL);

		$this->render('admin/mail_logs', array(
			'page_title'  => 'Mailbox Access Log',
			'active_menu' => 'mail',
			'rows'        => $list['rows'],
			'total'       => $list['total'],
			'page'        => $page,
			'per_page'    => $per,
			'account'     => $account,
		));
	}

	/* ================================================================ */
	/*  Webmail                                                         */
	/* ================================================================ */

	/** A folder's messages, newest first. */
	public function open($id)
	{
		$this->require_perm('mail.access');

		if ( ! ($account = $this->connect($id)))
		{
			return;
		}

		$folder = $this->requested_folder();
		$page   = max(1, (int) $this->input->get('page'));
		$search = trim((string) $this->input->get('q', TRUE));

		if ( ! $this->imap_lib->select($folder))
		{
			$this->session->set_flashdata('error', $this->imap_lib->error());
			$folder = 'INBOX';
			$this->imap_lib->select($folder);
		}

		$list = $this->imap_lib->messages($page, self::PER_PAGE, $search);

		$this->log_access('open', $account->id, $folder.($search !== '' ? ' (search)' : ''));
		$this->mail_account_model->mark_checked($account->id);

		$this->render('admin/mail_inbox', array(
			'page_title'  => $account->email,
			'active_menu' => 'mail',
			'account'     => $account,
			'folders'     => $this->imap_lib->folders(),
			'folder'      => $folder,
			'rows'        => $list['rows'],
			'total'       => $list['total'],
			'page'        => $page,
			'per_page'    => self::PER_PAGE,
			'search'      => $search,
			'can_send'    => $this->can('mail.send'),
			'trash'       => $account->trash_folder,
		));
	}

	/** One message, read. */
	public function message($id, $uid)
	{
		$this->require_perm('mail.access');

		if ( ! ($account = $this->connect($id)))
		{
			return;
		}

		$folder = $this->requested_folder();

		if ( ! $this->imap_lib->select($folder))
		{
			$this->session->set_flashdata('error', $this->imap_lib->error());
			redirect($this->mail_url($id, 'INBOX'));
		}

		$message = $this->imap_lib->message((int) $uid);

		if ($message === FALSE)
		{
			$this->session->set_flashdata('error', $this->imap_lib->error());
			redirect($this->mail_url($id, $folder));
		}

		$show_images = (bool) $this->input->get('images');

		$this->load->library('mail_sanitizer_lib');

		// Inline parts are addressed by Content-ID from inside the body; they
		// are served from our own attachment route rather than inlined, so a
		// mail with a megabyte of embedded images does not become a megabyte
		// of base64 in the page source.
		$inline_urls = array();

		foreach ($message['inline'] as $cid => $part)
		{
			$inline_urls[$cid] = html_escape($this->attachment_url($id, $folder, $message['uid'], $part['part']));
		}

		if ($message['html'] !== '')
		{
			$body = $this->mail_sanitizer_lib->clean($message['html'], $show_images, $inline_urls);
		}
		else
		{
			$body = $this->mail_sanitizer_lib->text_to_html($message['text']);
		}

		$this->log_access('read', $account->id, $folder.' #'.(int) $uid.' - '.short_txt($message['subject'], 40, 0));

		$this->render('admin/mail_message', array(
			'page_title'     => $message['subject'],
			'active_menu'    => 'mail',
			'account'        => $account,
			'folders'        => $this->imap_lib->folders(),
			'folder'         => $folder,
			'm'              => $message,
			'body'           => $body,
			'blocked_images' => $this->mail_sanitizer_lib->blocked_images(),
			'show_images'    => $show_images,
			'can_send'       => $this->can('mail.send'),
			'trash'          => $account->trash_folder,
		));
	}

	/**
	 * Stream one attachment to the browser.
	 *
	 * Always as a download, always as an opaque byte stream, never with the
	 * type the sender claimed: a mail attachment is a stranger's file, and
	 * letting the browser render it inline on this origin would put its
	 * content in the same place as the admin session.
	 */
	public function attachment($id, $uid, $part)
	{
		$this->require_perm('mail.access');

		if ( ! ($account = $this->connect($id)))
		{
			return;
		}

		$folder = $this->requested_folder();

		if ( ! $this->imap_lib->select($folder))
		{
			show_404();
		}

		$file = $this->imap_lib->attachment((int) $uid, $part);

		if ($file === FALSE)
		{
			show_404();
		}

		$name = $this->safe_filename($file['filename']);

		$this->log_access('download', $account->id, $folder.' #'.(int) $uid.' - '.$name);

		$this->output
			->set_status_header(200)
			->set_content_type('application/octet-stream')
			->set_header('Content-Disposition: attachment; filename="'.$name.'"; filename*=UTF-8\'\''.rawurlencode($file['filename']))
			->set_header('Content-Length: '.strlen($file['data']))
			->set_header('X-Content-Type-Options: nosniff')
			->set_header('Content-Security-Policy: default-src \'none\'')
			->set_header('Cache-Control: private, no-store')
			->set_output($file['data']);
	}

	/**
	 * Bulk operations from the list screen.
	 *
	 * POST only, and every one of these changes the mailbox.
	 */
	public function action($id)
	{
		$this->require_perm('mail.access');

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		if ( ! ($account = $this->connect($id)))
		{
			return;
		}

		$folder = (string) $this->input->post('folder', TRUE);
		$op     = (string) $this->input->post('op', TRUE);
		$uids   = array_filter(array_map('intval', (array) $this->input->post('uids')));
		$back   = $this->mail_url($id, $folder, array('page' => (int) $this->input->post('page') ?: 1));

		if (empty($uids))
		{
			$this->session->set_flashdata('error', 'Nothing was selected.');
			redirect($back);
		}

		if ( ! $this->imap_lib->select($folder))
		{
			$this->session->set_flashdata('error', $this->imap_lib->error());
			redirect($this->mail_url($id, 'INBOX'));
		}

		$count = count($uids);

		switch ($op)
		{
			case 'read':
				$this->imap_lib->flag($uids, '\\Seen', TRUE);
				$this->session->set_flashdata('success', $count.' marked as read.');
				break;

			case 'unread':
				$this->imap_lib->flag($uids, '\\Seen', FALSE);
				$this->session->set_flashdata('success', $count.' marked as unread.');
				break;

			case 'star':
				$this->imap_lib->flag($uids, '\\Flagged', TRUE);
				$this->session->set_flashdata('success', $count.' starred.');
				break;

			case 'unstar':
				$this->imap_lib->flag($uids, '\\Flagged', FALSE);
				$this->session->set_flashdata('success', $count.' unstarred.');
				break;

			case 'move':
				$target = (string) $this->input->post('target', TRUE);

				if ( ! $this->imap_lib->folder_exists($target))
				{
					$this->session->set_flashdata('error', 'That folder does not exist.');
					break;
				}

				if ($this->imap_lib->move($uids, $target))
				{
					$this->log_access('move', $account->id, $count.' from '.$folder.' to '.$target);
					$this->session->set_flashdata('success', $count.' moved to '.$target.'.');
				}
				else
				{
					$this->session->set_flashdata('error', $this->imap_lib->error());
				}
				break;

			case 'delete':
				$erasing = (strcasecmp($folder, (string) $account->trash_folder) === 0);

				if ($this->imap_lib->delete_messages($uids, (string) $account->trash_folder))
				{
					$this->log_access('delete', $account->id, $count.' from '.$folder.($erasing ? ' (erased)' : ' (to trash)'));
					$this->session->set_flashdata('success', $erasing
						? $count.' permanently deleted.'
						: $count.' moved to '.$account->trash_folder.'.');
				}
				else
				{
					$this->session->set_flashdata('error', $this->imap_lib->error());
				}
				break;

			default:
				$this->session->set_flashdata('error', 'Unknown action.');
		}

		redirect($back);
	}

	/* ================================================================ */
	/*  Compose                                                         */
	/* ================================================================ */

	/**
	 * The compose form.
	 *
	 * `?reply=UID&mode=reply|replyall|forward` pre-fills from an existing
	 * message, which means opening the source mailbox to read it.
	 */
	public function compose($id)
	{
		$this->require_perm('mail.send');

		$account = $this->mail_account_model->with_domain($id);

		if ( ! $account)
		{
			show_404();
		}

		$draft = array(
			'to' => '', 'cc' => '', 'bcc' => '', 'subject' => '', 'body' => '',
			'in_reply_to' => '', 'references' => '',
		);

		$quote   = '';
		$reply   = (int) $this->input->get('reply');
		$mode    = (string) $this->input->get('mode');
		$folder  = $this->input->get('f') ? (string) $this->input->get('f', TRUE) : 'INBOX';

		if ($reply > 0 && in_array($mode, array('reply', 'replyall', 'forward'), TRUE))
		{
			if ( ! $this->connect($id))
			{
				return;
			}

			if ($this->imap_lib->select($folder))
			{
				// FALSE: opening the reply form must not mark the original read
				// on a message the admin never actually opened.
				$source = $this->imap_lib->message($reply, FALSE);

				if ($source !== FALSE)
				{
					$draft = $this->prefill($account, $source, $mode);
					$quote = $this->quote_body($source);
				}
			}

			$this->imap_lib->close();
		}

		$this->render('admin/mail_compose', array(
			'page_title'  => 'Compose',
			'active_menu' => 'mail',
			'account'     => $account,
			'd'           => $draft,
			'quote'       => $quote,
			'folder'      => $folder,
			'max_bytes'   => self::MAX_ATTACH_BYTES,
		));
	}

	/** Send what the compose form posted, and file a copy in Sent. */
	public function send($id)
	{
		$this->require_perm('mail.send');

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$account = $this->mail_account_model->with_domain($id);

		if ( ! $account)
		{
			show_404();
		}

		if ($account->status !== 'active')
		{
			$this->session->set_flashdata('error', 'That mailbox is suspended.');
			redirect('admin/mail');
		}

		$this->load->library('mailer_lib');

		$password = $this->mail_account_model->password_of($account);

		$msg = array(
			'to'          => (string) $this->input->post('to', TRUE),
			'cc'          => (string) $this->input->post('cc', TRUE),
			'bcc'         => (string) $this->input->post('bcc', TRUE),
			'subject'     => (string) $this->input->post('subject', TRUE),
			'body'        => (string) $this->input->post('body', FALSE),
			'in_reply_to' => (string) $this->input->post('in_reply_to', TRUE),
			'references'  => (string) $this->input->post('references', TRUE),
		);

		// The body is admin-authored HTML going out to a third party, but it
		// comes back through the panel in the Sent folder, so it is cleaned on
		// the way out rather than trusted because of who typed it.
		$this->load->library('mail_sanitizer_lib');
		$msg['body'] = $this->mail_sanitizer_lib->clean($msg['body'], TRUE);

		if (trim(strip_tags($msg['body'])) === '' && $msg['subject'] === '')
		{
			$this->session->set_flashdata('error', 'An empty message with no subject was not sent.');
			redirect('admin/mail/compose/'.$id);
		}

		$bad = $this->mailer_lib->rejected_addresses($msg['to'].','.$msg['cc'].','.$msg['bcc']);

		if ( ! empty($bad))
		{
			$this->session->set_flashdata('error', 'Not a valid address: '.implode(', ', array_slice($bad, 0, 5)));
			redirect('admin/mail/compose/'.$id);
		}

		$attachments = $this->collect_attachments();

		if ($attachments === FALSE)
		{
			redirect('admin/mail/compose/'.$id);
		}

		$msg['attachments'] = $attachments;

		if ($account->signature)
		{
			$msg['body'] .= '<br><br>--<br>'.nl2br(html_escape($account->signature));
		}

		if ( ! $this->mailer_lib->send($account, $password, $msg))
		{
			$this->session->set_flashdata('error', $this->mailer_lib->error());
			redirect('admin/mail/compose/'.$id);
		}

		$this->log_access('send', $account->id, 'to '.short_txt($msg['to'], 40, 0).' - '.short_txt($msg['subject'], 40, 0));
		$this->log_action('Sent mail', 'mail', $account->id, $account->email.' to '.short_txt($msg['to'], 40, 0));

		// Filing the copy is a second connection to a second server and can
		// fail on its own. The mail has already gone, so this reports rather
		// than rolls back - there is nothing to roll back to.
		$filed = FALSE;

		if ($this->mailer_lib->raw() !== '' && $this->connect($id, FALSE))
		{
			$filed = $this->imap_lib->append($account->sent_folder, $this->mailer_lib->raw());
		}

		$this->session->set_flashdata('success', $filed
			? 'Message sent, and copied to '.$account->sent_folder.'.'
			: 'Message sent. A copy could not be filed in '.$account->sent_folder.' - check that folder name on the domain.');

		redirect($this->mail_url($id, $account->sent_folder));
	}

	/* ================================================================ */
	/*  Internals                                                       */
	/* ================================================================ */

	/**
	 * Open the account's mailbox for this request.
	 *
	 * Every failure path here ends in a redirect, so callers check the return
	 * and `return` on FALSE rather than trying to carry on.
	 *
	 * @param  bool $flash FALSE to fail quietly, for the second connection in
	 *                     send() where a message has already left
	 * @return object|false the account row
	 */
	protected function connect($id, $flash = TRUE)
	{
		if ($this->account && (int) $this->account->id === (int) $id)
		{
			return $this->account;
		}

		$account = $this->mail_account_model->with_domain($id);

		if ( ! $account)
		{
			show_404();
		}

		$this->load->library('imap_lib');

		if ($account->status !== 'active' || $account->domain_status !== 'active')
		{
			if ($flash)
			{
				$this->session->set_flashdata('error', 'That mailbox is suspended.');
				redirect('admin/mail');
			}

			return FALSE;
		}

		$password = $this->mail_account_model->password_of($account);

		if ( ! $this->imap_lib->open($account, $password))
		{
			$this->mail_account_model->mark_error($account->id, $this->imap_lib->error());

			if ($flash)
			{
				$this->session->set_flashdata('error', $account->email.': '.$this->imap_lib->error());
				redirect('admin/mail');
			}

			return FALSE;
		}

		return $this->account = $account;
	}

	/**
	 * The folder asked for, or INBOX.
	 *
	 * Validated against what the server actually lists, so the parameter cannot
	 * be used to probe for folders or to push an arbitrary string into an IMAP
	 * command.
	 */
	protected function requested_folder()
	{
		$folder = trim((string) $this->input->get('f', TRUE));

		if ($folder === '' || ! $this->imap_lib->folder_exists($folder))
		{
			return 'INBOX';
		}

		return $folder;
	}

	/** `admin/mail/open/7?f=INBOX.Sent&page=2` */
	protected function mail_url($id, $folder, $extra = array())
	{
		$query = array_merge(array('f' => $folder), $extra);

		return 'admin/mail/open/'.(int) $id.'?'.http_build_query(array_filter($query, 'strlen'));
	}

	protected function attachment_url($id, $folder, $uid, $part)
	{
		return base_url('admin/mail/attachment/'.(int) $id.'/'.(int) $uid.'/'.rawurlencode($part)
			.'?'.http_build_query(array('f' => $folder)));
	}

	/** Pre-fill the compose fields from the message being answered. */
	protected function prefill($account, $source, $mode)
	{
		$subject = (string) $source['subject'];

		if ($mode === 'forward')
		{
			$to = '';
			$cc = '';
			$subject = preg_match('/^fwd:/i', $subject) ? $subject : 'Fwd: '.$subject;
		}
		else
		{
			// Reply-To wins over From when the sender asked for it.
			$to = $source['reply_to'] !== '' ? $source['reply_to'] : $source['from'];
			$cc = '';
			$subject = preg_match('/^re:/i', $subject) ? $subject : 'Re: '.$subject;

			if ($mode === 'replyall')
			{
				$this->load->library('mailer_lib');

				// Everyone the message reached, minus ourselves - replying all
				// should not mail the mailbox doing the replying.
				$others = array_merge(
					$this->mailer_lib->addresses($source['to']),
					$this->mailer_lib->addresses($source['cc'])
				);

				$mine = array_map('strtolower', array_merge(
					array($account->email),
					$this->mailer_lib->addresses($to)
				));

				$cc = implode(', ', array_filter($others, function ($a) use ($mine) {
					return ! in_array(strtolower($a), $mine, TRUE);
				}));
			}
		}

		return array(
			'to'          => $to,
			'cc'          => $cc,
			'bcc'         => '',
			'subject'     => $subject,
			'body'        => '',
			'in_reply_to' => $source['message_id'],
			'references'  => $source['references'],
		);
	}

	/** The quoted original, cleaned, for the bottom of a reply. */
	protected function quote_body($source)
	{
		$this->load->library('mail_sanitizer_lib');

		$inner = $source['html'] !== ''
			? $this->mail_sanitizer_lib->clean($source['html'], FALSE)
			: $this->mail_sanitizer_lib->text_to_html($source['text']);

		$header = 'On '.fmt_date(date('Y-m-d H:i:s', $source['date'])).', '
			.html_escape($source['from']).' wrote:';

		return '<blockquote class="mail-quoted"><p class="small text-muted">'.$header.'</p>'.$inner.'</blockquote>';
	}

	/**
	 * Read the posted attachments into memory.
	 *
	 * @return array|false FALSE after setting a flash message
	 */
	protected function collect_attachments()
	{
		$out   = array();
		$total = 0;

		if (empty($_FILES['attachments']['name']) || ! is_array($_FILES['attachments']['name']))
		{
			return $out;
		}

		$files = $_FILES['attachments'];

		foreach ($files['name'] as $i => $name)
		{
			if ($files['error'][$i] === UPLOAD_ERR_NO_FILE || $name === '')
			{
				continue;
			}

			if ($files['error'][$i] !== UPLOAD_ERR_OK)
			{
				$this->session->set_flashdata('error', 'Upload failed for "'.html_escape($name).'".');
				return FALSE;
			}

			$total += (int) $files['size'][$i];

			if ($total > self::MAX_ATTACH_BYTES)
			{
				$this->session->set_flashdata('error',
					'Attachments come to more than '.round(self::MAX_ATTACH_BYTES / 1048576).' MB. Send the large ones as a link.');
				return FALSE;
			}

			// is_uploaded_file, not just the error code: without it a crafted
			// $_FILES entry could name any path on the server and mail it out.
			if ( ! is_uploaded_file($files['tmp_name'][$i]))
			{
				$this->session->set_flashdata('error', 'That upload was rejected.');
				return FALSE;
			}

			$out[] = array(
				'name'    => $this->safe_filename($name),
				// The browser's Content-Type is a hint from the client; the
				// recipient's client sniffs anyway, and claiming a type we did
				// not verify is worse than claiming none.
				'mime'    => 'application/octet-stream',
				'content' => (string) file_get_contents($files['tmp_name'][$i]),
			);
		}

		return $out;
	}

	/**
	 * A filename safe to put in a header and on a disk.
	 *
	 * Path separators and control characters go, because both a
	 * Content-Disposition header and a download directory will believe them.
	 */
	protected function safe_filename($name)
	{
		$name = basename(str_replace('\\', '/', (string) $name));
		$name = preg_replace('/[\x00-\x1F\x7F"\r\n]/', '', $name);
		$name = trim($name, '. ');

		if ($name === '')
		{
			$name = 'attachment';
		}

		return mb_substr($name, 0, 120);
	}

	/** Mailbox name normalisation: the part before the @, lowercased. */
	protected function normalise_local_part($raw)
	{
		$local = strtolower(trim((string) $raw));

		if (($at = strpos($local, '@')) !== FALSE)
		{
			$local = substr($local, 0, $at);
		}

		return preg_match('/^[a-z0-9](?:[a-z0-9._%+\-]{0,118}[a-z0-9])?$/', $local) ? $local : '';
	}

	protected function log_access($action, $account_id, $detail = NULL)
	{
		$this->mail_access_log_model->insert(array(
			'admin_id'   => $this->admin->id,
			'account_id' => $account_id ? (int) $account_id : NULL,
			'action'     => $action,
			'detail'     => $detail !== NULL ? mb_substr($detail, 0, 255) : NULL,
			'ip_address' => $this->input->ip_address(),
		));
	}

	/** Crypto_lib, loaded on demand - only the credential form needs it. */
	protected function crypto_lib_ready()
	{
		$this->load->library('crypto_lib');

		return $this->crypto_lib;
	}

	protected function back_to($mode, $row)
	{
		return $mode === 'edit' ? 'admin/mail/edit/'.$row->id : 'admin/mail/create';
	}

	protected function blank()
	{
		return (object) array(
			'id' => NULL, 'domain_id' => NULL, 'local_part' => '', 'email' => '',
			'display_name' => '', 'signature' => '', 'status' => 'active',
			'last_checked_at' => NULL, 'last_error' => NULL,
		);
	}
}
