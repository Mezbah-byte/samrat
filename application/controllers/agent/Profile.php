<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * An agent may change their own name, email and password. Username, NID and
 * commission rates are identity and terms - only an admin edits those.
 */
class Profile extends Agent_Controller {

	public function index()
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('name', 'Name', 'required|trim|max_length[120]');
			$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]');
			$this->form_validation->set_rules('password', 'New Password', 'min_length[8]');

			if ($this->form_validation->run())
			{
				$email = $this->input->post('email', TRUE);
				$taken = $this->agent_model->find_by(array('email' => $email));

				if ($taken && (int) $taken->id !== (int) $this->agent->id)
				{
					$this->session->set_flashdata('error', 'That email belongs to another agent.');
					redirect('agent/profile');
				}

				$data = array(
					'name'  => $this->input->post('name', TRUE),
					'email' => $email,
				);

				if ($password = $this->input->post('password'))
				{
					if ( ! password_verify($this->input->post('current_password'), $this->agent->password))
					{
						$this->session->set_flashdata('error', 'Your current password is incorrect.');
						redirect('agent/profile');
					}
					$data['password'] = password_hash($password, PASSWORD_BCRYPT);
				}

				$this->agent_model->update($this->agent->id, $data);
				$this->log_action('Updated profile', 'profile', $this->agent->id);

				$this->session->set_flashdata('success', 'Profile updated.');
				redirect('agent/profile');
			}
		}

		$linked = $this->agent->user_id ? $this->user_model->find($this->agent->user_id) : NULL;

		$this->render('agent/profile', array(
			'page_title'  => 'My Profile',
			'active_menu' => 'profile',
			'linked'      => $linked,
			'team_size'   => count($this->team_ids()),
		));
	}
}
