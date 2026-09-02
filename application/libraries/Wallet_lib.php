<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The single choke point for balance changes.
 *
 * Nothing else in the application may UPDATE users.balance. Every movement
 * here writes a matching row into `transactions` inside the same transaction,
 * so SUM(transactions.amount) always equals users.balance.
 *
 * Callers wrap multi-step flows in $this->db->trans_start() / trans_complete();
 * CI3 tracks nesting depth, so the inner transaction here joins the outer one.
 */
class Wallet_lib {

	/** @var CI_Controller */
	protected $CI;

	/** Types that move money in. */
	const CREDIT_TYPES = array('deposit', 'daily_profit', 'referral_bonus', 'refund', 'admin_credit', 'agent_commission');

	/** Types that move money out. */
	const DEBIT_TYPES = array('investment', 'withdrawal', 'withdrawal_fee', 'admin_debit');

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
	}

	/**
	 * Add money to a user's balance.
	 *
	 * @return int|false transaction id, or FALSE on failure
	 */
	public function credit($user_id, $amount, $type, $ref_table = NULL, $ref_id = NULL, $description = NULL)
	{
		if ( ! in_array($type, self::CREDIT_TYPES, TRUE))
		{
			log_message('error', 'Wallet_lib::credit called with non-credit type: '.$type);
			return FALSE;
		}
		return $this->move($user_id, abs((float) $amount), $type, $ref_table, $ref_id, $description);
	}

	/**
	 * Remove money from a user's balance. Refuses to overdraw.
	 *
	 * @return int|false transaction id, or FALSE when funds are short
	 */
	public function debit($user_id, $amount, $type, $ref_table = NULL, $ref_id = NULL, $description = NULL, $allow_negative = FALSE)
	{
		if ( ! in_array($type, self::DEBIT_TYPES, TRUE))
		{
			log_message('error', 'Wallet_lib::debit called with non-debit type: '.$type);
			return FALSE;
		}
		return $this->move($user_id, -abs((float) $amount), $type, $ref_table, $ref_id, $description, $allow_negative);
	}

	/**
	 * Applies one signed movement. Locks the user row first so two concurrent
	 * requests cannot both read the same starting balance.
	 */
	protected function move($user_id, $signed, $type, $ref_table, $ref_id, $description, $allow_negative = FALSE)
	{
		$user_id = (int) $user_id;
		$signed  = round((float) $signed, MONEY_SCALE);

		if ($signed == 0.0)
		{
			return FALSE;
		}

		$db = $this->CI->db;

		$row = $db->query('SELECT balance FROM users WHERE id = ? FOR UPDATE', array($user_id))->row();
		if ( ! $row)
		{
			log_message('error', 'Wallet_lib: user '.$user_id.' not found');
			return FALSE;
		}

		$before = (float) $row->balance;
		$after  = round($before + $signed, MONEY_SCALE);

		if ($after < 0 && ! $allow_negative)
		{
			log_message('error', 'Wallet_lib: refused overdraw for user '.$user_id.' ('.$before.' '.$signed.')');
			return FALSE;
		}

		$update = array('balance' => money_raw($after));

		// Lifetime aggregates. Kept on the user row so dashboards do not have
		// to scan the whole ledger on every page load.
		switch ($type)
		{
			case 'deposit':
				$update['total_deposit'] = money_raw((float) $this->column($user_id, 'total_deposit') + abs($signed));
				break;
			case 'daily_profit':
				$update['total_earned'] = money_raw((float) $this->column($user_id, 'total_earned') + abs($signed));
				break;
			case 'referral_bonus':
				$update['total_referral_bonus'] = money_raw((float) $this->column($user_id, 'total_referral_bonus') + abs($signed));
				break;
			case 'withdrawal':
				$update['total_withdrawn'] = money_raw((float) $this->column($user_id, 'total_withdrawn') + abs($signed));
				break;
			case 'refund':
				// A refund reverses a held withdrawal, so the lifetime figure
				// must come back down with it.
				$current = (float) $this->column($user_id, 'total_withdrawn');
				$update['total_withdrawn'] = money_raw(max(0, $current - abs($signed)));
				break;
		}

		$db->where('id', $user_id)->update('users', $update);

		$db->insert('transactions', array(
			'user_id'         => $user_id,
			'type'            => $type,
			'amount'          => money_raw($signed),
			'balance_after'   => money_raw($after),
			'reference_table' => $ref_table,
			'reference_id'    => $ref_id,
			'description'     => $description !== NULL ? mb_substr($description, 0, 255) : NULL,
		));

		return (int) $db->insert_id();
	}

	protected function column($user_id, $column)
	{
		$row = $this->CI->db->select($column)->where('id', (int) $user_id)->get('users')->row();
		return $row ? $row->{$column} : 0;
	}

	public function balance($user_id)
	{
		$row = $this->CI->db->select('balance')->where('id', (int) $user_id)->get('users')->row();
		return $row ? (float) $row->balance : 0.0;
	}

	/**
	 * Integrity check: does the ledger still add up to the stored balance?
	 * Surfaced on the admin user detail screen.
	 */
	public function reconcile($user_id)
	{
		$row = $this->CI->db->select_sum('amount', 'total')
			->where('user_id', (int) $user_id)->get('transactions')->row();
		$ledger  = round((float) ($row->total ?: 0), MONEY_SCALE);
		$balance = round($this->balance($user_id), MONEY_SCALE);

		return array(
			'ledger'   => $ledger,
			'balance'  => $balance,
			'drift'    => round($balance - $ledger, MONEY_SCALE),
			'balanced' => (abs($balance - $ledger) < 0.00000001),
		);
	}
}
