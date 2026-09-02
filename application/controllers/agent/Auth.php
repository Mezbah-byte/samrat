<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Agent sign-in. Deliberately does NOT extend Agent_Controller, which would
 * redirect back here in a loop.
 *
 * There is no register() here and there never will be: agents are created by
 * an admin or promoted through an approved application.
 */
class Auth extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('auth_lib');
	}

	public function index()
	{
		redirect('agent/login');
	}

	public function login()
	{
		if ($this->session->userdata('agent_id'))
		{
			redirect('agent/dashboard');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('identity', 'Username or Email', 'required|trim');
			$this->form_validation->set_rules('password', 'Password', 'required');

			if ($this->form_validation->run())
			{
				$result = $this->auth_lib->agent_login(
					$this->input->post('identity', TRUE),
					$this->input->post('password')
				);

				if ($result['ok'])
				{
					$this->load->model('agent_log_model');
					$this->agent_log_model->insert(array(
						'agent_id'   => $result['agent']->id,
						'action'     => 'Signed in',
						'module'     => 'auth',
						'ip_address' => $this->input->ip_address(),
					));

					redirect('agent/dashboard');
				}

				$this->session->set_flashdata('error', $result['message']);
				redirect('agent/login');
			}
		}

		$this->render('agent/auth/login', array(
			'page_title' => 'Agent Login',
		), 'layouts/auth');
	}

	public function logout()
	{
		$this->auth_lib->agent_logout();
		$this->session->set_flashdata('success', 'Signed out of the agent panel.');
		redirect('agent/login');
	}
}
