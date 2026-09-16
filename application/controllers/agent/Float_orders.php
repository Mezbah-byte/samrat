<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Buying float from the platform.
 *
 * The agent sends USDT to a company wallet off-platform and submits the hash;
 * an admin verifies it and credits the deposit wallet. Nothing here touches a
 * balance - this controller only records the claim, exactly as the user-side
 * Deposit controller does.
 */
class Float_orders extends Agent_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->require_float();
		$this->load->model(array('agent_float_order_model', 'deposit_method_model'));
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';

		if ( ! in_array($status, array('', 'pending', 'approved', 'rejected'), TRUE))
		{
			$status = '';
		}

		$result = $this->agent_float_order_model->for_agent($this->agent->id, $per_page, ($page - 1) * $per_page, $status);

		$this->render('agent/float_orders', array(
			'page_title'  => 'Buy Float',
			'active_menu' => 'float',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'methods'     => $this->deposit_method_model->active(),
			'balance'     => (float) $this->agent->deposit_balance,
		));
	}

	/** Records an off-platform transfer to a company wallet. */
	public function create()
	{
		$methods = $this->deposit_method_model->active();

		if (empty($methods))
		{
			$this->session->set_flashdata('error', 'No company wallet is configured yet. Contact the admin.');
			redirect('agent/float');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('deposit_method_id', 'Company Wallet', 'required|integer');
			$this->form_validation->set_rules('amount', 'Amount', 'required|numeric|greater_than[0]');
			$this->form_validation->set_rules('txid', 'Transaction Hash', 'required|trim|min_length[10]|max_length[191]');

			if ($this->form_validation->run())
			{
				$method = $this->deposit_method_model->find($this->input->post('deposit_method_id'));

				if ( ! $method || $method->status !== 'active')
				{
					$this->session->set_flashdata('error', 'Pick a valid company wallet.');
					redirect('agent/float/create');
				}

				$amount = round((float) $this->input->post('amount'), MONEY_SCALE);

				if ($amount < (float) $method->min_amount)
				{
					$this->session->set_flashdata('error', 'The minimum for this wallet is '.money($method->min_amount).'.');
					redirect('agent/float/create');
				}

				$txid = $this->input->post('txid', TRUE);

				if ($this->agent_float_order_model->txid_exists($txid))
				{
					$this->session->set_flashdata('error', 'That transaction hash has already been submitted.');
					redirect('agent/float/create');
				}

				$proof = NULL;
				if ( ! empty($_FILES['proof_image']['name']))
				{
					$this->load->library('uploader_lib');
					$proof = $this->uploader_lib->image('proof_image', 'deposits');

					if ($proof === FALSE)
					{
						$this->session->set_flashdata('error', 'Screenshot: '.$this->uploader_lib->error());
						redirect('agent/float/create');
					}
				}

				$order_id = $this->agent_float_order_model->insert(array(
					'agent_id'          => $this->agent->id,
					'deposit_method_id' => $method->id,
					'amount'            => money_raw($amount),
					'network'           => $method->network,
					'txid'              => $txid,
					'proof_image'       => $proof,
					'status'            => 'pending',
				));

				if ($order_id)
				{
					$this->log_action('Requested float', 'agent_float_orders', $order_id, money($amount));
					$this->session->set_flashdata('success', 'Float order submitted. Your balance updates once an admin verifies the transfer.');
					redirect('agent/float');
				}

				$this->session->set_flashdata('error', 'Could not submit the order. Please try again.');
			}
		}

		$this->render('agent/float_create', array(
			'page_title'  => 'Buy Float',
			'active_menu' => 'float',
			'methods'     => $methods,
			'balance'     => (float) $this->agent->deposit_balance,
		));
	}

	public function view($id)
	{
		$order = $this->agent_float_order_model->find_for_agent($id, $this->agent->id);

		if ( ! $order)
		{
			show_404();
		}

		$this->load->model('deposit_method_model');
		$method = $order->deposit_method_id ? $this->deposit_method_model->find($order->deposit_method_id) : NULL;

		$this->render('agent/float_view', array(
			'page_title'  => 'Float Order #'.$order->id,
			'active_menu' => 'float',
			'order'       => $order,
			'method'      => $method,
		));
	}
}
