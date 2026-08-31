<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The referral ladder: one row per generation, edited as a single form.
 *
 * Levels stay contiguous - a new generation is always appended on top and only
 * the top one can be removed - because the payout walk in Investment_lib climbs
 * level 1, 2, 3 in order. A generation that should stop paying is switched to
 * inactive rather than deleted, which keeps its history intact.
 */
class Referral_levels extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('referral_level_model', 'referral_model'));
	}

	public function index()
	{
		if ($this->input->method() === 'post')
		{
			$this->save();
		}

		$this->render('admin/referral_levels', array(
			'page_title'  => 'Referral Levels',
			'active_menu' => 'referral_levels',
			'rows'        => $this->referral_level_model->ladder(),
			'paid'        => $this->referral_model->totals_by_level(),
			'total_pct'   => $this->referral_level_model->total_percent(),
			'rules'       => array(
				'referral_require_active_upline'      => (int) $this->setting_model->get('referral_require_active_upline', 1),
				'referral_require_upline_investment'  => (int) $this->setting_model->get('referral_require_upline_investment', 0),
			),
		));
	}

	/** Rates, on/off state and the two upline rules, all in one submit. */
	protected function save()
	{
		$this->require_role(array('super_admin', 'admin'));

		$percents = (array) $this->input->post('percent');
		$active   = (array) $this->input->post('active');
		$changed  = array();

		foreach ($this->referral_level_model->ladder() as $row)
		{
			if ( ! array_key_exists($row->id, $percents))
			{
				continue;
			}

			$percent = (float) $percents[$row->id];

			// A negative rate would debit the upline on someone else's deposit.
			if ($percent < 0)
			{
				$percent = 0;
			}
			if ($percent > 100)
			{
				$percent = 100;
			}

			$status = isset($active[$row->id]) ? 'active' : 'inactive';

			if ((float) $row->percent === $percent && $row->status === $status)
			{
				continue;
			}

			$this->referral_level_model->update($row->id, array(
				'percent' => $percent,
				'status'  => $status,
			));

			$changed[] = 'G'.$row->level.' '.$percent.'% ('.$status.')';
		}

		foreach (array('referral_require_active_upline', 'referral_require_upline_investment') as $key)
		{
			$this->setting_model->set($key, $this->input->post($key) ? '1' : '0');
		}

		$this->log_action('Updated referral ladder', 'referral_levels', NULL,
			$changed ? implode(', ', $changed) : 'rules only');

		$this->session->set_flashdata('success', 'Referral ladder saved.');
		redirect('admin/referral-levels');
	}

	/** Appends the next generation so the ladder has no gaps. */
	public function add()
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		if ($this->referral_level_model->max_level() >= 20)
		{
			$this->session->set_flashdata('warning', 'Twenty generations is the cap.');
			redirect('admin/referral-levels');
		}

		$level = $this->referral_level_model->max_level() + 1;
		$this->referral_level_model->append(1, 'active');
		$this->log_action('Added referral generation', 'referral_levels', NULL, 'G'.$level);

		$this->session->set_flashdata('success', 'Generation '.$level.' added at 1%. Set its rate below.');
		redirect('admin/referral-levels');
	}

	/**
	 * Removes the top generation only. Anything below it is load-bearing for the
	 * levels above, and a generation that has already paid out keeps its rows -
	 * switch it off instead so the history still resolves.
	 */
	public function delete($id)
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->referral_level_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$top = $this->referral_level_model->highest_row();

		if ( ! $top || (int) $top->id !== (int) $row->id)
		{
			$this->session->set_flashdata('error', 'Only the highest generation can be removed. Switch this one off instead.');
			redirect('admin/referral-levels');
		}

		$paid = (int) $this->db->where('level', (int) $row->level)->count_all_results('referral_commissions');

		if ($paid > 0)
		{
			$this->referral_level_model->update($row->id, array('status' => 'inactive'));
			$this->log_action('Deactivated referral generation (has payouts)', 'referral_levels', $row->id, 'G'.$row->level);
			$this->session->set_flashdata('warning', 'Generation '.$row->level.' has '.$paid.' payouts on record, so it was switched off instead of deleted.');
			redirect('admin/referral-levels');
		}

		$this->referral_level_model->delete($row->id);
		$this->log_action('Deleted referral generation', 'referral_levels', $row->id, 'G'.$row->level);

		$this->session->set_flashdata('success', 'Generation '.$row->level.' removed.');
		redirect('admin/referral-levels');
	}
}
