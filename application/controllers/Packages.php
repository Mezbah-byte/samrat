<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Packages extends User_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('package_model', 'investment_model'));
	}

	public function index()
	{
		$result = $this->investment_model->for_user($this->user->id, 20);

		$this->render('user/packages', array(
			'page_title'    => 'Packages',
			'active_menu'   => 'packages',
			'packages'      => $this->package_model->active(),
			'investments'   => $result['rows'],
			'ads_remaining' => $this->investment_lib->today_progress($this->user->id)['remaining'],
		));
	}

	/** Buying is just a shortcut into the deposit form for that package. */
	public function buy($package_id)
	{
		$package = $this->package_model->find($package_id);

		if ( ! $package || $package->status !== 'active')
		{
			$this->session->set_flashdata('error', 'That package is not available.');
			redirect('packages');
		}

		redirect('deposit/create/'.$package->id);
	}
}
