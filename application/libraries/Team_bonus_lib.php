<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Team volume bonus - the third income stream.
 *
 * Daily ad profit pays a user for their own investment. referral_commissions
 * pays them a percentage of one deposit made below them. This pays a flat
 * milestone bonus once the purchases made by their DIRECT referrals add up to
 * a target the admin sets.
 *
 * Rules, all confirmed with the client:
 *   - Direct referrals only. One hop up users.referred_by, no deeper.
 *   - A tier is measured either 'combined' (the whole team's volume summed) or
 *     'single' (the biggest single purchase one member made).
 *   - Volume is lifetime cumulative. It never resets, so clearing the $1,000
 *     tier leaves that same $1,000 counting toward the $5,000 one.
 *   - Each tier unlocks once per user, and the money only moves when the user
 *     presses Claim.
 *
 * The three counters on the user row are the source of truth for progress.
 * recompute() rebuilds them from `deposits` and runs nightly from the cron, so
 * an admin-adjusted deposit cannot leave a user's bar permanently wrong.
 */
class Team_bonus_lib {

	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
		$this->CI->load->model(array(
			'user_model', 'team_bonus_tier_model', 'team_bonus_claim_model',
			'notification_model', 'setting_model',
		));
		$this->CI->load->library('wallet_lib');
	}

	/** Master switch. Everything below is a no-op while this is off. */
	public function enabled()
	{
		return (string) $this->CI->setting_model->get('team_bonus_enabled', '1') === '1';
	}

	/* =================================================================
	 | Accrual
	 |================================================================= */

	/**
	 * Called from Investment_lib::approve_deposit(), inside its transaction.
	 *
	 * Moves the depositor's direct referrer forward by this purchase, then
	 * checks whether that crossed any milestone.
	 *
	 * @return bool TRUE when a referrer's counters were moved
	 */
	public function record_deposit($deposit)
	{
		if ( ! $this->enabled() || empty($deposit->user_id))
		{
			return FALSE;
		}

		$buyer = $this->CI->user_model->find($deposit->user_id);

		if ( ! $buyer || empty($buyer->referred_by))
		{
			return FALSE;
		}

		$referrer_id = (int) $buyer->referred_by;

		// A user cannot be their own upline, whatever the data says.
		if ($referrer_id === (int) $buyer->id)
		{
			return FALSE;
		}

		$amount = round((float) $deposit->amount, MONEY_SCALE);

		if ($amount <= 0)
		{
			return FALSE;
		}

		// Was this the buyer's first approved deposit? Counted before the
		// caller's own row is written in some flows, so the current deposit is
		// excluded explicitly and the head count derived from the rest.
		$earlier = (int) $this->CI->db
			->where('user_id', (int) $buyer->id)
			->where('status', 'approved')
			->where('id !=', (int) $deposit->id)
			->count_all_results('deposits');

		$db = $this->CI->db;

		// Single statement so two approvals landing together cannot both read
		// the same starting figure and lose one of the two increments.
		// The CAST is load-bearing: GREATEST() compares as strings the moment
		// one argument is a string, and '9' sorts above '100'.
		$db->set('team_volume', 'team_volume + '.$db->escape(money_raw($amount)), FALSE)
			->set('team_best_single',
				'GREATEST(team_best_single, CAST('.$db->escape(money_raw($amount)).' AS DECIMAL(18,8)))', FALSE);

		if ($earlier === 0)
		{
			$db->set('team_buyers', 'team_buyers + 1', FALSE);
		}

		$db->where('id', $referrer_id)->update('users');

		$this->sync_unlocks($referrer_id);

		return TRUE;
	}

	/* =================================================================
	 | Unlocking
	 |================================================================= */

	/**
	 * Compare the user's counters against every active tier and open a claim
	 * row for each newly cleared one.
	 *
	 * Called on deposit approval and again when the user opens their team
	 * bonus page, so lowering a target in admin unlocks it on the next page
	 * load rather than waiting for the team's next purchase.
	 *
	 * @return int how many tiers were unlocked by this call
	 */
	public function sync_unlocks($user_id)
	{
		if ( ! $this->enabled())
		{
			return 0;
		}

		$user = $this->CI->user_model->find($user_id);

		if ( ! $user)
		{
			return 0;
		}

		if ((string) $this->CI->setting_model->get('team_bonus_require_active_upline', '1') === '1'
			&& $user->status !== 'active')
		{
			return 0;
		}

		$existing = $this->CI->team_bonus_claim_model->map_for_user($user->id);
		$opened   = 0;

		foreach ($this->CI->team_bonus_tier_model->active_ladder() as $tier)
		{
			if (isset($existing[(int) $tier->id]))
			{
				continue;
			}

			$reached = $this->measure($user, $tier);

			if ($reached < (float) $tier->target_volume)
			{
				continue;
			}
			if ((int) $user->team_buyers < (int) $tier->min_referrals)
			{
				continue;
			}

			// The unique key is the real guard; a duplicate here just means a
			// concurrent approval got there first, which is fine.
			$this->CI->db->query(
				'INSERT IGNORE INTO team_bonus_claims
				 (user_id, tier_id, target_volume, bonus_amount, mode, volume_at_unlock, status, unlocked_at)
				 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
				array(
					(int) $user->id, (int) $tier->id,
					money_raw($tier->target_volume), money_raw($tier->bonus_amount),
					$tier->mode, money_raw($reached), 'unlocked', date('Y-m-d H:i:s'),
				)
			);

			if ($this->CI->db->affected_rows() < 1)
			{
				continue;
			}

			$opened++;

			$this->CI->notification_model->push(
				$user->id,
				'Team bonus unlocked',
				'Your team reached '.money($tier->target_volume).' - the '.$tier->name
					.' bonus of '.money($tier->bonus_amount).' is ready to claim.',
				'team-bonus'
			);
		}

		return $opened;
	}

	/** Which counter a tier is measured against. */
	protected function measure($user, $tier)
	{
		return ($tier->mode === 'single')
			? (float) $user->team_best_single
			: (float) $user->team_volume;
	}

	/* =================================================================
	 | Claiming
	 |================================================================= */

	/**
	 * Move an unlocked tier's bonus into the user's balance.
	 *
	 * The status flip is guarded by affected_rows() exactly as
	 * Investment_lib::credit_day_row() guards a daily payout: a double-clicked
	 * Claim button loses the race on the second request and pays nothing.
	 *
	 * @return array{ok:bool,message:string,amount:float}
	 */
	public function claim($user_id, $tier_id)
	{
		if ( ! $this->enabled())
		{
			return $this->result(FALSE, 'The team bonus programme is currently switched off.');
		}

		$claim = $this->CI->team_bonus_claim_model->for_user_tier($user_id, $tier_id);

		if ( ! $claim)
		{
			return $this->result(FALSE, 'You have not unlocked that bonus yet.');
		}
		if ($claim->status === 'claimed')
		{
			return $this->result(FALSE, 'That bonus has already been claimed.');
		}

		$amount = round((float) $claim->bonus_amount, MONEY_SCALE);

		if ($amount <= 0)
		{
			return $this->result(FALSE, 'That bonus has no amount attached. Contact support.');
		}

		$tier = $this->CI->team_bonus_tier_model->find($claim->tier_id);
		$name = $tier ? $tier->name : 'Team bonus';

		$db = $this->CI->db;

		// Manual transaction mode, unlike the rest of the app: the wallet
		// credit can decline for reasons that are not query errors, and only
		// trans_rollback() can undo the status flip when it does.
		$db->trans_begin();

		$db->where('id', $claim->id)->where('status', 'unlocked')
			->update('team_bonus_claims', array(
				'status'     => 'claimed',
				'claimed_at' => date('Y-m-d H:i:s'),
			));

		if ($db->affected_rows() < 1)
		{
			// Another request claimed it between the read above and here.
			$db->trans_rollback();
			return $this->result(FALSE, 'That bonus has already been claimed.');
		}

		$tx = $this->CI->wallet_lib->credit(
			$user_id, $amount, 'team_bonus',
			'team_bonus_claims', $claim->id,
			'Team bonus - '.$name
		);

		if ($tx === FALSE || $db->trans_status() === FALSE)
		{
			$db->trans_rollback();
			return $this->result(FALSE, 'Could not credit the bonus. Please try again.');
		}

		$db->where('id', $claim->id)->update('team_bonus_claims', array('transaction_id' => $tx));

		if ($db->trans_status() === FALSE)
		{
			$db->trans_rollback();
			return $this->result(FALSE, 'Database error while claiming. Nothing was changed.');
		}

		$db->trans_commit();

		return $this->result(TRUE, money($amount).' team bonus credited to your balance.', $amount);
	}

	protected function result($ok, $message, $amount = 0.0)
	{
		return array('ok' => $ok, 'message' => $message, 'amount' => (float) $amount);
	}

	/* =================================================================
	 | Progress, for the user panel and the API
	 |================================================================= */

	/**
	 * Everything the team bonus page, the dashboard card and the API endpoint
	 * need, in one shape.
	 *
	 * `next` is the unclaimed tier closest to completion by percentage, which
	 * is the one worth putting a headline bar on - not necessarily the
	 * cheapest, because a 'single' tier can sit nearer than a smaller
	 * 'combined' one.
	 */
	public function progress($user_id)
	{
		$user = $this->CI->user_model->find($user_id);

		$out = array(
			'enabled'         => $this->enabled(),
			'team_volume'     => $user ? (float) $user->team_volume      : 0.0,
			'best_single'     => $user ? (float) $user->team_best_single : 0.0,
			'team_buyers'     => $user ? (int)   $user->team_buyers      : 0,
			'tiers'           => array(),
			'next'            => NULL,
			'claimable_count' => 0,
			'claimable_total' => 0.0,
			'claimed_total'   => 0.0,
		);

		if ( ! $user || ! $out['enabled'])
		{
			return $out;
		}

		$claims = $this->CI->team_bonus_claim_model->map_for_user($user->id);

		foreach ($this->CI->team_bonus_tier_model->active_ladder() as $tier)
		{
			$claim   = isset($claims[(int) $tier->id]) ? $claims[(int) $tier->id] : NULL;
			$target  = (float) $tier->target_volume;
			$reached = $this->measure($user, $tier);

			// A claimed tier always reads 100%: its snapshot is what was
			// promised, and later edits to the tier must not un-finish it.
			$percent = $claim
				? 100
				: ($target > 0 ? min(100, floor($reached / $target * 100)) : 0);

			$row = array(
				'id'            => (int) $tier->id,
				'name'          => $tier->name,
				'mode'          => $tier->mode,
				'target'        => $target,
				'bonus'         => (float) $tier->bonus_amount,
				'min_referrals' => (int) $tier->min_referrals,
				'reached'       => $reached,
				'remaining'     => max(0, round($target - $reached, MONEY_SCALE)),
				'percent'       => (int) $percent,
				'status'        => $claim ? $claim->status : 'locked',
				'claimed_at'    => ($claim && $claim->status === 'claimed') ? $claim->claimed_at : NULL,
				// A tier can be at 100% on volume and still be held back by the
				// head-count gate, so the page can say which one is missing.
				'buyers_short'  => max(0, (int) $tier->min_referrals - (int) $user->team_buyers),
			);

			$out['tiers'][] = $row;

			if ($row['status'] === 'locked'
				&& ($out['next'] === NULL || $row['percent'] > $out['next']['percent']))
			{
				$out['next'] = $row;
			}
		}

		$out['claimable_count'] = $this->CI->team_bonus_claim_model->claimable_count($user->id);
		$out['claimable_total'] = $this->CI->team_bonus_claim_model->claimable_total($user->id);
		$out['claimed_total']   = $this->CI->team_bonus_claim_model->claimed_total($user->id);

		return $out;
	}

	/* =================================================================
	 | Drift repair
	 |================================================================= */

	/**
	 * Rebuild the three counters from `deposits`, which is the only authority.
	 *
	 * Needed because the counters are incremented on approval and nothing
	 * decrements them - an admin who edits or reverses an approved deposit
	 * directly in the database would otherwise leave a permanently inflated
	 * bar. Runs nightly from the cron.
	 *
	 * @param  int|null $user_id one referrer, or NULL for every user
	 * @return array{scanned:int,corrected:int}
	 */
	public function recompute($user_id = NULL)
	{
		$db     = $this->CI->db;
		$report = array('scanned' => 0, 'corrected' => 0);

		// Aggregate every referrer's team in one pass rather than per user.
		$db->select('r.referred_by AS referrer_id,
		             SUM(d.amount) AS volume,
		             MAX(d.amount) AS best_single,
		             COUNT(DISTINCT d.user_id) AS buyers', FALSE)
			->from('deposits d')
			->join('users r', 'r.id = d.user_id')
			->where('d.status', 'approved')
			->where('r.referred_by IS NOT NULL')
			->group_by('r.referred_by');

		if ($user_id !== NULL)
		{
			$db->where('r.referred_by', (int) $user_id);
		}

		$totals = array();
		foreach ($db->get()->result() as $row)
		{
			$totals[(int) $row->referrer_id] = $row;
		}

		// Every user who could hold a stale figure: those with fresh totals,
		// plus anyone still carrying a non-zero counter that no longer has any
		// approved deposits behind it.
		$db->select('id, team_volume, team_best_single, team_buyers');
		if ($user_id !== NULL)
		{
			$db->where('id', (int) $user_id);
		}
		else
		{
			$db->group_start()
				->where('team_volume >', 0)->or_where('team_best_single >', 0)->or_where('team_buyers >', 0)
				->group_end();
			if ( ! empty($totals))
			{
				$db->or_where_in('id', array_keys($totals));
			}
		}
		$users = $db->get('users')->result();

		foreach ($users as $u)
		{
			$report['scanned']++;

			$t = isset($totals[(int) $u->id]) ? $totals[(int) $u->id] : NULL;

			$volume = $t ? round((float) $t->volume, MONEY_SCALE)      : 0.0;
			$best   = $t ? round((float) $t->best_single, MONEY_SCALE) : 0.0;
			$buyers = $t ? (int) $t->buyers                            : 0;

			if (abs((float) $u->team_volume - $volume) < 0.00000001
				&& abs((float) $u->team_best_single - $best) < 0.00000001
				&& (int) $u->team_buyers === $buyers)
			{
				continue;
			}

			$this->CI->db->where('id', $u->id)->update('users', array(
				'team_volume'      => money_raw($volume),
				'team_best_single' => money_raw($best),
				'team_buyers'      => $buyers,
			));

			$report['corrected']++;

			// A correction upward can clear a milestone the counters had been
			// under-reporting, so re-check that user's ladder.
			$this->sync_unlocks($u->id);
		}

		return $report;
	}
}
