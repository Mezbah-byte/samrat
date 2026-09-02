<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Starts an impersonation session.
 *
 * Stopping lives in the root Impersonate controller instead, because by then
 * the request is coming from inside the user or agent panel.
 */
class Impersonate extends Admin_Controller {

	public function index()
	{
		redirect('admin/dashboard');
	}

	public function user($id = 0)
	{
		$this->guard();

		$result = $this->impersonate_lib->start_user($this->admin, (int) $id);

		if ( ! $result['ok'])
		{
			$this->session->set_flashdata('error', $result['message']);
			redirect('admin/users/view/'.(int) $id);
		}

		$this->log_action('Started impersonating user', 'impersonate', (int) $id);
		$this->session->set_flashdata('success', $result['message']);
		redirect('dashboard');
	}

	public function agent($id = 0)
	{
		$this->guard();

		$result = $this->impersonate_lib->start_agent($this->admin, (int) $id);

		if ( ! $result['ok'])
		{
			$this->session->set_flashdata('error', $result['message']);
			redirect('admin/agents');
		}

		$this->log_action('Started impersonating agent', 'impersonate', (int) $id);
		$this->session->set_flashdata('success', $result['message']);
		redirect('agent');
	}

	/**
	 * POST only, so the CSRF token is required and no link, image or prefetch
	 * can put an admin into somebody else's session.
	 */
	protected function guard()
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_404();
		}
	}
}
