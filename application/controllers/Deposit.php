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
	 * Submit proof of an off-platform USDT transfer.
	 *
	 * Two routes land here, chosen by the `deposit_route` setting:
	 *
	 *   admin  the user pays a platform wallet and an admin approves on-chain
	 *   agent  the user pays an agent's own wallet; that agent accepts and
	 *          their float pays for the plan on the spot
	 *
	 * Nothing is credited either way - this only records the claim.
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

		$route   = $this->route();
		$methods = in_array($route, array('admin', 'both'), TRUE) ? $this->deposit_method_model->active() : array();
		$agents  = in_array($route, array('agent', 'both'), TRUE) ? $this->available_agents($package->price) : array();

		if (empty($methods) && empty($agents))
		{
			$this->session->set_flashdata('error', $route === 'agent'
				? 'No agent has enough float to take your deposit right now. Please try again shortly.'
				: 'No deposit wallet is configured yet. Please contact support.');
			redirect('deposit');
		}

		if ($this->input->method() === 'post')
		{
			$via = $this->input->post('pay_via', TRUE);

			// The route setting decides what is on offer, not the posted field.
			if ($via === 'agent' && ! empty($agents))
			{
				$this->submit_via_agent($package, $agents);
			}
			elseif ( ! empty($methods))
			{
				$this->submit_via_admin($package);
			}
			else
			{
				$this->session->set_flashdata('error', 'That payment route is not available.');
				redirect('deposit/create/'.$package->id);
			}
		}

		$this->render('user/deposit_create', array(
			'page_title'  => 'Deposit - '.$package->name,
			'active_menu' => 'deposit',
			'package'     => $package,
			'methods'     => $methods,
			'agents'      => $agents,
			'route'       => $route,
		));
	}

	/* ----------------------------------------------------------------
	 * Routes
	 * ---------------------------------------------------------------- */

	/** The original path: pay a platform wallet, an admin verifies it. */
	protected function submit_via_admin($package)
	{
		$this->form_validation->set_rules('deposit_method_id', 'Payment Wallet', 'required|integer');
		$this->form_validation->set_rules('txid', 'Transaction Hash', 'required|trim|min_length[10]|max_length[191]');

		if ( ! $this->form_validation->run())
		{
			return;
		}

		$method = $this->deposit_method_model->find($this->input->post('deposit_method_id'));

		if ( ! $method || $method->status !== 'active')
		{
			$this->session->set_flashdata('error', 'Pick a valid payment wallet.');
			redirect('deposit/create/'.$package->id);
		}

		$txid  = $this->checked_txid($package);
		$proof = $this->uploaded_proof($package);

		// The amount is fixed by the package price, so a tampered form field
		// cannot buy a plan for less than it costs.
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

		if ( ! $deposit_id)
		{
			$this->session->set_flashdata('error', 'Could not submit the deposit. Please try again.');
			return;
		}

		$this->session->set_flashdata('success', 'Deposit submitted. It will activate once an admin verifies the transaction.');
		redirect('deposit/history');
	}

	/** Pay a chosen agent's wallet; that agent settles it from their float. */
	protected function submit_via_agent($package, $agents)
	{
		$this->form_validation->set_rules('agent_id', 'Agent', 'required|integer');
		$this->form_validation->set_rules('agent_wallet_id', 'Agent Wallet', 'required|integer');
		$this->form_validation->set_rules('txid', 'Transaction Hash', 'required|trim|min_length[10]|max_length[191]');

		if ( ! $this->form_validation->run())
		{
			return;
		}

		$agent_id = (int) $this->input->post('agent_id');

		// Re-derive from the list this request built rather than trusting the
		// posted id: an agent whose float dropped since the page rendered must
		// not be selectable.
		$agent = isset($agents[$agent_id]) ? $agents[$agent_id] : NULL;

		if ( ! $agent)
		{
			$this->session->set_flashdata('error', 'That agent can no longer take this deposit. Pick another.');
			redirect('deposit/create/'.$package->id);
		}

		$this->load->model('agent_wallet_model');
		$wallet = $this->agent_wallet_model->find_active_for_agent($this->input->post('agent_wallet_id'), $agent_id);

		if ( ! $wallet)
		{
			$this->session->set_flashdata('error', 'Pick a valid wallet for that agent.');
			redirect('deposit/create/'.$package->id);
		}

		$price = (float) $package->price;

		if ($price < (float) $wallet->min_amount
			|| ((float) $wallet->max_amount > 0 && $price > (float) $wallet->max_amount))
		{
			$this->session->set_flashdata('error', 'That wallet does not accept '.money($price).'. Pick another.');
			redirect('deposit/create/'.$package->id);
		}

		$txid  = $this->checked_txid($package);
		$proof = $this->uploaded_proof($package);

		$deposit_id = $this->deposit_model->insert(array(
			'user_id'         => $this->user->id,
			'package_id'      => $package->id,
			'amount'          => money_raw($price),
			'network'         => $wallet->network,
			'txid'            => $txid,
			'proof_image'     => $proof,
			'status'          => 'pending',
			'agent_id'        => $agent_id,
			'agent_wallet_id' => $wallet->id,
			'agent_status'    => 'pending',
		));

		if ( ! $deposit_id)
		{
			$this->session->set_flashdata('error', 'Could not submit the deposit. Please try again.');
			return;
		}

		$this->notify_agent($agent, 'New deposit request',
			money($price).' is waiting for you to confirm and settle.', 'agent/requests/deposit/'.$deposit_id);

		$hours = (int) $this->setting_model->get('agent_accept_timeout_hours', 6);

		$this->session->set_flashdata('success',
			'Deposit submitted to '.$agent->username.'. Your plan activates as soon as they confirm the payment'
			.($hours > 0 ? ' - if they do not respond within '.$hours.' hours an admin takes it over.' : '.'));
		redirect('deposit/history');
	}

	/* ----------------------------------------------------------------
	 * Shared
	 * ---------------------------------------------------------------- */

	/** Which routes are on offer right now. */
	protected function route()
	{
		if ($this->setting_model->get('agent_float_enabled', '0') !== '1')
		{
			return 'admin';
		}

		$route = $this->setting_model->get('deposit_route', 'admin');

		return in_array($route, array('admin', 'agent', 'both'), TRUE) ? $route : 'admin';
	}

	/**
	 * Agents who can take a deposit of this size, keyed by id.
	 *
	 * An agent with float but no active wallet has nowhere for the user to
	 * send the money, so they are dropped here rather than offered and then
	 * failing at the next step.
	 *
	 * @return array agent id => agent row, each carrying a `wallets` array
	 */
	protected function available_agents($amount)
	{
		$this->load->model(array('agent_model', 'agent_wallet_model'));

		$agents = $this->agent_model->available_for_deposit(
			$amount, (float) $this->setting_model->get('agent_min_float', 0)
		);

		if (empty($agents))
		{
			return array();
		}

		$ids     = array_map(function ($a) { return (int) $a->id; }, $agents);
		$wallets = $this->agent_wallet_model->active_for_agents($ids);
		$out     = array();

		foreach ($agents as $agent)
		{
			$id = (int) $agent->id;

			if (empty($wallets[$id]))
			{
				continue;
			}

			$agent->wallets = $wallets[$id];
			$out[$id]       = $agent;
		}

		return $out;
	}

	/** Rejects a reused hash before anything is written. */
	protected function checked_txid($package)
	{
		$txid = $this->input->post('txid', TRUE);

		if ($this->deposit_model->txid_exists($txid))
		{
			$this->session->set_flashdata('error', 'That transaction hash has already been submitted.');
			redirect('deposit/create/'.$package->id);
		}

		return $txid;
	}

	protected function uploaded_proof($package)
	{
		if (empty($_FILES['proof_image']['name']))
		{
			return NULL;
		}

		$this->load->library('uploader_lib');
		$proof = $this->uploader_lib->image('proof_image', 'deposits');

		if ($proof === FALSE)
		{
			$this->session->set_flashdata('error', 'Screenshot: '.$this->uploader_lib->error());
			redirect('deposit/create/'.$package->id);
		}

		return $proof;
	}

	/**
	 * Agents read notifications through their linked user account, so a
	 * standalone agent gets none - they see the request in their panel.
	 */
	protected function notify_agent($agent, $title, $body, $link)
	{
		$this->load->model('agent_model');
		$full = $this->agent_model->find($agent->id);

		if ($full && $full->user_id)
		{
			$this->notification_model->push($full->user_id, $title, $body, $link);
		}
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
