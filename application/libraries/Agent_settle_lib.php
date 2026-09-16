<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The float system's money moves.
 *
 * Four acts live here, and nowhere else:
 *
 *   settle_deposit()    agent accepts a user deposit: their float pays for it,
 *                       the user is credited and the plan activates
 *   pay_withdrawal()    agent has paid a user out of pocket: they collect the
 *                       amount into their withdraw wallet
 *   decline_deposit()   / decline_withdrawal()
 *                       agent hands the request back; it escalates to an admin
 *                       rather than being rejected outright, because the user
 *                       has already sent (or is owed) real money
 *   expire_stale()      the same escalation, on a timer
 *
 * Every one is idempotent and every one runs in a single transaction that
 * opens by locking the request row FOR UPDATE. A double-submitted Accept must
 * not spend the float twice, and two agents cannot race the same row.
 *
 * What this library deliberately does NOT do is re-implement approval. A
 * settled deposit goes through Investment_lib::approve_deposit() exactly as an
 * admin-approved one does, so the plan, the referral chain, the team bonus and
 * the old team-based agent commission all behave identically on both routes.
 */
class Agent_settle_lib {

	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
		$this->CI->load->model(array(
			'deposit_model', 'withdrawal_model', 'agent_model',
			'agent_commission_model', 'notification_model', 'setting_model',
		));
		$this->CI->load->library(array('agent_wallet_lib', 'investment_lib', 'wallet_lib'));
	}

	/* =================================================================
	 * Deposits
	 * ================================================================= */

	/**
	 * Agent accepts a deposit: float out, user balance in, plan active.
	 *
	 * @param  int $deposit_id
	 * @param  int $agent_id
	 * @return array ok, message
	 */
	public function settle_deposit($deposit_id, $agent_id)
	{
		$db = $this->CI->db;
		$db->trans_start();

		$deposit = $this->lock_deposit($deposit_id);

		if ( ! $deposit || (int) $deposit->agent_id !== (int) $agent_id)
		{
			$db->trans_complete();
			return $this->fail('That request is not yours.');
		}

		if ($deposit->agent_status !== 'pending' || $deposit->status !== 'pending')
		{
			$db->trans_complete();
			return $this->fail('This request has already been handled.');
		}

		$agent  = $this->CI->agent_model->find($agent_id);
		$amount = (float) $deposit->amount;

		if ( ! $this->CI->agent_wallet_lib->can_settle($agent, $amount))
		{
			$db->trans_complete();
			return $this->fail('Your deposit float is below '.money($amount).', or you are not accepting deposits.');
		}

		// Float first: if it cannot pay, nothing else should have happened.
		$spent = $this->CI->agent_wallet_lib->debit(
			$agent_id, 'deposit', $amount, 'deposit_settle',
			'deposits', $deposit->id, 'Settled deposit #'.$deposit->id
		);

		if ($spent === FALSE)
		{
			$db->trans_rollback();
			return $this->fail('Could not spend your float. Nothing was changed.');
		}

		// reviewed_by stays NULL: an agent is not an admin, and the admin_note
		// is what records who actually settled it.
		$result = $this->CI->investment_lib->approve_deposit(
			$deposit->id, NULL, 'Settled by agent '.$agent->username
		);

		if (empty($result['ok']))
		{
			$db->trans_rollback();
			return $this->fail($result['message']);
		}

		$commission = $this->pay_commission(
			$agent, 'agent_deposit', $deposit->id, $amount, $deposit->user_id,
			'commission_settle_percent', 'agent_deposit_commission_percent', '1',
			'Commission on deposit #'.$deposit->id
		);

		$this->CI->deposit_model->update($deposit->id, array(
			'agent_status'      => 'accepted',
			'agent_accepted_at' => date('Y-m-d H:i:s'),
			'agent_commission'  => money_raw($commission),
		));

		$db->trans_complete();

		if ($db->trans_status() === FALSE)
		{
			return $this->fail('Database error while settling. Nothing was changed.');
		}

		return array(
			'ok'         => TRUE,
			'commission' => $commission,
			'message'    => money($amount).' settled from your float'
				.($commission > 0 ? '. You earned '.money($commission).'.' : '.'),
		);
	}

	/**
	 * Agent hands a deposit back. The user has already sent money to the
	 * agent's address, so this is a dispute, not a rejection: the deposit
	 * stays pending and lands in the admin queue.
	 */
	public function decline_deposit($deposit_id, $agent_id, $note)
	{
		$deposit = $this->CI->deposit_model->find($deposit_id);

		if ( ! $deposit || (int) $deposit->agent_id !== (int) $agent_id)
		{
			return $this->fail('That request is not yours.');
		}

		if ($deposit->agent_status !== 'pending' || $deposit->status !== 'pending')
		{
			return $this->fail('This request has already been handled.');
		}

		$this->CI->deposit_model->update($deposit->id, array(
			'agent_status' => 'rejected',
			'agent_note'   => $note,
			'agent_reviewed_at' => date('Y-m-d H:i:s'),
		));

		$this->CI->notification_model->push(
			$deposit->user_id,
			'Deposit passed to support',
			'Your agent could not confirm this payment, so an admin is reviewing it directly.'
				.($note ? ' Agent note: '.$note : ''),
			'deposit/history'
		);

		return array('ok' => TRUE, 'message' => 'Request handed to an admin. Your float was not touched.');
	}

	/* =================================================================
	 * Withdrawals
	 * ================================================================= */

	/**
	 * Agent has paid the user from their own pocket and collects the amount.
	 *
	 * The user's balance was already debited when they made the request, so
	 * nothing moves on their side here - this closes the request and credits
	 * the agent.
	 *
	 * @param string $txid hash of the transfer the agent sent the user
	 */
	public function pay_withdrawal($withdrawal_id, $agent_id, $txid)
	{
		$db = $this->CI->db;
		$db->trans_start();

		$row = $this->lock_withdrawal($withdrawal_id);

		if ( ! $row || (int) $row->agent_id !== (int) $agent_id)
		{
			$db->trans_complete();
			return $this->fail('That request is not yours.');
		}

		if ($row->agent_status !== 'pending' || $row->status !== 'pending')
		{
			$db->trans_complete();
			return $this->fail('This request has already been handled.');
		}

		$agent = $this->CI->agent_model->find($agent_id);

		if ( ! $agent || $agent->status !== 'active')
		{
			$db->trans_complete();
			return $this->fail('Your agent account cannot take payments right now.');
		}

		// The agent collects exactly what they sent the user. The platform's
		// withdrawal fee was taken from the user at request time and is not
		// the agent's to collect.
		$collected = $this->CI->agent_wallet_lib->credit(
			$agent_id, 'withdraw', $row->net_amount, 'withdraw_settle',
			'withdrawals', $row->id, 'Paid withdrawal #'.$row->id
		);

		if ($collected === FALSE)
		{
			$db->trans_rollback();
			return $this->fail('Could not credit your withdraw wallet. Nothing was changed.');
		}

		$commission = $this->pay_commission(
			$agent, 'agent_withdraw', $row->id, (float) $row->amount, $row->user_id,
			'commission_withdraw_percent', 'agent_withdraw_commission_percent', '1',
			'Commission on withdrawal #'.$row->id
		);

		$this->CI->withdrawal_model->update($row->id, array(
			'status'           => 'paid',
			'txid'             => $txid,
			'agent_status'     => 'accepted',
			'agent_paid_at'    => date('Y-m-d H:i:s'),
			'agent_txid'       => $txid,
			'agent_commission' => money_raw($commission),
		));

		$db->trans_complete();

		if ($db->trans_status() === FALSE)
		{
			return $this->fail('Database error while recording the payment. Nothing was changed.');
		}

		$this->CI->notification_model->push(
			$row->user_id,
			'Withdrawal paid',
			money($row->net_amount).' has been sent to your wallet by your agent. TXID: '.$txid,
			'withdraw/history'
		);

		return array(
			'ok'         => TRUE,
			'commission' => $commission,
			'message'    => money($row->net_amount).' collected into your withdraw wallet'
				.($commission > 0 ? ', plus '.money($commission).' commission.' : '.'),
		);
	}

	/**
	 * Agent hands a withdrawal back. The user's balance stays held and an
	 * admin picks it up - releasing it here would let an agent cancel a payout
	 * they had already sent.
	 */
	public function decline_withdrawal($withdrawal_id, $agent_id, $note)
	{
		$row = $this->CI->withdrawal_model->find($withdrawal_id);

		if ( ! $row || (int) $row->agent_id !== (int) $agent_id)
		{
			return $this->fail('That request is not yours.');
		}

		if ($row->agent_status !== 'pending' || $row->status !== 'pending')
		{
			return $this->fail('This request has already been handled.');
		}

		$this->CI->withdrawal_model->update($row->id, array(
			'agent_status' => 'rejected',
			'agent_note'   => $note,
			'agent_reviewed_at' => date('Y-m-d H:i:s'),
		));

		$this->CI->notification_model->push(
			$row->user_id,
			'Withdrawal passed to support',
			'Your agent could not pay this request, so an admin is handling it directly.'
				.($note ? ' Agent note: '.$note : ''),
			'withdraw/history'
		);

		return array('ok' => TRUE, 'message' => 'Request handed to an admin.');
	}

	/* =================================================================
	 * Escalation
	 * ================================================================= */

	/**
	 * Marks every request an agent has left past the timeout as expired,
	 * which drops it into the admin queue.
	 *
	 * No balance moves: an unaccepted deposit never spent float and an unpaid
	 * withdrawal never credited anything, so expiry is a status change and two
	 * notifications.
	 *
	 * @return array counts, keyed deposits / withdrawals
	 */
	public function expire_stale()
	{
		$hours = (int) $this->CI->setting_model->get('agent_accept_timeout_hours', 6);
		$out   = array('deposits' => 0, 'withdrawals' => 0);

		if ($hours < 1)
		{
			return $out;
		}

		$cutoff = date('Y-m-d H:i:s', strtotime('-'.$hours.' hours'));
		$now    = date('Y-m-d H:i:s');

		foreach ($this->CI->deposit_model->stale_agent_requests($cutoff) as $d)
		{
			$this->CI->deposit_model->update($d->id, array(
				'agent_status'      => 'expired',
				'agent_reviewed_at' => $now,
			));

			$this->CI->notification_model->push(
				$d->user_id,
				'Deposit passed to support',
				'Your agent did not respond in time, so an admin is now reviewing your deposit.',
				'deposit/history'
			);

			$out['deposits']++;
		}

		foreach ($this->CI->withdrawal_model->stale_agent_requests($cutoff) as $w)
		{
			$this->CI->withdrawal_model->update($w->id, array(
				'agent_status'      => 'expired',
				'agent_reviewed_at' => $now,
			));

			$this->CI->notification_model->push(
				$w->user_id,
				'Withdrawal passed to support',
				'Your agent did not respond in time, so an admin is now handling your withdrawal.',
				'withdraw/history'
			);

			$out['withdrawals']++;
		}

		return $out;
	}

	/* =================================================================
	 * Internals
	 * ================================================================= */

	/**
	 * Books the agent's cut into their commission wallet.
	 *
	 * The ledger row carries wallet = 'commission', which is what separates
	 * these from the old team-based rows that still pay a linked user's main
	 * balance. The unique index on (agent_id, source, reference_id) is the
	 * real double-pay guard; paid_for() only avoids a failed insert.
	 *
	 * @return float amount credited, 0 when nothing was owed
	 */
	protected function pay_commission($agent, $source, $reference_id, $base, $user_id, $agent_field, $setting_key, $fallback, $description)
	{
		if ($base <= 0)
		{
			return 0;
		}

		if ($this->CI->agent_commission_model->paid_for($agent->id, $source, $reference_id))
		{
			return 0;
		}

		$percent = ($agent->{$agent_field} !== NULL && $agent->{$agent_field} !== '')
			? (float) $agent->{$agent_field}
			: (float) $this->CI->setting_model->get($setting_key, $fallback);

		if ($percent <= 0)
		{
			return 0;
		}

		$amount = round(($base * $percent) / 100, MONEY_SCALE);

		if ($amount <= 0)
		{
			return 0;
		}

		$commission_id = $this->CI->agent_commission_model->insert(array(
			'agent_id'     => $agent->id,
			'user_id'      => $user_id,
			'source'       => $source,
			'reference_id' => $reference_id,
			'base_amount'  => money_raw($base),
			'percent'      => $percent,
			'amount'       => money_raw($amount),
			'settled'      => 1,
			'wallet'       => 'commission',
		));

		if ( ! $commission_id)
		{
			return 0;
		}

		$credited = $this->CI->agent_wallet_lib->credit(
			$agent->id, 'commission', $amount, 'commission',
			'agent_commissions', $commission_id, $description
		);

		return ($credited === FALSE) ? 0 : $amount;
	}

	protected function lock_deposit($id)
	{
		return $this->CI->db->query(
			'SELECT id, user_id, package_id, amount, status, agent_id, agent_status FROM deposits WHERE id = ? FOR UPDATE',
			array((int) $id)
		)->row();
	}

	protected function lock_withdrawal($id)
	{
		return $this->CI->db->query(
			'SELECT id, user_id, amount, net_amount, fee, status, agent_id, agent_status FROM withdrawals WHERE id = ? FOR UPDATE',
			array((int) $id)
		)->row();
	}

	protected function fail($message)
	{
		return array('ok' => FALSE, 'message' => $message, 'commission' => 0);
	}
}
