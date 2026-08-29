<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Investments extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('investment_model', 'daily_earning_model'));
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->investment_model->paginate_admin($per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('admin/investments', array(
			'page_title'  => 'Investments',
			'active_menu' => 'investments',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->investment_model->stats(),
		));
	}

	public function view($id)
	{
		$investment = $this->investment_model->find($id);

		if ( ! $investment)
		{
			show_404();
		}

		$this->load->model(array('user_model', 'package_model'));

		$this->render('admin/investment_view', array(
			'page_title'  => 'Investment #'.$investment->id,
			'active_menu' => 'investments',
			'i'           => $investment,
			'owner'       => $this->user_model->find($investment->user_id),
			'package'     => $this->package_model->find($investment->package_id),
			'days'        => $this->db->where('investment_id', $investment->id)
				->order_by('earn_date', 'DESC')->limit(60)->get('daily_earnings')->result(),
		));
	}

	/**
	 * Stops an active plan. Deliberately does not claw back anything already
	 * credited — those days were earned.
	 */
	public function cancel($id)
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$investment = $this->investment_model->find($id);

		if ( ! $investment)
		{
			show_404();
		}

		if ($investment->status !== 'active')
		{
			$this->session->set_flashdata('error', 'That plan is already '.$investment->status.'.');
			redirect('admin/investments/view/'.$id);
		}

		$this->db->trans_start();

		$this->investment_model->update($id, array('status' => 'cancelled'));

		// Future pending days can never pay out now, so close them off.
		$this->db->where('investment_id', $id)->where('status', 'pending')
			->update('daily_earnings', array('status' => 'missed'));

		$this->db->trans_complete();

		$this->log_action('Cancelled investment', 'investments', $id, $this->input->post('reason', TRUE));

		$this->load->model('notification_model');
		$this->notification_model->push($investment->user_id, 'Plan cancelled',
			'Your plan has been cancelled by an administrator. Earnings already credited are unaffected.', 'packages');

		$this->session->set_flashdata('success', 'Investment cancelled.');
		redirect('admin/investments/view/'.$id);
	}
}
