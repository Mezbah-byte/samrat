<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends Admin_Controller {

	/** Keys that must never be blanked or set to nonsense. */
	protected $numeric_keys = array(
		'withdrawal_fee_percent',
		'agent_deposit_commission_percent',
		'agent_withdraw_commission_percent',
		'agent_payout_fee_percent',
	);

	/**
	 * Free-text keys that are really enums.
	 *
	 * These two decide where every deposit and withdrawal is routed, so a
	 * typo here would take the whole flow down. The list is both the form's
	 * option source and the save whitelist, so the two cannot drift.
	 */
	protected $enum_keys = array(
		'deposit_route' => array(
			'admin' => 'Admin only - users pay the platform wallet (current behaviour)',
			'agent' => 'Agent only - every deposit is routed through an agent',
			'both'  => 'Both - the user picks admin wallet or agent',
		),
		'withdraw_route' => array(
			'admin' => 'Admin only - the platform pays every withdrawal (current behaviour)',
			'agent' => 'Agent only - every withdrawal is paid by an agent',
			'both'  => 'Both - the user picks admin or agent',
		),
	);

	public function index($group = 'general')
	{
		$this->require_perm('settings.view');

		$groups = $this->setting_model->groups();

		if ( ! in_array($group, $groups, TRUE))
		{
			$group = reset($groups) ?: 'general';
		}

		if ($this->input->method() === 'post')
		{
			$this->save($group);
		}

		$this->render('admin/settings', array(
			'page_title'  => 'Settings',
			'active_menu' => 'settings',
			'groups'      => $groups,
			'group'       => $group,
			'rows'        => $this->setting_model->by_group($group),
			'enum_keys'   => $this->enum_keys,
		));
	}

	protected function save($group)
	{
		$this->require_perm('settings.manage');

		$rows   = $this->setting_model->by_group($group);
		$posted = $this->input->post();

		foreach ($rows as $row)
		{
			if ($row->type === 'image')
			{
				$this->save_image($row);
				continue;
			}

			if ($row->type === 'boolean')
			{
				$this->setting_model->set($row->key, isset($posted[$row->key]) ? '1' : '0');
				continue;
			}

			if ( ! array_key_exists($row->key, $posted))
			{
				continue;
			}

			$value = $posted[$row->key];

			if (in_array($row->key, $this->numeric_keys, TRUE))
			{
				if ( ! is_numeric($value) || (float) $value < 0 || (float) $value > 100)
				{
					$this->session->set_flashdata('error', $row->label.' must be a number between 0 and 100.');
					redirect('admin/settings/index/'.$group);
				}
			}

			// A route key outside its whitelist would leave the deposit and
			// withdrawal screens with no path at all, so it is rejected rather
			// than stored and discovered later.
			if (isset($this->enum_keys[$row->key]) && ! isset($this->enum_keys[$row->key][$value]))
			{
				$this->session->set_flashdata('error', $row->label.' must be one of: '
					.implode(', ', array_keys($this->enum_keys[$row->key])).'.');
				redirect('admin/settings/index/'.$group);
			}

			$this->setting_model->set($row->key, is_string($value) ? trim($value) : $value);
		}

		$this->log_action('Updated settings', 'settings', NULL, $group);
		$this->session->set_flashdata('success', ucfirst($group).' settings saved.');
		redirect('admin/settings/index/'.$group);
	}

	protected function save_image($row)
	{
		if (empty($_FILES[$row->key]['name']))
		{
			// A checked "remove" box clears the stored file.
			if ($this->input->post('remove_'.$row->key))
			{
				$this->load->library('uploader_lib');
				$this->uploader_lib->remove('logo', $this->setting_model->get($row->key));
				$this->setting_model->set($row->key, '');
			}
			return;
		}

		$this->load->library('uploader_lib');
		$file = $this->uploader_lib->image($row->key, 'logo');

		if ($file === FALSE)
		{
			$this->session->set_flashdata('error', $row->label.': '.$this->uploader_lib->error());
			return;
		}

		$old = $this->setting_model->get($row->key);

		if ($old)
		{
			$this->uploader_lib->remove('logo', $old);
		}

		$this->setting_model->set($row->key, $file);
	}

	/** Rotates the cron secret so an exposed URL can be invalidated. */
	public function regenerate_cron_secret()
	{
		$this->require_perm('settings.cron_secret');

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$secret = bin2hex(random_bytes(16));
		$this->setting_model->set('cron_secret', $secret);
		$this->log_action('Regenerated cron secret', 'settings');

		$this->session->set_flashdata('success', 'New cron secret generated. Update any scheduled task that uses the URL.');
		redirect('admin/settings/index/system');
	}
}
