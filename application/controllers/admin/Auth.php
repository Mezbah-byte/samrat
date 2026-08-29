<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin sign-in. Deliberately does NOT extend Admin_Controller, which would
 * redirect back here in a loop.
 */
class Auth extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('auth_lib');
	}

	public function index()
	{
		redirect('admin/login');
	}

	public function login()
	{
		if ($this->session->userdata('admin_id'))
		{
			redirect('admin/dashboard');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('identity', 'Username or Email', 'required|trim');
			$this->form_validation->set_rules('password', 'Password', 'required');

			if ($this->form_validation->run())
			{
				$result = $this->auth_lib->admin_login(
					$this->input->post('identity', TRUE),
					$this->input->post('password')
				);

				if ($result['ok'])
				{
					$this->load->model('admin_log_model');
					$this->admin_log_model->insert(array(
						'admin_id'   => $result['admin']->id,
						'action'     => 'Signed in',
						'module'     => 'auth',
						'ip_address' => $this->input->ip_address(),
					));

					redirect('admin/dashboard');
				}

				$this->session->set_flashdata('error', $result['message']);
				redirect('admin/login');
			}
		}

		$this->render('admin/auth/login', array(
			'page_title' => 'Admin Login',
		), 'layouts/auth');
	}

	public function logout()
	{
		$this->auth_lib->admin_logout();
		$this->session->set_flashdata('success', 'Signed out of the admin panel.');
		redirect('admin/login');
	}
}
