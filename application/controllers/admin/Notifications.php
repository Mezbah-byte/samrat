<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('notification_model', 'user_model'));
	}

	public function index()
	{
		if ($this->input->method() === 'post')
		{
			$this->send();
		}

		$per_page = 25;
		$page     = max(1, (int) $this->input->get('page'));

		$rows = $this->db->select('n.*, u.username')
			->from('notifications n')->join('users u', 'u.id = n.user_id', 'left')
			->order_by('n.id', 'DESC')->limit($per_page, ($page - 1) * $per_page)->get()->result();

		$this->render('admin/notifications', array(
			'page_title'  => 'Notifications',
			'active_menu' => 'notifications',
			'rows'        => $rows,
			'total'       => (int) $this->db->count_all('notifications'),
			'per_page'    => $per_page,
			'page'        => $page,
		));
	}

	protected function send()
	{
		$this->form_validation->set_rules('title', 'Title', 'required|trim|max_length[180]');
		$this->form_validation->set_rules('message', 'Message', 'required|trim');
		$this->form_validation->set_rules('audience', 'Audience', 'required|in_list[all,one]');

		if ( ! $this->form_validation->run())
		{
			$this->session->set_flashdata('error', validation_errors(' ', ' '));
			redirect('admin/notifications');
		}

		$title    = $this->input->post('title', TRUE);
		$message  = $this->input->post('message', TRUE);
		$link     = $this->input->post('link', TRUE) ?: NULL;
		$audience = $this->input->post('audience', TRUE);

		if ($audience === 'all')
		{
			$count = $this->notification_model->broadcast($title, $message, $link);
			$this->log_action('Broadcast notification', 'notifications', NULL, $title);
			$this->session->set_flashdata('success', 'Sent to '.$count.' active users.');
		}
		else
		{
			$username = $this->input->post('username', TRUE);
			$user     = $this->user_model->by_login($username);

			if ( ! $user)
			{
				$this->session->set_flashdata('error', 'No user matches "'.$username.'".');
				redirect('admin/notifications');
			}

			$this->notification_model->push($user->id, $title, $message, $link);
			$this->log_action('Sent notification', 'notifications', $user->id, $title);
			$this->session->set_flashdata('success', 'Sent to '.$user->username.'.');
		}

		redirect('admin/notifications');
	}
}
