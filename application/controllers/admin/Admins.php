<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admins extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('admin_model');
	}

	public function index()
	{
		$this->require_role(array('super_admin'));

		$this->render('admin/admins', array(
			'page_title'  => 'Admin Users',
			'active_menu' => 'admins',
			'rows'        => $this->admin_model->all(),
		));
	}

	public function create()
	{
		$this->require_role(array('super_admin'));
		$this->form($this->blank(), 'create');
	}

	public function edit($id)
	{
		$this->require_role(array('super_admin'));

		$row = $this->admin_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$this->form($row, 'edit');
	}

	protected function form($row, $mode)
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('name', 'Name', 'required|trim|max_length[100]');
			$this->form_validation->set_rules('username', 'Username', 'required|trim|alpha_dash|max_length[60]');
			$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]');
			$this->form_validation->set_rules('role', 'Role', 'required|in_list[super_admin,admin,moderator]');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,blocked]');

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
				$username = $this->input->post('username', TRUE);
				$email    = $this->input->post('email', TRUE);

				foreach (array('username' => $username, 'email' => $email) as $field => $value)
				{
					$taken = $this->admin_model->find_by(array($field => $value));

					if ($taken && (int) $taken->id !== (int) $row->id)
					{
						$this->session->set_flashdata('error', 'That '.$field.' is already in use.');
						redirect($mode === 'edit' ? 'admin/admins/edit/'.$row->id : 'admin/admins/create');
					}
				}

				$data = array(
					'name'     => $this->input->post('name', TRUE),
					'username' => $username,
					'email'    => $email,
					'role'     => $this->input->post('role', TRUE),
					'status'   => $this->input->post('status', TRUE),
				);

				if ($password = $this->input->post('password'))
				{
					$data['password'] = password_hash($password, PASSWORD_BCRYPT);
				}

				if ($mode === 'edit')
				{
					// Never let the last active super admin lock everyone out.
					if ($row->role === 'super_admin' && $data['role'] !== 'super_admin' && $this->super_admin_count() <= 1)
					{
						$this->session->set_flashdata('error', 'This is the only super admin. Promote another account first.');
						redirect('admin/admins/edit/'.$row->id);
					}

					$this->admin_model->update($row->id, $data);
					$this->log_action('Updated admin', 'admins', $row->id, $username);
					$this->session->set_flashdata('success', 'Admin updated.');
				}
				else
				{
					$new_id = $this->admin_model->insert($data);
					$this->log_action('Created admin', 'admins', $new_id, $username);
					$this->session->set_flashdata('success', 'Admin created.');
				}

				redirect('admin/admins');
			}
		}

		$this->render('admin/admin_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit Admin' : 'New Admin',
			'active_menu' => 'admins',
			'a'           => $row,
			'mode'        => $mode,
		));
	}

	public function delete($id)
	{
		$this->require_role(array('super_admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->admin_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		if ((int) $row->id === (int) $this->admin->id)
		{
			$this->session->set_flashdata('error', 'You cannot delete your own account.');
			redirect('admin/admins');
		}

		if ($row->role === 'super_admin' && $this->super_admin_count() <= 1)
		{
			$this->session->set_flashdata('error', 'That is the only super admin account.');
			redirect('admin/admins');
		}

		$this->admin_model->delete($id);
		$this->log_action('Deleted admin', 'admins', $id, $row->username);

		$this->session->set_flashdata('success', 'Admin deleted.');
		redirect('admin/admins');
	}

	/** Any signed-in admin can manage their own name, email and password. */
	public function profile()
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('name', 'Name', 'required|trim|max_length[100]');
			$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]');
			$this->form_validation->set_rules('password', 'New Password', 'min_length[8]');

			if ($this->form_validation->run())
			{
				$email = $this->input->post('email', TRUE);
				$taken = $this->admin_model->find_by(array('email' => $email));

				if ($taken && (int) $taken->id !== (int) $this->admin->id)
				{
					$this->session->set_flashdata('error', 'That email belongs to another admin.');
					redirect('admin/admins/profile');
				}

				$data = array(
					'name'  => $this->input->post('name', TRUE),
					'email' => $email,
				);

				if ($password = $this->input->post('password'))
				{
					if ( ! password_verify($this->input->post('current_password'), $this->admin->password))
					{
						$this->session->set_flashdata('error', 'Your current password is incorrect.');
						redirect('admin/admins/profile');
					}
					$data['password'] = password_hash($password, PASSWORD_BCRYPT);
				}

				$this->admin_model->update($this->admin->id, $data);
				$this->session->set_flashdata('success', 'Profile updated.');
				redirect('admin/admins/profile');
			}
		}

		$this->render('admin/admin_profile', array(
			'page_title'  => 'My Profile',
			'active_menu' => 'admins',
		));
	}

	protected function super_admin_count()
	{
		return (int) $this->db->where('role', 'super_admin')->where('status', 'active')->count_all_results('admins');
	}

	protected function blank()
	{
		return (object) array(
			'id' => NULL, 'name' => '', 'username' => '', 'email' => '',
			'role' => 'admin', 'status' => 'active', 'avatar' => NULL,
			'last_login_at' => NULL, 'created_at' => NULL,
		);
	}
}
