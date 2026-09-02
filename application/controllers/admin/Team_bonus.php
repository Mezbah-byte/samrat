<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The team volume bonus ladder, edited as a single form.
 *
 * Shaped on Admin/Referral_levels: one submit saves every tier plus the
 * feature switches, and a tier that has already unlocked for somebody is
 * switched to inactive rather than deleted, so the claim rows pointing at it
 * still resolve to a name.
 */
class Team_bonus extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('team_bonus_tier_model', 'team_bonus_claim_model'));
		$this->load->library('team_bonus_lib');
	}

	public function index()
	{
		if ($this->input->method() === 'post')
		{
			$this->save();
		}

		$this->render('admin/team_bonus', array(
			'page_title'  => 'Team Bonus',
			'active_menu' => 'team_bonus',
			'rows'        => $this->team_bonus_tier_model->ladder(),
			'stats'       => $this->team_bonus_claim_model->totals_by_tier(),
			'paid_total'  => $this->team_bonus_claim_model->paid_total(),
			'rules'       => array(
				'team_bonus_enabled'               => (int) $this->setting_model->get('team_bonus_enabled', 1),
				'team_bonus_require_active_upline' => (int) $this->setting_model->get('team_bonus_require_active_upline', 1),
			),
		));
	}

	/** Names, targets, amounts, modes, on/off state and the two switches. */
	protected function save()
	{
		$this->require_role(array('super_admin', 'admin'));

		$names   = (array) $this->input->post('name');
		$targets = (array) $this->input->post('target_volume');
		$bonuses = (array) $this->input->post('bonus_amount');
		$modes   = (array) $this->input->post('mode');
		$minrefs = (array) $this->input->post('min_referrals');
		$orders  = (array) $this->input->post('sort_order');
		$active  = (array) $this->input->post('active');

		$changed = array();

		foreach ($this->team_bonus_tier_model->ladder() as $row)
		{
			$id = (int) $row->id;

			if ( ! array_key_exists($id, $names))
			{
				continue;
			}

			// A negative target would unlock for everyone the moment it is
			// saved; a negative bonus would debit the user on claim.
			$data = array(
				'name'          => mb_substr(trim((string) $names[$id]), 0, 80) ?: 'Tier '.$id,
				'target_volume' => money_raw(max(0, (float) (isset($targets[$id]) ? $targets[$id] : 0))),
				'bonus_amount'  => money_raw(max(0, (float) (isset($bonuses[$id]) ? $bonuses[$id] : 0))),
				'mode'          => (isset($modes[$id]) && $modes[$id] === 'single') ? 'single' : 'combined',
				'min_referrals' => max(0, (int) (isset($minrefs[$id]) ? $minrefs[$id] : 0)),
				'sort_order'    => max(0, (int) (isset($orders[$id]) ? $orders[$id] : 0)),
				'status'        => isset($active[$id]) ? 'active' : 'inactive',
			);

			// Numbers are compared as numbers - the form posts "1000" against a
			// stored "1000.00000000", which a string test would call a change
			// and rewrite every row on every save. Text is compared as text.
			$dirty = $row->name !== $data['name']
				|| $row->mode !== $data['mode']
				|| $row->status !== $data['status']
				|| (float) $row->target_volume !== (float) $data['target_volume']
				|| (float) $row->bonus_amount  !== (float) $data['bonus_amount']
				|| (int) $row->min_referrals   !== (int) $data['min_referrals']
				|| (int) $row->sort_order      !== (int) $data['sort_order'];

			if ( ! $dirty)
			{
				continue;
			}

			$this->team_bonus_tier_model->update($id, $data);

			$changed[] = $data['name'].' '.money($data['target_volume']).'/'
				.money($data['bonus_amount']).' ('.$data['mode'].', '.$data['status'].')';
		}

		foreach (array('team_bonus_enabled', 'team_bonus_require_active_upline') as $key)
		{
			$this->setting_model->set($key, $this->input->post($key) ? '1' : '0');
		}

		$this->log_action('Updated team bonus ladder', 'team_bonus_tiers', NULL,
			$changed ? implode(', ', $changed) : 'switches only');

		$this->session->set_flashdata('success', 'Team bonus ladder saved.');
		redirect('admin/team-bonus');
	}

	/** Appends a blank, inactive tier for the admin to fill in. */
	public function add()
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		if (count($this->team_bonus_tier_model->ladder()) >= 20)
		{
			$this->session->set_flashdata('warning', 'Twenty tiers is the cap.');
			redirect('admin/team-bonus');
		}

		$id = $this->team_bonus_tier_model->append();
		$this->log_action('Added team bonus tier', 'team_bonus_tiers', $id);

		$this->session->set_flashdata('success', 'Tier added, switched off. Set its target and bonus below.');
		redirect('admin/team-bonus');
	}

	/**
	 * A tier with claim rows behind it is switched off instead of deleted, so
	 * the history still resolves to a name - the same protection
	 * Referral_levels::delete() gives a generation that has paid out.
	 */
	public function delete($id)
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->team_bonus_tier_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$claims = $this->team_bonus_claim_model->count_for_tier($row->id);

		if ($claims > 0)
		{
			$this->team_bonus_tier_model->update($row->id, array('status' => 'inactive'));
			$this->log_action('Deactivated team bonus tier (has claims)', 'team_bonus_tiers', $row->id, $row->name);
			$this->session->set_flashdata('warning', $row->name.' has '.$claims
				.' claim record(s), so it was switched off instead of deleted.');
			redirect('admin/team-bonus');
		}

		$this->team_bonus_tier_model->delete($row->id);
		$this->log_action('Deleted team bonus tier', 'team_bonus_tiers', $row->id, $row->name);

		$this->session->set_flashdata('success', $row->name.' removed.');
		redirect('admin/team-bonus');
	}

	/**
	 * Rebuild every user's team counters from `deposits`.
	 *
	 * The counters only ever move up on approval, so a deposit edited or
	 * reversed by hand leaves a bar reading too high until this runs. The cron
	 * does it nightly; this is the button for when it cannot wait.
	 */
	public function recompute()
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$report = $this->team_bonus_lib->recompute();

		$this->log_action('Recomputed team bonus counters', 'users', NULL,
			$report['corrected'].' of '.$report['scanned'].' corrected');

		$this->session->set_flashdata(
			$report['corrected'] > 0 ? 'success' : 'info',
			$report['corrected'] > 0
				? 'Recomputed '.$report['scanned'].' accounts and corrected '.$report['corrected'].'.'
				: 'Recomputed '.$report['scanned'].' accounts. Everything already matched.'
		);
		redirect('admin/team-bonus');
	}
}
