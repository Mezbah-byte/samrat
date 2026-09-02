<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Agent accounts. Super-admin only, and the only way an agent is ever born:
 * there is no agent sign-up anywhere in the app.
 *
 * NID scans are PII, so they never go through upload_url() like the other
 * images do. uploads/nid/ is blocked at the webserver and every read goes
 * through nid() below, behind the admin session.
 */
class Agents extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('agent_model', 'agent_application_model', 'user_model'));
	}

	public function index()
	{
		$this->require_role(array('super_admin'));

		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->agent_model->paginate_admin($per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('admin/agents', array(
			'page_title'  => 'Agents',
			'active_menu' => 'agents',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->agent_model->stats(),
		));
	}

	/**
	 * @param int $application_id when set, the form opens prefilled from an
	 *                            approved-pending application. The admin still
	 *                            types the password by hand - that is the
	 *                            agreed handover.
	 */
	public function create($application_id = 0)
	{
		$this->require_role(array('super_admin'));

		$row         = $this->blank();
		$application = NULL;

		if ($application_id)
		{
			$application = $this->agent_application_model->find($application_id);

			if ( ! $application || $application->status !== 'pending')
			{
				$this->session->set_flashdata('error', 'That application is not awaiting review.');
				redirect('admin/agent-applications');
			}

			$row->name       = $application->full_name;
			$row->username   = $application->username;
			$row->email      = $application->email;
			$row->country    = $application->country;
			$row->nid_number = $application->nid_number;
			$row->user_id    = $application->user_id;
		}

		$this->form($row, 'create', $application);
	}

	public function edit($id)
	{
		$this->require_role(array('super_admin'));

		$row = $this->agent_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$this->form($row, 'edit');
	}

	protected function form($row, $mode, $application = NULL)
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('name', 'Name', 'required|trim|max_length[120]');
			$this->form_validation->set_rules('username', 'Username', 'required|trim|alpha_dash|min_length[3]|max_length[60]');
			$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]');
			$this->form_validation->set_rules('country', 'Country', 'trim|max_length[80]');
			$this->form_validation->set_rules('nid_number', 'NID Number', 'required|trim|max_length[40]');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,blocked]');
			$this->form_validation->set_rules('commission_deposit_percent', 'Deposit Commission', 'trim|numeric|less_than_equal_to[100]');
			$this->form_validation->set_rules('commission_profit_percent', 'Profit Commission', 'trim|numeric|less_than_equal_to[100]');

			if ($mode === 'create')
			{
				$this->form_validation->set_rules('password', 'Password', 'required|min_length[8]');
			}
			else
			{
				$this->form_validation->set_rules('password', 'Password', 'min_length[8]');
			}

			if ($this->form_validation->run())
			{
				$username       = $this->input->post('username', TRUE);
				$email          = $this->input->post('email', TRUE);
				$application_id = (int) $this->input->post('application_id');
				$back           = $mode === 'edit'
					? 'admin/agents/edit/'.$row->id
					: 'admin/agents/create'.($application_id ? '/'.$application_id : '');

				foreach (array('username' => $username, 'email' => $email) as $field => $value)
				{
					$taken = $this->agent_model->find_by(array($field => $value));

					if ($taken && (int) $taken->id !== (int) $row->id)
					{
						$this->session->set_flashdata('error', 'That '.$field.' is already used by another agent.');
						redirect($back);
					}
				}

				// The linked user is what gives the agent a team. Without one
				// the panel still works, but every team screen is empty.
				$linked_id = NULL;
				$linked_in = trim((string) $this->input->post('linked_username', TRUE));

				if ($linked_in !== '')
				{
					$linked = $this->user_model->by_login($linked_in);

					if ( ! $linked)
					{
						$this->session->set_flashdata('error', 'No user matches "'.$linked_in.'".');
						redirect($back);
					}
					if ($linked->status !== 'active')
					{
						$this->session->set_flashdata('error', 'That user account is '.$linked->status.'. Link an active account.');
						redirect($back);
					}

					$holder = $this->agent_model->by_user($linked->id);

					if ($holder && (int) $holder->id !== (int) $row->id)
					{
						$this->session->set_flashdata('error', 'That user is already linked to agent "'.$holder->username.'".');
						redirect($back);
					}

					$linked_id = $linked->id;
				}

				$data = array(
					'name'       => $this->input->post('name', TRUE),
					'username'   => $username,
					'email'      => $email,
					'country'    => $this->input->post('country', TRUE) ?: NULL,
					'nid_number' => $this->input->post('nid_number', TRUE),
					'status'     => $this->input->post('status', TRUE),
					'user_id'    => $linked_id,
					'commission_deposit_percent' => $this->numeric_or_null($this->input->post('commission_deposit_percent', TRUE)),
					'commission_profit_percent'  => $this->numeric_or_null($this->input->post('commission_profit_percent', TRUE)),
				);

				if ($password = $this->input->post('password'))
				{
					$data['password'] = password_hash($password, PASSWORD_BCRYPT);
				}

				foreach (array('nid_front', 'nid_back') as $field)
				{
					if (empty($_FILES[$field]['name']))
					{
						continue;
					}

					$this->load->library('uploader_lib');
					$file = $this->uploader_lib->image($field, 'nid');

					if ($file === FALSE)
					{
						$this->session->set_flashdata('error', 'NID image: '.$this->uploader_lib->error());
						redirect($back);
					}

					if ($mode === 'edit' && $row->{$field})
					{
						$this->uploader_lib->remove('nid', $row->{$field});
					}

					$data[$field] = $file;
				}

				if ($mode === 'edit')
				{
					$this->agent_model->update($row->id, $data);
					$this->log_action('Updated agent', 'agents', $row->id, $username);
					$this->session->set_flashdata('success', 'Agent updated.');
					redirect('admin/agents');
				}

				// Promoting from an application: carry its scans over as
				// physical copies, so deleting either record later cannot
				// pull the file out from under the other.
				if ($application_id)
				{
					$application = $this->agent_application_model->find($application_id);

					if ( ! $application || $application->status !== 'pending')
					{
						$this->session->set_flashdata('error', 'That application is not awaiting review.');
						redirect('admin/agent-applications');
					}

					foreach (array('nid_front', 'nid_back') as $field)
					{
						if (empty($data[$field]) && $application->{$field})
						{
							$data[$field] = $this->copy_nid($application->{$field});
						}
					}
				}

				if (empty($data['nid_front']) || empty($data['nid_back']))
				{
					$this->session->set_flashdata('error', 'Both sides of the NID are required.');
					redirect($back);
				}

				$data['created_by'] = $this->admin->id;

				$new_id = $this->agent_model->insert($data);
				$this->log_action('Created agent', 'agents', $new_id, $username);

				if ($application_id)
				{
					$this->agent_application_model->update($application_id, array(
						'status'      => 'approved',
						'agent_id'    => $new_id,
						'reviewed_by' => $this->admin->id,
						'reviewed_at' => date('Y-m-d H:i:s'),
						'admin_note'  => $this->input->post('admin_note', TRUE),
					));

					$this->log_action('Approved agentship application', 'agent_applications', $application_id, $username);

					$this->load->model('notification_model');
					$this->notification_model->push(
						$application->user_id,
						'Agentship approved',
						'Your agentship application has been approved. Your agent username is '
							.$username.'. Sign in at '.base_url('agent/login')
							.' - the admin will pass you the password separately.',
						'agentship'
					);
				}

				$this->session->set_flashdata('success', $application_id
					? 'Application approved and agent account created.'
					: 'Agent created.');
				redirect('admin/agents');
			}
		}

		$linked = ($row->user_id) ? $this->user_model->find($row->user_id) : NULL;

		$this->render('admin/agent_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit Agent' : 'New Agent',
			'active_menu' => 'agents',
			'a'           => $row,
			'mode'        => $mode,
			'application' => $application,
			'linked'      => $linked,
		));
	}

	public function delete($id)
	{
		$this->require_role(array('super_admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->agent_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$this->load->library('uploader_lib');

		foreach (array('nid_front', 'nid_back') as $field)
		{
			if ($row->{$field})
			{
				$this->uploader_lib->remove('nid', $row->{$field});
			}
		}

		$this->agent_model->delete($id);
		$this->log_action('Deleted agent', 'agents', $id, $row->username);

		$this->session->set_flashdata('success', 'Agent deleted. Their commission history went with them.');
		redirect('admin/agents');
	}

	/**
	 * Serves one NID scan from behind the admin session.
	 *
	 * $side picks between two fixed column names; it never reaches the
	 * filesystem. The stored filename is basename()d before it does.
	 */
	public function nid($id, $side = 'front')
	{
		$this->require_role(array('super_admin'));

		$row = $this->agent_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		stream_nid($row, $side);
	}

	/** Duplicates a stored scan under a fresh name, returns the new filename. */
	protected function copy_nid($filename)
	{
		$source = UPLOAD_PATH.'nid'.DIRECTORY_SEPARATOR.basename($filename);

		if ( ! is_file($source))
		{
			return NULL;
		}

		$ext    = strtolower(pathinfo($source, PATHINFO_EXTENSION));
		$target = date('Ymd').'_'.bin2hex(random_bytes(8)).'.'.$ext;

		return copy($source, UPLOAD_PATH.'nid'.DIRECTORY_SEPARATOR.$target) ? $target : NULL;
	}

	/** An empty percent box means "use the platform setting", not zero. */
	protected function numeric_or_null($value)
	{
		$value = trim((string) $value);
		return ($value === '') ? NULL : $value;
	}

	protected function blank()
	{
		return (object) array(
			'id' => NULL, 'user_id' => NULL, 'name' => '', 'username' => '', 'email' => '',
			'country' => '', 'nid_number' => '', 'nid_front' => NULL, 'nid_back' => NULL,
			'commission_deposit_percent' => NULL, 'commission_profit_percent' => NULL,
			'total_commission' => 0, 'status' => 'active',
			'last_login_at' => NULL, 'created_at' => NULL,
		);
	}
}
