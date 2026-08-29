<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions extends Admin_Controller {

	public function index()
	{
		$this->load->model(array('transaction_model', 'withdrawal_model'));

		$per_page = 25;
		$page     = max(1, (int) $this->input->get('page'));
		$type     = $this->input->get('type', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$result   = $this->transaction_model->paginate_with_user($per_page, ($page - 1) * $per_page, $type, $search);
		$wd_stats = $this->withdrawal_model->stats();

		$this->render('admin/transactions', array(
			'page_title'  => 'Transactions',
			'active_menu' => 'transactions',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'type'        => $type,
			'search'      => $search,
			// Withdrawn and fees come from the withdrawals table, not the raw
			// ledger: a rejected request leaves its debit and its refund both
			// in the ledger, so summing by type would overstate both figures.
			'totals'      => array(
				'deposit'        => $this->transaction_model->total_by_type('deposit'),
				'daily_profit'   => $this->transaction_model->total_by_type('daily_profit'),
				'referral_bonus' => $this->transaction_model->total_by_type('referral_bonus'),
				'refunded'       => $this->transaction_model->total_by_type('refund'),
				'withdrawal'     => $wd_stats['paid_total'],
				'withdrawal_fee' => $wd_stats['fee_total'],
			),
		));
	}
}
