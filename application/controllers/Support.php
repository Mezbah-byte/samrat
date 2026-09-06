<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The support page inside the user panel.
 *
 * There is nothing to store: every channel on it is a row an admin publishes
 * under Admin -> Support Links, and support_channels() decides which ones are
 * usable. The page is read-only on purpose - the platform does not run a
 * ticket system, it hands the user the ways to reach a human.
 */
class Support extends User_Controller {

	public function __construct()
	{
		parent::__construct();

		// A single admin switch, matching how the agent panel and team bonus
		// are gated: off means the page is gone, not merely hidden.
		if (setting('support_enabled', '1') !== '1')
		{
			show_404();
		}
	}

	public function index()
	{
		$this->render('user/support', array(
			'page_title'  => 'Support',
			'active_menu' => 'support',
			'channels'    => support_channels(),
			'hours'       => setting('support_hours', ''),
			'note'        => setting('support_note', ''),
		));
	}
}
