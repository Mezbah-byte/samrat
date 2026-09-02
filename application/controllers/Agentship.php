<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The user side of becoming an agent: check the team threshold, submit one
 * application, then wait for an admin.
 *
 * Nothing here creates an agent. Approval happens in
 * admin/Agent_applications.php, where an admin sets the password by hand.
 */
class Agentship extends User_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('agent_application_model');

		if ($this->setting_model->get('agent_panel_enabled', '1') !== '1')
		{
			$this->session->set_flashdata('error', 'Agentship applications are closed.');
			redirect('referral');
		}
	}

	public function index()
	{
		$this->render('user/agentship', array(
			'page_title'   => 'Agentship',
			'active_menu'  => 'agentship',
			'application'  => $this->agent_application_model->latest_for_user($this->user->id),
			'open'         => $this->agent_application_model->open_for_user($this->user->id),
			'team_active'  => $this->user_model->active_downline_count($this->user->id),
			'threshold'    => (int) setting('agent_min_team_size', 50),
		));
	}

	public function apply()
	{
		$threshold   = (int) setting('agent_min_team_size', 50);
		$team_active = $this->user_model->active_downline_count($this->user->id);

		// The hidden button on the referral page is presentation only. This is
		// the gate, and it is re-checked on the POST as well as the GET.
		if ($team_active < $threshold)
		{
			$this->session->set_flashdata('error',
				'You need '.$threshold.' active team members to apply. You currently have '.$team_active.'.');
			redirect('agentship');
		}

		if ($open = $this->agent_application_model->open_for_user($this->user->id))
		{
			$this->session->set_flashdata('error', $open->status === 'approved'
				? 'You are already an agent.'
				: 'You already have an application awaiting review.');
			redirect('agentship');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('full_name', 'Full Name', 'required|trim|min_length[3]|max_length[120]');
			$this->form_validation->set_rules('username', 'Agent Username', 'required|trim|alpha_dash|min_length[3]|max_length[60]');
			$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]');
			$this->form_validation->set_rules('country', 'Country', 'required|trim|max_length[80]');
			$this->form_validation->set_rules('nid_number', 'NID Number', 'required|trim|min_length[5]|max_length[40]');
			$this->form_validation->set_rules('agree', 'Terms', 'required');

			if ($this->form_validation->run())
			{
				$username = $this->input->post('username', TRUE);
				$email    = $this->input->post('email', TRUE);

				// Checked against agents rather than users: the agent username
				// is a separate login, and a clash would block the admin at
				// approval time instead of here.
				$this->load->model('agent_model');

				foreach (array('username' => $username, 'email' => $email) as $field => $value)
				{
					if ($this->agent_model->find_by(array($field => $value)))
					{
						$this->session->set_flashdata('error', 'That '.$field.' is already taken by an agent. Choose another.');
						redirect('agentship/apply');
					}
				}

				// No set_rules for file inputs anywhere in this app; presence
				// is checked directly and errors come back as flashdata.
				foreach (array('nid_front' => 'NID front', 'nid_back' => 'NID back') as $field => $label)
				{
					if (empty($_FILES[$field]['name']))
					{
						$this->session->set_flashdata('error', $label.' image is required.');
						redirect('agentship/apply');
					}
				}

				$this->load->library('uploader_lib');
				$files = array();

				foreach (array('nid_front' => 'NID front', 'nid_back' => 'NID back') as $field => $label)
				{
					$file = $this->uploader_lib->image($field, 'nid');

					if ($file === FALSE)
					{
						// Roll back anything already stored, so a half-failed
						// submission leaves no orphaned scan behind.
						foreach ($files as $stored)
						{
							$this->uploader_lib->remove('nid', $stored);
						}

						$this->session->set_flashdata('error', $label.': '.$this->uploader_lib->error());
						redirect('agentship/apply');
					}

					$files[$field] = $file;
				}

				$this->agent_application_model->insert(array(
					'user_id'           => $this->user->id,
					'full_name'         => $this->input->post('full_name', TRUE),
					'username'          => $username,
					'email'             => $email,
					'country'           => $this->input->post('country', TRUE),
					'nid_number'        => $this->input->post('nid_number', TRUE),
					'nid_front'         => $files['nid_front'],
					'nid_back'          => $files['nid_back'],
					'team_active_count' => $team_active,
					'status'            => 'pending',
				));

				$this->session->set_flashdata('success',
					'Your agentship application has been submitted. An admin will review it shortly.');
				redirect('agentship');
			}
		}

		$this->render('user/agentship_apply', array(
			'page_title'  => 'Apply for Agentship',
			'active_menu' => 'agentship',
			'team_active' => $team_active,
			'threshold'   => $threshold,
		));
	}
}
