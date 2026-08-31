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
			$this->form_validation->set_rules('source', 'Creative Source', 'required|in_list[upload,embed,vast]');
			$this->form_validation->set_rules('placement', 'Placement', 'required|in_list[daily_task,global]');
			$this->form_validation->set_rules('watch_seconds', 'Watch Seconds', 'required|integer|greater_than_equal_to[0]');
			$this->form_validation->set_rules('target_url', 'Target URL', 'trim|max_length[500]');
			$this->form_validation->set_rules('media_url', 'Media URL', 'trim|max_length[500]');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,inactive]');

			// An ad with nothing to show would still eat a slot in the quota, so
			// whichever source is picked has to carry its creative.
			$source = $this->input->post('source', TRUE);

			$this->form_validation->set_rules('vast_url', 'VAST Tag URL',
				$source === 'vast' ? 'required|trim|valid_url|max_length[500]' : 'trim|max_length[500]');

			if ($source === 'embed')
			{
				$this->form_validation->set_rules('embed_code', 'Network Tag', 'required');
			}

			// An `upload` ad with no file, no URL and no text renders as an empty
			// box, so Ad_model refuses to serve it. Say so here instead of
			// letting the admin save an ad that silently never appears.
			if ($source === 'upload'
				&& empty($_FILES['media']['name'])
				&& ! $this->input->post('media_url')
				&& ! trim((string) $this->input->post('body', TRUE))
				&& ! ($mode === 'edit' && $ad->media))
			{
				$this->form_validation->set_rules('media_url', 'Media URL',
					'required', array('required' => 'Add an image, a media URL or some body text - an ad with no creative is never shown.'));
			}

			if ($this->form_validation->run())
			{
				$data = array(
					'title'         => $this->input->post('title', TRUE),
					'type'          => $this->input->post('type', TRUE),
					'source'        => $this->input->post('source', TRUE),
					'placement'     => $this->input->post('placement', TRUE),
					'target_url'    => $this->input->post('target_url', TRUE) ?: NULL,
					'media_url'     => $this->input->post('media_url', TRUE) ?: NULL,
					'body'          => $this->input->post('body', TRUE),
					// Raw on purpose: this is the network's own script tag, and
					// escaping it would break what it ships. Admin-only input,
					// and it only ever runs inside the sandboxed iframe the ad
					// modal builds - never in this document.
					'embed_code'    => $this->input->post('embed_code', FALSE) ?: NULL,
					'vast_url'      => $this->input->post('vast_url', TRUE) ?: NULL,
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
			'id' => NULL, 'title' => '', 'type' => 'image', 'source' => 'upload',
			'media' => NULL, 'media_url' => '', 'target_url' => '', 'body' => '',
			'embed_code' => '', 'vast_url' => '', 'watch_seconds' => 15,
			'placement' => 'daily_task', 'total_views' => 0, 'sort_order' => 0,
			'starts_at' => NULL, 'ends_at' => NULL, 'status' => 'active',
		);
	}
}
