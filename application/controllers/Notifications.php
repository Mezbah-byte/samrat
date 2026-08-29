<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends User_Controller {

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$result   = $this->notification_model->for_user($this->user->id, $per_page, ($page - 1) * $per_page);

		$this->render('user/notifications', array(
			'page_title'  => 'Notifications',
			'active_menu' => 'notifications',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
		));
	}

	public function read($id)
	{
		$this->notification_model->mark_read($id, $this->user->id);

		$row = $this->notification_model->find($id);

		if ($row && (int) $row->user_id === (int) $this->user->id && $row->link)
		{
			redirect($row->link);
		}

		redirect('notifications');
	}

	public function read_all()
	{
		$this->notification_model->mark_all_read($this->user->id);
		$this->session->set_flashdata('success', 'All notifications marked as read.');
		redirect('notifications');
	}
}
