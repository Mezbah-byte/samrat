<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The single choke point for agent balance changes.
 *
 * What Wallet_lib is for `users.balance`, this is for the three columns an
 * agent holds: `deposit_balance`, `withdraw_balance`, `commission_balance`.
 * Nothing else in the application may UPDATE them. Every movement here writes
 * a matching row into `agent_ledger` inside the same transaction, so
 * SUM(agent_ledger.amount) per (agent_id, wallet) always equals the stored
 * column - which is what reconcile() checks.
 *
 * The three wallets mean different things and are deliberately not fungible:
 *
 *   deposit     float bought from the admin. Spent settling user deposits.
 *   withdraw    collected by paying user withdrawals out of pocket. Cashed out.
 *   commission  earnings. Cashed out, or transferred into the float.
 *
 * Only transfer() moves value between them, and only in the one direction the
 * business rules allow.
 *
 * Callers wrap multi-step flows in $this->db->trans_start() / trans_complete();
 * CI3 tracks nesting depth, so the inner transaction here joins the outer one.
 */
class Agent_wallet_lib {

	/** @var CI_Controller */
	protected $CI;

	/** The three balance columns, keyed by the short name used everywhere. */
	const WALLETS = array('deposit', 'withdraw', 'commission');

	/** Types that move money in. */
	const CREDIT_TYPES = array(
		'float_purchase',   // admin approved a float order
		'deposit_refund',   // a settled deposit was reversed
		'withdraw_settle',  // agent paid a user withdrawal, collects the amount
		'commission',       // earned on a settled deposit or a paid withdrawal
		'payout_refund',    // admin rejected a cash-out, the hold comes back
		'transfer_in',      // the receiving half of transfer()
		'admin_credit',     // manual adjustment
	);

