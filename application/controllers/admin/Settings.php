<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends Admin_Controller {

	/** Keys that must never be blanked or set to nonsense. */
	protected $numeric_keys = array('withdrawal_fee_percent');

	public function index($group = 'general')
	{
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
		));
	}

	protected function save($group)
	{
		$this->require_role(array('super_admin', 'admin'));

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
		$this->require_role(array('super_admin'));

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
