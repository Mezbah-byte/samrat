<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The agent's own receive addresses - what a user is shown after picking this
 * agent to pay.
 *
 * Every lookup goes through find_for_agent(), never find(), so an id in the
 * URL can only ever reach a row this agent owns.
 */
class Wallets extends Agent_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->require_float();
		$this->load->model('agent_wallet_model');
	}

	public function index()
	{
		$this->render('agent/wallets', array(
			'page_title'  => 'My Wallets',
			'active_menu' => 'wallets',
			'rows'        => $this->agent_wallet_model->for_agent($this->agent->id),
		));
	}

	public function create()
	{
		$this->form($this->blank(), 'create');
	}

	public function edit($id)
	{
		$row = $this->agent_wallet_model->find_for_agent($id, $this->agent->id);

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
			$this->form_validation->set_rules('label', 'Label', 'required|trim|max_length[80]');
			$this->form_validation->set_rules('network', 'Network', 'required|trim|max_length[30]');
			$this->form_validation->set_rules('currency', 'Currency', 'required|trim|max_length[20]');
			$this->form_validation->set_rules('wallet_address', 'Wallet Address', 'required|trim|max_length[191]');
			$this->form_validation->set_rules('min_amount', 'Minimum', 'trim|numeric|greater_than_equal_to[0]');
			$this->form_validation->set_rules('max_amount', 'Maximum', 'trim|numeric|greater_than_equal_to[0]');
			$this->form_validation->set_rules('sort_order', 'Sort Order', 'trim|integer');
			$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,inactive]');

			if ($this->form_validation->run())
			{
				$min = round((float) $this->input->post('min_amount'), MONEY_SCALE);
				$max = round((float) $this->input->post('max_amount'), MONEY_SCALE);
				$back = $mode === 'edit' ? 'agent/wallets/edit/'.$row->id : 'agent/wallets/create';

				// A ceiling below the floor would hide the wallet from every
				// amount at once, which reads as "my wallet stopped working".
				if ($max > 0 && $max < $min)
				{
					$this->session->set_flashdata('error', 'The maximum cannot be below the minimum.');
					redirect($back);
				}

				$data = array(
					'label'          => $this->input->post('label', TRUE),
					'network'        => $this->input->post('network', TRUE),
					'currency'       => $this->input->post('currency', TRUE),
					'wallet_address' => $this->input->post('wallet_address', TRUE),
					'instructions'   => $this->input->post('instructions', TRUE) ?: NULL,
					'min_amount'     => money_raw($min),
					'max_amount'     => money_raw($max),
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
						redirect($back);
					}

					if ($mode === 'edit' && $row->qr_image)
					{
						$this->uploader_lib->remove('qr', $row->qr_image);
					}

					$data['qr_image'] = $file;
				}

				if ($mode === 'edit')
				{
					$this->agent_wallet_model->update($row->id, $data);
					$this->log_action('Updated wallet', 'agent_wallets', $row->id, $data['label']);
					$this->session->set_flashdata('success', 'Wallet updated.');
				}
				else
				{
					$data['agent_id'] = $this->agent->id;
					$new_id = $this->agent_wallet_model->insert($data);
					$this->log_action('Added wallet', 'agent_wallets', $new_id, $data['label']);
					$this->session->set_flashdata('success', 'Wallet added. Users paying you will see it.');
				}

				redirect('agent/wallets');
			}
		}

		$this->render('agent/wallet_form', array(
			'page_title'  => $mode === 'edit' ? 'Edit Wallet' : 'New Wallet',
			'active_menu' => 'wallets',
			'w'           => $row,
			'mode'        => $mode,
		));
	}

	public function delete($id)
	{
		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$row = $this->agent_wallet_model->find_for_agent($id, $this->agent->id);

		if ( ! $row)
		{
			show_404();
		}

		// Deposits point at this row, and the FK is ON DELETE SET NULL, so the
		// history survives the delete with the address blanked out.
		if ($row->qr_image)
		{
			$this->load->library('uploader_lib');
			$this->uploader_lib->remove('qr', $row->qr_image);
		}

		$this->agent_wallet_model->delete($row->id);
		$this->log_action('Deleted wallet', 'agent_wallets', $row->id, $row->label);

		$this->session->set_flashdata('success', 'Wallet deleted.');
		redirect('agent/wallets');
	}

	protected function blank()
	{
		return (object) array(
			'id' => NULL, 'label' => '', 'network' => 'TRC20', 'currency' => 'USDT',
			'wallet_address' => '', 'qr_image' => NULL, 'instructions' => '',
			'min_amount' => 0, 'max_amount' => 0, 'sort_order' => 0, 'status' => 'active',
		);
	}
}
