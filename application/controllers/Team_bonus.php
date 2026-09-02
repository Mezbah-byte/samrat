<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The user-facing team volume bonus page.
 *
 * All of the thinking lives in Team_bonus_lib; this only decides what to show
 * and turns a Claim press into one library call.
 */
class Team_bonus extends User_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('team_bonus_lib');

		// The whole feature is a single admin switch. With it off the page is
		// gone, not just hidden from the sidebar.
		if ( ! $this->team_bonus_lib->enabled())
		{
			show_404();
		}
	}

	public function index()
	{
		// Re-checks the ladder on every visit, so a target the admin lowered
		// unlocks here rather than waiting for the team's next purchase.
		$this->team_bonus_lib->sync_unlocks($this->user->id);

		$this->load->model('team_bonus_claim_model');

		$this->render('user/team_bonus', array(
			'page_title'  => 'Team Bonus',
			'active_menu' => 'team_bonus',
			'progress'    => $this->team_bonus_lib->progress($this->user->id),
			'history'     => $this->team_bonus_claim_model->claimed_for_user($this->user->id, 20),
			'direct_count' => $this->user_model->referral_count($this->user->id),
		));
	}

	public function claim($tier_id)
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$result = $this->team_bonus_lib->claim($this->user->id, $tier_id);

		$this->session->set_flashdata($result['ok'] ? 'success' : 'error', $result['message']);
		redirect('team-bonus');
	}
}
