<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Deposit extends User_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('package_model', 'deposit_model', 'deposit_method_model'));
	}

	public function index()
	{
		$this->render('user/deposit_start', array(
			'page_title'  => 'Deposit',
			'active_menu' => 'deposit',
			'packages'    => $this->package_model->active(),
			'methods'     => $this->deposit_method_model->active(),
			'recent'      => $this->deposit_model->for_user($this->user->id, 5)['rows'],
		));
	}

	/**
	 * Submit proof of an off-platform USDT transfer. Nothing is credited here;
	 * an admin reviews the TXID and approves it.
	 */
	public function create($package_id)
	{
		if ($this->setting_model->get('deposit_enabled', '1') !== '1')
		{
			$this->session->set_flashdata('error', 'Deposits are temporarily disabled.');
			redirect('deposit');
		}

		$package = $this->package_model->find($package_id);

		if ( ! $package || $package->status !== 'active')
		{
			$this->session->set_flashdata('error', 'That package is not available.');
			redirect('packages');
		}

		$methods = $this->deposit_method_model->active();

		if (empty($methods))
		{
			$this->session->set_flashdata('error', 'No deposit wallet is configured yet. Please contact support.');
			redirect('deposit');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('deposit_method_id', 'Payment Wallet', 'required|integer');
			$this->form_validation->set_rules('txid', 'Transaction Hash', 'required|trim|min_length[10]|max_length[191]');

			if ($this->form_validation->run())
			{
				$method = $this->deposit_method_model->find($this->input->post('deposit_method_id'));

				if ( ! $method || $method->status !== 'active')
				{
					$this->session->set_flashdata('error', 'Pick a valid payment wallet.');
					redirect('deposit/create/'.$package->id);
				}

				$txid = $this->input->post('txid', TRUE);

				if ($this->deposit_model->txid_exists($txid))
				{
					$this->session->set_flashdata('error', 'That transaction hash has already been submitted.');
					redirect('deposit/create/'.$package->id);
				}

				$proof = NULL;
				if ( ! empty($_FILES['proof_image']['name']))
				{
					$this->load->library('uploader_lib');
					$proof = $this->uploader_lib->image('proof_image', 'deposits');

					if ($proof === FALSE)
					{
						$this->session->set_flashdata('error', 'Screenshot: '.$this->uploader_lib->error());
						redirect('deposit/create/'.$package->id);
					}
				}

				// The amount is fixed by the package price, so a tampered form
				// field cannot buy a plan for less than it costs.
				$deposit_id = $this->deposit_model->insert(array(
					'user_id'           => $this->user->id,
					'package_id'        => $package->id,
					'deposit_method_id' => $method->id,
					'amount'            => money_raw($package->price),
					'network'           => $method->network,
					'txid'              => $txid,
					'proof_image'       => $proof,
					'status'            => 'pending',
				));

				if ($deposit_id)
				{
					$this->session->set_flashdata('success', 'Deposit submitted. It will activate once an admin verifies the transaction.');
					redirect('deposit/history');
				}

				$this->session->set_flashdata('error', 'Could not submit the deposit. Please try again.');
			}
		}

		$this->render('user/deposit_create', array(
			'page_title'  => 'Deposit - '.$package->name,
			'active_menu' => 'deposit',
			'package'     => $package,
			'methods'     => $methods,
		));
	}

	public function history()
	{
		$per_page = 15;
		$page     = max(1, (int) $this->input->get('page'));
		$result   = $this->deposit_model->for_user($this->user->id, $per_page, ($page - 1) * $per_page);

		$this->render('user/deposit_history', array(
			'page_title'  => 'Deposit History',
			'active_menu' => 'deposit',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
		));
	}
}
