<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Deposits extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('deposit_model');
		$this->load->library('investment_lib');
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->deposit_model->paginate_admin($per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('admin/deposits', array(
			'page_title'  => 'Deposits',
			'active_menu' => 'deposits',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->deposit_model->stats(),
		));
	}

	public function view($id)
	{
		$deposit = $this->deposit_model->find_detailed($id);

		if ( ! $deposit)
		{
			show_404();
		}

		$this->render('admin/deposit_view', array(
			'page_title'  => 'Deposit #'.$deposit->id,
			'active_menu' => 'deposits',
			'deposit'     => $deposit,
		));
	}

	/**
	 * Approving credits the balance, opens the investment and pays the
	 * referrer — all inside Investment_lib's single transaction.
	 */
	public function approve($id)
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$note   = $this->input->post('admin_note', TRUE);
		$result = $this->investment_lib->approve_deposit($id, $this->admin->id, $note);

		if ($result['ok'])
		{
			$this->log_action('Approved deposit', 'deposits', $id, $note);
			$this->session->set_flashdata('success', $result['message']);
		}
		else
		{
			$this->session->set_flashdata('error', $result['message']);
		}

		redirect('admin/deposits/view/'.$id);
	}

	public function reject($id)
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$note   = $this->input->post('admin_note', TRUE);
		$result = $this->investment_lib->reject_deposit($id, $this->admin->id, $note);

		if ($result['ok'])
		{
			$this->log_action('Rejected deposit', 'deposits', $id, $note);
			$this->session->set_flashdata('success', $result['message']);
		}
		else
		{
			$this->session->set_flashdata('error', $result['message']);
		}

		redirect('admin/deposits/view/'.$id);
	}
}
