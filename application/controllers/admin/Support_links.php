<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The contact channels on the Support page.
 *
 * Deliberately a table and not a fixed set of settings keys: the list is
 * open-ended, and a business that moves to a network nobody thought of should
 * be able to publish it without a deploy.
 */
class Support_links extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('support_link_model');
	}

	public function index()
	{
		$this->render('admin/support_links', array(
			'page_title'  => 'Support Links',
			'active_menu' => 'support_links',
			'rows'        => $this->support_link_model->all(),
			'enabled'     => setting('support_enabled', '1') === '1',
		));
	}

	public function create()
	{
		$this->form($this->blank(), 'create');
	}

	public function edit($id)
	{
		$row = $this->support_link_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$this->form($row, 'edit');
	}

	protected function form($row, $mode)
	{
		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('label', 'Label', 'required|trim|max_length[60]');
			$this->form_validation->set_rules('kind', 'Type', 'required|in_list[link,mailto,tel]');
			$this->form_validation->set_rules('value', 'Value', 'required|trim|max_length[255]');
			$this->form_validation->set_rules('icon', 'Icon', 'required|trim|max_length[40]');
			$this->form_validation->set_rules('note', 'Note', 'trim|max_length[160]');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,inactive]');

			if ($this->form_validation->run())
			{
				$label = $this->input->post('label', TRUE);
				$kind  = $this->input->post('kind', TRUE);

				// support_channel_url() is what the front end runs on this
				// value, so validating with it means anything stored here is
				// something the Support page can actually render. It also adds
				// the https:// an admin who pasted a bare domain left off.
				$url = support_channel_url($kind, $this->input->post('value', TRUE));

				if ($url === '')
				{
					$this->session->set_flashdata('error', $this->value_error($kind));
					redirect($this->back_to($mode, $row));
				}

				if ($this->support_link_model->label_taken($label, $row->id))
				{
					$this->session->set_flashdata('error', 'Another channel is already called "'.$label.'".');
					redirect($this->back_to($mode, $row));
				}

				$data = array(
					'label'      => $label,
					'kind'       => $kind,
					// The normalised form, not the raw input: what is stored is
					// what will be linked.
					'value'      => $kind === 'link' ? $url : $this->input->post('value', TRUE),
					'icon'       => $this->input->post('icon', TRUE),
					'note'       => $this->input->post('note', TRUE) ?: NULL,
					'sort_order' => (int) $this->input->post('sort_order'),
					'status'     => $this->input->post('status', TRUE),
				);

				if ($mode === 'edit')
				{
					$this->support_link_model->update($row->id, $data);
					$this->log_action('Updated support link', 'support_links', $row->id, $label);
					$this->session->set_flashdata('success', 'Channel updated.');
				}
				else
				{
					$new_id = $this->support_link_model->insert($data);
					$this->log_action('Created support link', 'support_links', $new_id, $label);
					$this->session->set_flashdata('success', 'Channel added.');
				}

				redirect('admin/support-links');
			}
		}

		$this->render('admin/support_link_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit Channel' : 'New Channel',
			'active_menu' => 'support_links',
			'm'           => $row,
			'mode'        => $mode,
			'icons'       => support_icon_choices(),
		));
	}

	public function delete($id)
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->support_link_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		$this->support_link_model->delete($id);
		$this->log_action('Deleted support link', 'support_links', $id, $row->label);

		$this->session->set_flashdata('success', 'Channel deleted.');
		redirect('admin/support-links');
	}

	protected function value_error($kind)
	{
		if ($kind === 'mailto')
		{
			return 'Value must be a valid email address.';
		}

		if ($kind === 'tel')
		{
			return 'Value must be a phone number.';
		}

		return 'Value must be a full http:// or https:// link.';
	}

	protected function back_to($mode, $row)
	{
		return $mode === 'edit'
			? 'admin/support-links/edit/'.$row->id
			: 'admin/support-links/create';
	}

	protected function blank()
	{
		return (object) array(
			'id' => NULL, 'label' => '', 'kind' => 'link', 'value' => '',
			'icon' => 'life-buoy', 'note' => '', 'sort_order' => 0, 'status' => 'active',
		);
	}
}
