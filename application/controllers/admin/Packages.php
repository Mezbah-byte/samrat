<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Packages extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('package_model');
	}

	public function index()
	{
		$this->render('admin/packages', array(
			'page_title'  => 'Packages',
			'active_menu' => 'packages',
			'rows'        => $this->package_model->all(),
		));
	}

	public function create()
	{
		$this->form($this->blank_package(), 'create');
	}

	public function edit($id)
	{
		$package = $this->package_model->find($id);

		if ( ! $package)
		{
			show_404();
		}

		$this->form($package, 'edit');
	}

	protected function form($package, $mode)
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('name', 'Name', 'required|trim|max_length[80]');
			$this->form_validation->set_rules('price', 'Deposit Amount', 'required|numeric|greater_than[0]');
			$this->form_validation->set_rules('daily_return_percent', 'Daily Return %', 'required|numeric|greater_than[0]');
			$this->form_validation->set_rules('duration_days', 'Duration', 'required|integer|greater_than[0]');
			$this->form_validation->set_rules('daily_ads', 'Daily Ads', 'required|integer|greater_than_equal_to[0]');
			$this->form_validation->set_rules('min_withdraw', 'Minimum Withdrawal', 'required|numeric|greater_than_equal_to[0]');
			$this->form_validation->set_rules('sort_order', 'Sort Order', 'integer');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,inactive]');

			if ($this->form_validation->run())
			{
				$name = $this->input->post('name', TRUE);

				$data = array(
					'name'                 => $name,
					'slug'                 => $this->package_model->unique_slug($name, $mode === 'edit' ? $package->id : NULL),
					'price'                => money_raw($this->input->post('price')),
					'daily_return_percent' => (float) $this->input->post('daily_return_percent'),
					'duration_days'        => (int) $this->input->post('duration_days'),
					'daily_ads'            => (int) $this->input->post('daily_ads'),
					'min_withdraw'         => money_raw($this->input->post('min_withdraw')),
					'description'          => $this->input->post('description', TRUE),
					'sort_order'           => (int) $this->input->post('sort_order'),
					'status'               => $this->input->post('status', TRUE),
				);

				if ( ! empty($_FILES['image']['name']))
				{
					$this->load->library('uploader_lib');
					$file = $this->uploader_lib->image('image', 'ads');

					if ($file === FALSE)
					{
						$this->session->set_flashdata('error', 'Image: '.$this->uploader_lib->error());
						redirect($mode === 'edit' ? 'admin/packages/edit/'.$package->id : 'admin/packages/create');
					}

					$data['image'] = $file;
				}

				if ($mode === 'edit')
				{
					$this->package_model->update($package->id, $data);
					$this->log_action('Updated package', 'packages', $package->id, $name);
					$this->session->set_flashdata('success', 'Package updated.');
				}
				else
				{
					$new_id = $this->package_model->insert($data);
					$this->log_action('Created package', 'packages', $new_id, $name);
					$this->session->set_flashdata('success', 'Package created.');
				}

				redirect('admin/packages');
			}
		}

		$this->render('admin/package_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit '.$package->name : 'New Package',
			'active_menu' => 'packages',
			'p'           => $package,
			'mode'        => $mode,
		));
	}

	public function delete($id)
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$package = $this->package_model->find($id);

		if ( ! $package)
		{
			show_404();
		}

		// Deposits and investments reference packages with ON DELETE RESTRICT,
		// so deactivate rather than break historical records.
		$in_use = (int) $this->db->where('package_id', $id)->count_all_results('investments')
			+ (int) $this->db->where('package_id', $id)->count_all_results('deposits');

		if ($in_use > 0)
		{
			$this->package_model->update($id, array('status' => 'inactive'));
			$this->log_action('Deactivated package (in use)', 'packages', $id, $package->name);
			$this->session->set_flashdata('warning', 'That package has '.$in_use.' linked records, so it was deactivated instead of deleted.');
			redirect('admin/packages');
		}

		$this->package_model->delete($id);
		$this->log_action('Deleted package', 'packages', $id, $package->name);

		$this->session->set_flashdata('success', 'Package deleted.');
		redirect('admin/packages');
	}

	protected function blank_package()
	{
		return (object) array(
			'id' => NULL, 'name' => '', 'slug' => '', 'price' => '',
			'daily_return_percent' => '2.0000', 'duration_days' => 100,
			'daily_ads' => 2, 'min_withdraw' => '', 'image' => NULL,
			'description' => '', 'sort_order' => 0, 'status' => 'active',
		);
	}
}
