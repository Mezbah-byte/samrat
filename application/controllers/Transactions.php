<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions extends User_Controller {

	public function index()
	{
		$this->load->model(array('transaction_model', 'daily_earning_model'));

		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$type     = $this->input->get('type', TRUE) ?: '';

		$result = $this->transaction_model->for_user($this->user->id, $per_page, ($page - 1) * $per_page, $type);

		$this->render('user/transactions', array(
			'page_title'   => 'Transactions',
			'active_menu'  => 'transactions',
			'rows'         => $result['rows'],
			'total'        => $result['total'],
			'per_page'     => $per_page,
			'page'         => $page,
			'type'         => $type,
			'earnings'     => $this->daily_earning_model->for_user($this->user->id, 10)['rows'],
			'ledger_check' => $this->wallet_lib->reconcile($this->user->id),
		));
	}
}
