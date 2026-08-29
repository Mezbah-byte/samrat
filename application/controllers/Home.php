<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends Public_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('package_model', 'notice_model', 'ad_model', 'user_model'));
		$this->view_data['ticker_notices'] = $this->notice_model->published(3);
	}

	public function index()
	{
		// The landing page is deliberately generic: no platform stats, packages,
		// notices or ads reach it, so nothing internal can leak to a visitor who
		// has not signed in. The notice ticker is dropped here for the same reason.
		$this->view_data['ticker_notices'] = array();

		$this->render('public/home', array(
			'page_title'  => '',
			'active_menu' => 'home',
		));
	}

	public function plans()
	{
		$this->render('public/plans', array(
			'page_title'  => 'Investment Plans',
			'active_menu' => 'plans',
			'packages'    => $this->package_model->active(),
		));
	}

	public function about()
	{
		$this->render('public/about', array(
			'page_title'  => 'About',
			'active_menu' => 'about',
		));
	}

	public function notices()
	{
		$this->render('public/notices', array(
			'page_title'  => 'Notice Board',
			'active_menu' => 'notices',
			'notices'     => $this->notice_model->published(),
		));
	}

	public function notice($slug)
	{
		$notice = $this->notice_model->published_by_slug($slug);

		if ( ! $notice)
		{
			show_404();
		}

		$this->render('public/notice', array(
			'page_title'  => $notice->title,
			'active_menu' => 'notices',
			'notice'      => $notice,
			'recent'      => $this->notice_model->published(5),
		));
	}
}
