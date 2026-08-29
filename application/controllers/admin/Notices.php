<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notices extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('notice_model');
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$search   = $this->input->get('q', TRUE) ?: '';
		$result   = $this->notice_model->paginate($per_page, ($page - 1) * $per_page, array(), $search, array('title'));

		$this->render('admin/notices', array(
			'page_title'  => 'Notices',
			'active_menu' => 'notices',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'search'      => $search,
		));
	}

	public function create()
	{
		$this->form($this->blank(), 'create');
	}

	public function edit($id)
	{
		$notice = $this->notice_model->find($id);

		if ( ! $notice)
		{
			show_404();
		}

		$this->form($notice, 'edit');
	}

	protected function form($notice, $mode)
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('title', 'Title', 'required|trim|max_length[180]');
			$this->form_validation->set_rules('content', 'Content', 'required|trim');
			$this->form_validation->set_rules('type', 'Type', 'required|in_list[announcement,notice,update,promotion]');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[published,draft]');

			if ($this->form_validation->run())
			{
				$title = $this->input->post('title', TRUE);

				$data = array(
					'title'        => $title,
					'slug'         => $this->notice_model->unique_slug($title, $mode === 'edit' ? $notice->id : NULL),
					// Notices are authored by trusted admins and rendered as
					// HTML, so basic formatting tags are allowed through.
					'content'      => $this->input->post('content'),
					'type'         => $this->input->post('type', TRUE),
					'is_pinned'    => $this->input->post('is_pinned') ? 1 : 0,
					'status'       => $this->input->post('status', TRUE),
					'published_at' => $this->input->post('published_at') ?: date('Y-m-d H:i:s'),
				);

				if ( ! empty($_FILES['image']['name']))
				{
					$this->load->library('uploader_lib');
					$file = $this->uploader_lib->image('image', 'notices');

					if ($file === FALSE)
					{
						$this->session->set_flashdata('error', 'Image: '.$this->uploader_lib->error());
						redirect($mode === 'edit' ? 'admin/notices/edit/'.$notice->id : 'admin/notices/create');
					}

					if ($mode === 'edit' && $notice->image)
					{
						$this->uploader_lib->remove('notices', $notice->image);
					}

					$data['image'] = $file;
				}

				if ($mode === 'edit')
				{
					$this->notice_model->update($notice->id, $data);
					$this->log_action('Updated notice', 'notices', $notice->id, $title);
					$this->session->set_flashdata('success', 'Notice updated.');
				}
				else
				{
					$new_id = $this->notice_model->insert($data);
					$this->log_action('Published notice', 'notices', $new_id, $title);
					$this->session->set_flashdata('success', 'Notice published.');
				}

				redirect('admin/notices');
			}
		}

		$this->render('admin/notice_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit Notice' : 'New Notice',
			'active_menu' => 'notices',
			'n'           => $notice,
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

		$notice = $this->notice_model->find($id);

		if ( ! $notice)
		{
			show_404();
		}

		if ($notice->image)
		{
			$this->load->library('uploader_lib');
			$this->uploader_lib->remove('notices', $notice->image);
		}

		$this->notice_model->delete($id);
		$this->log_action('Deleted notice', 'notices', $id, $notice->title);

		$this->session->set_flashdata('success', 'Notice deleted.');
		redirect('admin/notices');
	}

	protected function blank()
	{
		return (object) array(
			'id' => NULL, 'title' => '', 'slug' => '', 'content' => '',
			'type' => 'notice', 'image' => NULL, 'is_pinned' => 0,
			'status' => 'published', 'published_at' => date('Y-m-d H:i:s'),
		);
	}
}
