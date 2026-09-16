<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Every movement across the agent's three wallets, read-only.
 *
 * This is the agent-side answer to "where did my float go" - and, next to the
 * balance cards, the check that the two agree.
 */
class Ledger extends Agent_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->require_float();
		$this->load->model('agent_ledger_model');
		$this->load->library('agent_wallet_lib');
	}

	public function index()
	{
		$per_page = 25;
		$page     = max(1, (int) $this->input->get('page'));
		$wallet   = $this->input->get('wallet', TRUE) ?: '';

		if ( ! in_array($wallet, Agent_wallet_lib::WALLETS, TRUE))
		{
			$wallet = '';
		}

		$result = $this->agent_ledger_model->for_agent($this->agent->id, $per_page, ($page - 1) * $per_page, $wallet);

		$this->render('agent/ledger', array(
			'page_title'  => 'Wallet Ledger',
			'active_menu' => 'ledger',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'wallet'      => $wallet,
			'balances'    => $this->agent_wallet_lib->balances($this->agent->id),
		));
	}
}
