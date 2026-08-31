<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Public_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('auth_lib');
		$this->load->model('user_model');
	}

	public function index()
	{
		redirect('login');
	}

	/* ----------------------------------------------------------------- */

	public function login()
	{
		if ($this->session->userdata('user_id'))
		{
			redirect('dashboard');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('identity', 'Username or Email', 'required|trim');
			$this->form_validation->set_rules('password', 'Password', 'required');

			if ($this->form_validation->run())
			{
				$result = $this->auth_lib->login(
					$this->input->post('identity', TRUE),
					$this->input->post('password'),
					(bool) $this->input->post('remember')
				);

				if ($result['ok'])
				{
					$this->session->set_flashdata('success', $result['message']);
					redirect('dashboard');
				}

				$this->session->set_flashdata('error', $result['message']);
				redirect('login');
			}
		}

		$this->render('auth/login', array(
			'page_title' => 'Login',
		), 'layouts/auth');
	}

	/* ----------------------------------------------------------------- */

	public function register($ref = NULL)
	{
		if ($this->session->userdata('user_id'))
		{
			redirect('dashboard');
		}

		if ($this->setting_model->get('registration_open', '1') !== '1')
		{
			$this->session->set_flashdata('error', 'Registration is currently closed.');
			redirect('login');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('full_name', 'Full Name', 'required|trim|min_length[3]|max_length[120]');
			$this->form_validation->set_rules('username', 'Username', 'required|trim|min_length[3]|max_length[60]|alpha_dash|is_unique[users.username]');
			$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]|is_unique[users.email]');
			$this->form_validation->set_rules('mobile', 'Mobile Number', 'required|trim|min_length[6]|max_length[30]');
			$this->form_validation->set_rules('country', 'Country', 'required|trim|max_length[80]');
			$this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|max_length[100]');
			$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');
			// Auth_lib is what checks the ID resolves to an active account, and
			// waives it for the very first user on an empty install.
			$this->form_validation->set_rules('referral_code', 'Referral ID',
				$this->user_model->count_by() > 0 ? 'required|trim|max_length[16]' : 'trim|max_length[16]');
			$this->form_validation->set_rules('agree', 'Terms', 'required');

			if ($this->form_validation->run())
			{
				$result = $this->auth_lib->register(array(
					'full_name'     => $this->input->post('full_name', TRUE),
					'username'      => $this->input->post('username', TRUE),
					'email'         => $this->input->post('email', TRUE),
					'mobile'        => $this->input->post('mobile', TRUE),
					'country'       => $this->input->post('country', TRUE),
					'password'      => $this->input->post('password'),
					'referral_code' => $this->input->post('referral_code', TRUE),
				));

				if ($result['ok'])
				{
					$this->auth_lib->login($this->input->post('username', TRUE), $this->input->post('password'));
					$this->session->set_flashdata('success', 'Welcome aboard. Pick a package to start earning.');
					redirect('packages');
				}

				$this->session->set_flashdata('error', $result['message']);
			}
		}

		$referral_code = $this->input->post('referral_code')
			?: ($ref ?: $this->input->get('ref', TRUE));

		$this->render('auth/register', array(
			'page_title'    => 'Create Account',
			'wide'          => TRUE,
			'countries'     => country_list(),
			'referral_code' => $referral_code,
		), 'layouts/auth');
	}

	/* ----------------------------------------------------------------- */

	public function logout()
	{
		$this->auth_lib->logout();
		$this->session->set_flashdata('success', 'You have been logged out.');
		redirect('login');
	}

	/* ----------------------------------------------------------------- */

	public function forgot_password()
	{
		$issued_token = NULL;

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');

			if ($this->form_validation->run())
			{
				$email = $this->input->post('email', TRUE);
				$token = $this->auth_lib->create_reset_token($email);

				// No mail transport is configured out of the box. The link is
				// shown here so it can be delivered manually; wire up an SMTP
				// send in production and drop this display.
				if ($token)
				{
					$issued_token = $token;
				}

				$this->session->set_flashdata('info', 'If that email is registered, a reset link has been generated.');
			}
		}

		$this->render('auth/forgot_password', array(
			'page_title'   => 'Forgot Password',
			'issued_token' => $issued_token,
		), 'layouts/auth');
	}

	public function reset_password($token = NULL)
	{
		$row = $token ? $this->auth_lib->find_valid_reset($token) : NULL;

		if ( ! $row)
		{
			$this->session->set_flashdata('error', 'That reset link is invalid or has expired.');
			redirect('forgot-password');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('password', 'New Password', 'required|min_length[6]|max_length[100]');
			$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');

			if ($this->form_validation->run())
			{
				if ($this->auth_lib->consume_reset($token, $this->input->post('password')))
				{
					$this->session->set_flashdata('success', 'Password updated. You can log in now.');
					redirect('login');
				}
				$this->session->set_flashdata('error', 'Could not reset the password. Request a new link.');
				redirect('forgot-password');
			}
		}

		$this->render('auth/reset_password', array(
			'page_title' => 'Reset Password',
			'token'      => $token,
		), 'layouts/auth');
	}
}
