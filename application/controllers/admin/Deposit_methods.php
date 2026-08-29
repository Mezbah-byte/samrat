<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The company's receiving wallets. This is the concrete answer to the
 * "we will just give a Binance ID" gap in the requirements: a deposit needs a
 * network, an address and a QR code before a user can act on it.
 */
class Deposit_methods extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('deposit_method_model');
	}

	public function index()
	{
		$this->render('admin/deposit_methods', array(
			'page_title'  => 'Deposit Wallets',
			'active_menu' => 'deposit_methods',
			'rows'        => $this->deposit_method_model->all(),
		));
	}

	public function create()
	{
		$this->form($this->blank(), 'create');
	}

	public function edit($id)
	{
		$row = $this->deposit_method_model->find($id);

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
			$this->form_validation->set_rules('name', 'Name', 'required|trim|max_length[80]');
			$this->form_validation->set_rules('network', 'Network', 'required|trim|max_length[30]');
			$this->form_validation->set_rules('currency', 'Currency', 'required|trim|max_length[20]');
			$this->form_validation->set_rules('wallet_address', 'Wallet Address', 'required|trim|min_length[20]|max_length[191]');
			$this->form_validation->set_rules('min_amount', 'Minimum Amount', 'numeric');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,inactive]');

			if ($this->form_validation->run())
			{
				$data = array(
					'name'           => $this->input->post('name', TRUE),
					'network'        => strtoupper($this->input->post('network', TRUE)),
					'currency'       => strtoupper($this->input->post('currency', TRUE)),
					'wallet_address' => $this->input->post('wallet_address', TRUE),
					'min_amount'     => money_raw($this->input->post('min_amount') ?: 0),
					'instructions'   => $this->input->post('instructions', TRUE),
					'sort_order'     => (int) $this->input->post('sort_order'),
					'status'         => $this->input->post('status', TRUE),
				);

				if ( ! empty($_FILES['qr_image']['name']))
				{
					$this->load->library('uploader_lib');
					$file = $this->uploader_lib->image('qr_image', 'qr');

					if ($file === FALSE)
					{
						$this->session->set_flashdata('error', 'QR image: '.$this->uploader_lib->error());
						redirect($mode === 'edit' ? 'admin/deposit-methods/edit/'.$row->id : 'admin/deposit-methods/create');
					}

					if ($mode === 'edit' && $row->qr_image)
					{
						$this->uploader_lib->remove('qr', $row->qr_image);
					}

					$data['qr_image'] = $file;
				}

				if ($mode === 'edit')
				{
					$this->deposit_method_model->update($row->id, $data);
					$this->log_action('Updated deposit wallet', 'deposit_methods', $row->id, $data['name']);
					$this->session->set_flashdata('success', 'Wallet updated.');
				}
				else
				{
					$new_id = $this->deposit_method_model->insert($data);
					$this->log_action('Created deposit wallet', 'deposit_methods', $new_id, $data['name']);
					$this->session->set_flashdata('success', 'Wallet added.');
				}

				redirect('admin/deposit-methods');
			}
		}

		$this->render('admin/deposit_method_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit Wallet' : 'New Wallet',
			'active_menu' => 'deposit_methods',
			'm'           => $row,
			'mode'        => $mode,
			'networks'    => network_list(),
		));
	}

	public function delete($id)
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->deposit_method_model->find($id);

		if ( ! $row)
		{
			show_404();
		}

		// deposits.deposit_method_id is ON DELETE SET NULL, so history survives.
		if ($row->qr_image)
		{
			$this->load->library('uploader_lib');
			$this->uploader_lib->remove('qr', $row->qr_image);
		}

		$this->deposit_method_model->delete($id);
		$this->log_action('Deleted deposit wallet', 'deposit_methods', $id, $row->name);

		$this->session->set_flashdata('success', 'Wallet deleted.');
		redirect('admin/deposit-methods');
	}

	protected function blank()
	{
		return (object) array(
			'id' => NULL, 'name' => '', 'network' => 'TRC20', 'currency' => 'USDT',
			'wallet_address' => '', 'qr_image' => NULL, 'min_amount' => '0',
			'instructions' => '', 'sort_order' => 0, 'status' => 'active',
		);
	}
}
