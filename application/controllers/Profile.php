<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends User_Controller {

	public function index()
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('full_name', 'Full Name', 'required|trim|min_length[3]|max_length[120]');
			$this->form_validation->set_rules('mobile', 'Mobile Number', 'required|trim|min_length[6]|max_length[30]');
			$this->form_validation->set_rules('country', 'Country', 'required|trim|max_length[80]');
			$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]'
				.'|callback_email_available['.$this->user->id.']');

			if ($this->form_validation->run())
			{
				$this->user_model->update($this->user->id, array(
					'full_name' => $this->input->post('full_name', TRUE),
					'email'     => $this->input->post('email', TRUE),
					'mobile'    => $this->input->post('mobile', TRUE),
					'country'   => $this->input->post('country', TRUE),
				));

				$this->session->set_flashdata('success', 'Profile updated.');
				redirect('profile');
			}
		}

		$this->load->model(array('investment_model', 'referral_model'));

		$this->render('user/profile', array(
			'page_title'     => 'Profile',
			'active_menu'    => 'profile',
			'countries'      => country_list(),
			'active_plans'   => $this->investment_model->active_for_user($this->user->id),
			'referral_count' => $this->user_model->referral_count($this->user->id),
			'referral_total' => $this->referral_model->earned_total($this->user->id),
		));
	}

	/** Form validation callback: email must be free, ignoring this user's own row. */
	public function email_available($email, $user_id)
	{
		$existing = $this->user_model->find_by(array('email' => $email));

		if ($existing && (int) $existing->id !== (int) $user_id)
		{
			$this->form_validation->set_message('email_available', 'That email is already registered.');
			return FALSE;
		}
		return TRUE;
	}

	public function password()
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('current_password', 'Current Password', 'required');
			$this->form_validation->set_rules('password', 'New Password', 'required|min_length[6]|max_length[100]');
			$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');

			if ($this->form_validation->run())
			{
				if ( ! password_verify($this->input->post('current_password'), $this->user->password))
				{
					$this->session->set_flashdata('error', 'Your current password is incorrect.');
					redirect('profile/password');
				}

				$this->user_model->update($this->user->id, array(
					'password' => password_hash($this->input->post('password'), PASSWORD_BCRYPT),
				));

				$this->session->set_flashdata('success', 'Password changed.');
				redirect('profile/password');
			}
		}

		$this->render('user/profile_password', array(
			'page_title'  => 'Change Password',
			'active_menu' => 'profile',
		));
	}

	public function avatar()
	{
		if ($this->input->method() !== 'post')
		{
			redirect('profile');
		}

		$this->load->library('uploader_lib');
		$filename = $this->uploader_lib->image('avatar', 'avatars');

		if ($filename === FALSE)
		{
			$this->session->set_flashdata('error', $this->uploader_lib->error());
			redirect('profile');
		}

		if ($this->user->avatar)
		{
			$this->uploader_lib->remove('avatars', $this->user->avatar);
		}

		$this->user_model->update($this->user->id, array('avatar' => $filename));
		$this->session->set_flashdata('success', 'Profile picture updated.');
		redirect('profile');
	}
}
