<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ends an impersonation session.
 *
 * Deliberately does NOT extend Admin_Controller: the request arrives from
 * inside the user or agent panel, whose layout renders the return button. The
 * gate is the imp_admin_id session key, which only Impersonate_lib sets.
 */
class Impersonate extends MY_Controller {

	public function index()
	{
		redirect('dashboard');
	}

	public function stop()
	{
		if ( ! $this->impersonate_lib->active())
		{
			redirect('dashboard');
		}

		$admin_id = (int) $this->session->userdata('imp_admin_id');
		$type     = $this->session->userdata('imp_type');
		$target   = (int) $this->session->userdata('imp_target_id');

		$stopped = $this->impersonate_lib->stop();

		$this->load->model('admin_log_model');
		$this->admin_log_model->insert(array(
			'admin_id'     => $admin_id,
			'action'       => 'Stopped impersonating '.$type,
			'module'       => 'impersonate',
			'reference_id' => $target,
			'ip_address'   => $this->input->ip_address(),
		));

		$this->session->set_flashdata('success', 'Impersonation ended.');
		redirect($this->impersonate_lib->return_url($stopped));
	}
}
