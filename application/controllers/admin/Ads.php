<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ads extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('ad_model');
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$result   = $this->ad_model->paginate($per_page, ($page - 1) * $per_page, array(),
			$this->input->get('q', TRUE) ?: '', array('title'));

		$this->render('admin/ads', array(
			'page_title'  => 'Ads',
			'active_menu' => 'ads',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'search'      => $this->input->get('q', TRUE) ?: '',
			'stats'       => $this->ad_model->stats(),
		));
	}

	public function create()
	{
		$this->form($this->blank(), 'create');
	}

	public function edit($id)
	{
		$ad = $this->ad_model->find($id);

		if ( ! $ad)
		{
			show_404();
		}

		$this->form($ad, 'edit');
	}

	protected function form($ad, $mode)
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('title', 'Title', 'required|trim|max_length[150]');
			$this->form_validation->set_rules('type', 'Type', 'required|in_list[image,video,banner,link]');
			$this->form_validation->set_rules('placement', 'Placement', 'required|in_list[daily_task,global]');
			$this->form_validation->set_rules('watch_seconds', 'Watch Seconds', 'required|integer|greater_than_equal_to[0]');
			$this->form_validation->set_rules('target_url', 'Target URL', 'trim|max_length[500]');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,inactive]');

			if ($this->form_validation->run())
			{
				$data = array(
					'title'         => $this->input->post('title', TRUE),
					'type'          => $this->input->post('type', TRUE),
					'placement'     => $this->input->post('placement', TRUE),
					'target_url'    => $this->input->post('target_url', TRUE) ?: NULL,
					'body'          => $this->input->post('body', TRUE),
					'watch_seconds' => (int) $this->input->post('watch_seconds'),
					'sort_order'    => (int) $this->input->post('sort_order'),
					'starts_at'     => $this->input->post('starts_at') ?: NULL,
					'ends_at'       => $this->input->post('ends_at') ?: NULL,
					'status'        => $this->input->post('status', TRUE),
				);

				if ( ! empty($_FILES['media']['name']))
				{
					$this->load->library('uploader_lib');
					$file = $this->uploader_lib->image('media', 'ads');

					if ($file === FALSE)
					{
						$this->session->set_flashdata('error', 'Media: '.$this->uploader_lib->error());
						redirect($mode === 'edit' ? 'admin/ads/edit/'.$ad->id : 'admin/ads/create');
					}

					if ($mode === 'edit' && $ad->media)
					{
						$this->uploader_lib->remove('ads', $ad->media);
					}

					$data['media'] = $file;
				}

				if ($mode === 'edit')
				{
					$this->ad_model->update($ad->id, $data);
					$this->log_action('Updated ad', 'ads', $ad->id, $data['title']);
					$this->session->set_flashdata('success', 'Ad updated.');
				}
				else
				{
					$new_id = $this->ad_model->insert($data);
					$this->log_action('Created ad', 'ads', $new_id, $data['title']);
					$this->session->set_flashdata('success', 'Ad created.');
				}

				redirect('admin/ads');
			}
		}

		$this->render('admin/ad_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit Ad' : 'New Ad',
			'active_menu' => 'ads',
			'a'           => $ad,
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

		$ad = $this->ad_model->find($id);

		if ( ! $ad)
		{
			show_404();
		}

		if ($ad->media)
		{
			$this->load->library('uploader_lib');
			$this->uploader_lib->remove('ads', $ad->media);
		}

		// ad_views cascades, so historical view counts for this ad go with it.
		$this->ad_model->delete($id);
		$this->log_action('Deleted ad', 'ads', $id, $ad->title);

		$this->session->set_flashdata('success', 'Ad deleted.');
		redirect('admin/ads');
	}

	protected function blank()
	{
		return (object) array(
			'id' => NULL, 'title' => '', 'type' => 'image', 'media' => NULL,
			'target_url' => '', 'body' => '', 'watch_seconds' => 15,
			'placement' => 'daily_task', 'total_views' => 0, 'sort_order' => 0,
			'starts_at' => NULL, 'ends_at' => NULL, 'status' => 'active',
		);
	}
}
