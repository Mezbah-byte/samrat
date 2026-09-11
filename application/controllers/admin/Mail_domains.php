<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The mail domains and the servers behind them.
 *
 * Separated from the mailbox screen, and gated behind its own capability, for
 * one reason: whoever can edit `imap_host` can point every mailbox on the
 * domain at a machine they control and collect the passwords the panel sends
 * it. That is a different level of trust from adding a mailbox, so the `admin`
 * preset does not carry it.
 */
class Mail_domains extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('mail_domain_model', 'mail_account_model'));
	}

	public function index()
	{
		$this->require_perm('mail.view');

		$this->render('admin/mail_domains', array(
			'page_title'  => 'Mail Domains',
			'active_menu' => 'mail',
			'rows'        => $this->mail_domain_model->all_with_counts(),
			'can_manage'  => $this->can('mail.domains'),
		));
	}

	public function create()
	{
		$this->form($this->blank(), 'create');
	}

	public function edit($id)
	{
		$row = $this->mail_domain_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$this->form($row, 'edit');
	}

	protected function form($row, $mode)
	{
		$this->require_perm('mail.domains');

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('domain', 'Domain', 'required|trim|max_length[190]');
			$this->form_validation->set_rules('imap_host', 'IMAP host', 'required|trim|max_length[190]');
			$this->form_validation->set_rules('imap_port', 'IMAP port', 'required|integer|greater_than[0]|less_than[65536]');
			$this->form_validation->set_rules('imap_encryption', 'IMAP encryption', 'required|in_list[ssl,tls,none]');
			$this->form_validation->set_rules('smtp_host', 'SMTP host', 'required|trim|max_length[190]');
			$this->form_validation->set_rules('smtp_port', 'SMTP port', 'required|integer|greater_than[0]|less_than[65536]');
			$this->form_validation->set_rules('smtp_encryption', 'SMTP encryption', 'required|in_list[ssl,tls,none]');
			$this->form_validation->set_rules('sent_folder', 'Sent folder', 'required|trim|max_length[120]');
			$this->form_validation->set_rules('trash_folder', 'Trash folder', 'required|trim|max_length[120]');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,inactive]');
			$this->form_validation->set_rules('note', 'Note', 'trim|max_length[255]');

			if ($this->form_validation->run())
			{
				$domain = $this->normalise_domain($this->input->post('domain', TRUE));

				if ($domain === '')
				{
					$this->session->set_flashdata('error', 'That is not a usable domain name.');
					redirect($this->back_to($mode, $row));
				}

				if ($this->mail_domain_model->domain_taken($domain, $row->id))
				{
					$this->session->set_flashdata('error', $domain.' is already registered.');
					redirect($this->back_to($mode, $row));
				}

				$data = array(
					'domain'             => $domain,
					'imap_host'          => $this->input->post('imap_host', TRUE),
					'imap_port'          => (int) $this->input->post('imap_port'),
					'imap_encryption'    => $this->input->post('imap_encryption', TRUE),
					// Off is a deliberate downgrade, so it has to be asked for
					// rather than defaulted into.
					'imap_validate_cert' => $this->input->post('imap_validate_cert') ? 1 : 0,
					'smtp_host'          => $this->input->post('smtp_host', TRUE),
					'smtp_port'          => (int) $this->input->post('smtp_port'),
					'smtp_encryption'    => $this->input->post('smtp_encryption', TRUE),
					'sent_folder'        => $this->input->post('sent_folder', TRUE),
					'trash_folder'       => $this->input->post('trash_folder', TRUE),
					'status'             => $this->input->post('status', TRUE),
					'note'               => $this->input->post('note', TRUE) ?: NULL,
				);

				if ($mode === 'edit')
				{
					$this->mail_domain_model->update($row->id, $data);
					$this->log_action('Updated mail domain', 'mail', $row->id, $domain);
					$this->session->set_flashdata('success', 'Domain updated.');
				}
				else
				{
					$new_id = $this->mail_domain_model->insert($data);
					$this->log_action('Created mail domain', 'mail', $new_id, $domain);
					$this->session->set_flashdata('success', 'Domain added. Now add the mailboxes that live on it.');
				}

				redirect('admin/mail-domains');
			}
		}

		$this->render('admin/mail_domain_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit Mail Domain' : 'New Mail Domain',
			'active_menu' => 'mail',
			'm'           => $row,
			'mode'        => $mode,
		));
	}

	public function delete($id)
	{
		$this->require_perm('mail.domains');

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->mail_domain_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		// The FK would cascade, but silently dropping a dozen mailboxes because
		// somebody tidied up a domain is not a thing to do without a warning.
		if ($this->mail_domain_model->account_count($id) > 0)
		{
			$this->session->set_flashdata('error',
				'Remove the mailboxes on '.$row->domain.' first - deleting the domain would take them with it.');
			redirect('admin/mail-domains');
		}

		$this->mail_domain_model->delete($id);
		$this->log_action('Deleted mail domain', 'mail', $id, $row->domain);

		$this->session->set_flashdata('success', 'Domain deleted.');
		redirect('admin/mail-domains');
	}

	/**
	 * Reduce whatever was typed to a bare hostname.
	 *
	 * Admins paste `https://mail.example.com/`, `@example.com` and
	 * `Example.Com ` interchangeably; all three mean the same domain, and the
	 * unique index should see one string for them.
	 *
	 * @return string '' when nothing domain-shaped is left
	 */
	protected function normalise_domain($raw)
	{
		$d = strtolower(trim((string) $raw));
		$d = preg_replace('~^[a-z]+://~', '', $d);
		$d = ltrim($d, '@');
		$parts = explode('/', $d);
		$d = trim($parts[0]);

		return preg_match('/^(?=.{1,190}$)([a-z0-9]([a-z0-9\-]*[a-z0-9])?\.)+[a-z]{2,}$/', $d) ? $d : '';
	}

	protected function back_to($mode, $row)
	{
		return $mode === 'edit'
			? 'admin/mail-domains/edit/'.$row->id
			: 'admin/mail-domains/create';
	}

	/**
	 * A blank row pre-filled with the cPanel defaults, which is where these
	 * mailboxes live. Wrong for another host, but right for the common case
	 * and editable either way.
	 */
	protected function blank()
	{
		return (object) array(
			'id' => NULL, 'domain' => '',
			'imap_host' => '', 'imap_port' => 993, 'imap_encryption' => 'ssl', 'imap_validate_cert' => 1,
			'smtp_host' => '', 'smtp_port' => 465, 'smtp_encryption' => 'ssl',
			'sent_folder' => 'INBOX.Sent', 'trash_folder' => 'INBOX.Trash',
			'status' => 'active', 'note' => '',
		);
	}
}
