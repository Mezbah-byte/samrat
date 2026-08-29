<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logs extends Admin_Controller {

	public function index()
	{
		$per_page = 30;
		$page     = max(1, (int) $this->input->get('page'));
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->admin_log_model->paginate_with_admin($per_page, ($page - 1) * $per_page, $search);

		$this->render('admin/logs', array(
			'page_title'  => 'Activity Log',
			'active_menu' => 'logs',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'search'      => $search,
		));
	}
}