	/** Types that move money out. */
	const DEBIT_TYPES = array(
		'deposit_settle',   // agent accepted a user deposit, float is spent
		'withdraw_reverse', // a collected withdrawal was reversed
		'payout',           // cash-out requested; held until the admin pays
		'transfer_out',     // the sending half of transfer()
		'admin_debit',      // manual adjustment
	);

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
	}

	/**
	 * Add money to one of an agent's wallets.
	 *
	 * @param  int    $agent_id
	 * @param  string $wallet   one of self::WALLETS
	 * @return int|false ledger row id, or FALSE on failure
	 */
	public function credit($agent_id, $wallet, $amount, $type, $ref_table = NULL, $ref_id = NULL, $description = NULL)
	{
		if ( ! in_array($type, self::CREDIT_TYPES, TRUE))
		{
			log_message('error', 'Agent_wallet_lib::credit called with non-credit type: '.$type);
			return FALSE;
		}
		return $this->move($agent_id, $wallet, abs((float) $amount), $type, $ref_table, $ref_id, $description);
	}

	/**
	 * Remove money from one of an agent's wallets. Refuses to overdraw.
	 *
	 * The refusal is the whole point on the deposit wallet: an agent may not
	 * settle a deposit they have no float for.
	 *
	 * @return int|false ledger row id, or FALSE when funds are short
	 */
	public function debit($agent_id, $wallet, $amount, $type, $ref_table = NULL, $ref_id = NULL, $description = NULL, $allow_negative = FALSE)
	{
		if ( ! in_array($type, self::DEBIT_TYPES, TRUE))
		{
			log_message('error', 'Agent_wallet_lib::debit called with non-debit type: '.$type);
			return FALSE;
		}
		return $this->move($agent_id, $wallet, -abs((float) $amount), $type, $ref_table, $ref_id, $description, $allow_negative);
	}

	/**
	 * Move value between two of one agent's own wallets.
	 *
	 * Written as a debit plus a credit rather than one row, so the ledger
	 * still sums to each column independently. Only commission -> deposit is
	 * permitted: float must be bought from the admin, and collected
	 * withdrawals must be cashed out rather than recycled into float, or the
	 * platform's liability and the agent's float would drift apart.
	 *
	 * @return bool
	 */
	public function transfer($agent_id, $from_wallet, $to_wallet, $amount, $description = NULL)
	{
		$amount = round((float) $amount, MONEY_SCALE);

		if ($amount <= 0)
		{
			return FALSE;
		}

		if ($from_wallet !== 'commission' || $to_wallet !== 'deposit')
		{
			log_message('error', 'Agent_wallet_lib::transfer refused '.$from_wallet.' -> '.$to_wallet);
			return FALSE;
		}

		$description = $description ?: 'Commission moved into deposit float';

		$db = $this->CI->db;
		$db->trans_start();

		$out = $this->debit($agent_id, $from_wallet, $amount, 'transfer_out', 'agents', $agent_id, $description);

		if ($out === FALSE)
		{
			$db->trans_rollback();
			return FALSE;
		}

		$in = $this->credit($agent_id, $to_wallet, $amount, 'transfer_in', 'agents', $agent_id, $description);

		if ($in === FALSE)
		{
			$db->trans_rollback();
			return FALSE;
		}

		$db->trans_complete();

		return $db->trans_status() !== FALSE;
	}

	/**
	 * Applies one signed movement. Locks the agent row first so two concurrent
	 * requests cannot both read the same starting balance - the case that
	 * matters is two users paying the same agent at once, where an unlocked
	 * read would let the float be spent twice.
	 */
	protected function move($agent_id, $wallet, $signed, $type, $ref_table, $ref_id, $description, $allow_negative = FALSE)
	{
		$agent_id = (int) $agent_id;
		$signed   = round((float) $signed, MONEY_SCALE);

		if ( ! in_array($wallet, self::WALLETS, TRUE))
		{
			log_message('error', 'Agent_wallet_lib: unknown wallet '.$wallet);
			return FALSE;
		}

		if ($signed == 0.0)
		{
			return FALSE;
		}

		// Safe to interpolate: $wallet was just checked against the whitelist.
		$column = $wallet.'_balance';

		$db = $this->CI->db;

		$row = $db->query('SELECT `'.$column.'` AS bal FROM agents WHERE id = ? FOR UPDATE', array($agent_id))->row();

		if ( ! $row)
		{
			log_message('error', 'Agent_wallet_lib: agent '.$agent_id.' not found');
			return FALSE;
		}

		$before = (float) $row->bal;
		$after  = round($before + $signed, MONEY_SCALE);

		if ($after < 0 && ! $allow_negative)
		{
			log_message('error', 'Agent_wallet_lib: refused overdraw on '.$wallet.' for agent '.$agent_id.' ('.$before.' '.$signed.')');
			return FALSE;
		}

		$update = array($column => money_raw($after));

		// Lifetime commission counter, shown on the admin agent list. Kept on
		// the agent row for the same reason the user totals are: the listing
		// cannot afford a SUM() over the ledger per row.
		if ($type === 'commission')
		{
			$update['total_commission'] = money_raw((float) $this->column($agent_id, 'total_commission') + abs($signed));
		}

		$db->where('id', $agent_id)->update('agents', $update);

		$db->insert('agent_ledger', array(
			'agent_id'        => $agent_id,
			'wallet'          => $wallet,
			'type'            => $type,
			'amount'          => money_raw($signed),
			'balance_after'   => money_raw($after),
			'reference_table' => $ref_table,
			'reference_id'    => $ref_id,
			'description'     => $description !== NULL ? mb_substr($description, 0, 255) : NULL,
		));

		return (int) $db->insert_id();
	}

	protected function column($agent_id, $column)
	{
		$row = $this->CI->db->select($column)->where('id', (int) $agent_id)->get('agents')->row();
		return $row ? $row->{$column} : 0;
	}

	/** One wallet's current balance. */
	public function balance($agent_id, $wallet)
	{
		if ( ! in_array($wallet, self::WALLETS, TRUE))
		{
			return 0.0;
		}

		$row = $this->CI->db->select($wallet.'_balance AS bal')
			->where('id', (int) $agent_id)->get('agents')->row();

		return $row ? (float) $row->bal : 0.0;
	}

	/** All three at once, for the dashboard cards. */
	public function balances($agent_id)
	{
		$row = $this->CI->db->select('deposit_balance, withdraw_balance, commission_balance')
			->where('id', (int) $agent_id)->get('agents')->row();

		if ( ! $row)
		{
			return array('deposit' => 0.0, 'withdraw' => 0.0, 'commission' => 0.0);
		}

		return array(
			'deposit'    => (float) $row->deposit_balance,
			'withdraw'   => (float) $row->withdraw_balance,
			'commission' => (float) $row->commission_balance,
		);
	}

	/**
	 * Can this agent settle a deposit of this size right now?
	 *
	 * The three conditions are asked together because they are always asked
	 * together: the user-facing agent picker filters on exactly this, and the
	 * accept action re-checks it rather than trusting the picker.
	 */
	public function can_settle($agent, $amount)
	{
		if ( ! $agent || $agent->status !== 'active' || ! (int) $agent->accepting_deposits)
		{
			return FALSE;
		}

		return (float) $agent->deposit_balance >= round((float) $amount, MONEY_SCALE);
	}

	/**
	 * Integrity check: does the ledger still add up to the stored column?
	 * Surfaced on the admin agent detail screen, mirroring Wallet_lib.
	 */
	public function reconcile($agent_id, $wallet)
	{
		if ( ! in_array($wallet, self::WALLETS, TRUE))
		{
			return NULL;
		}

		$row = $this->CI->db->select_sum('amount', 'total')
			->where('agent_id', (int) $agent_id)
			->where('wallet', $wallet)
			->get('agent_ledger')->row();

		$ledger  = round((float) ($row->total ?: 0), MONEY_SCALE);
		$balance = round($this->balance($agent_id, $wallet), MONEY_SCALE);

		return array(
			'wallet'   => $wallet,
			'ledger'   => $ledger,
			'balance'  => $balance,
			'drift'    => round($balance - $ledger, MONEY_SCALE),
			'balanced' => (abs($balance - $ledger) < 0.00000001),
		);
	}

	/** reconcile() across all three wallets. */
	public function reconcile_all($agent_id)
	{
		$out = array();

		foreach (self::WALLETS as $wallet)
		{
			$out[$wallet] = $this->reconcile($agent_id, $wallet);
		}

		return $out;
	}
}
