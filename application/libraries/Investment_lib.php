<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Deposit approval, the ads-unlock-profit rule, and the daily cron.
 *
 * Earning model (confirmed with the client):
 *   - An approved deposit starts one investment for `duration_days` calendar
 *     days: start_date .. start_date + duration_days - 1.
 *   - Each day the user must watch `daily_ads` ads. Meeting the quota credits
 *     that day's `daily_amount`.
 *   - A day whose quota is not met by midnight is lost. The end date does not
 *     move, so a missed day is permanently forfeited income.
 */
class Investment_lib {

	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
		$this->CI->load->model(array(
			'user_model', 'package_model', 'deposit_model', 'investment_model',
			'daily_earning_model', 'referral_model', 'referral_level_model',
			'ad_model', 'notification_model', 'setting_model',
		));
		$this->CI->load->library(array('wallet_lib', 'agent_lib'));
	}

	/* =================================================================
	 | Deposit approval
	 |================================================================= */

	/**
	 * Approve a pending deposit: credit the balance, open the investment, and
	 * pay the referrer. All of it lands in one DB transaction.
	 *
	 * @return array{ok:bool,message:string,investment_id:?int}
	 */
	public function approve_deposit($deposit_id, $admin_id, $note = NULL)
	{
		$deposit = $this->CI->deposit_model->find($deposit_id);

		if ( ! $deposit)
		{
			return array('ok' => FALSE, 'message' => 'Deposit not found.', 'investment_id' => NULL);
		}
		if ($deposit->status !== 'pending')
		{
			return array('ok' => FALSE, 'message' => 'This deposit has already been '.$deposit->status.'.', 'investment_id' => NULL);
		}

		$package = $this->CI->package_model->find($deposit->package_id);
		if ( ! $package)
		{
			return array('ok' => FALSE, 'message' => 'The package attached to this deposit no longer exists.', 'investment_id' => NULL);
		}

		$db = $this->CI->db;
		$db->trans_start();

		$db->where('id', $deposit->id)->update('deposits', array(
			'status'      => 'approved',
			'admin_note'  => $note,
			'reviewed_by' => $admin_id,
			'reviewed_at' => date('Y-m-d H:i:s'),
		));

		$this->CI->wallet_lib->credit(
			$deposit->user_id, $deposit->amount, 'deposit',
			'deposits', $deposit->id, 'Deposit approved - '.$package->name
		);

		$start = date('Y-m-d');
		$end   = date('Y-m-d', strtotime($start.' +'.((int) $package->duration_days - 1).' days'));

		$daily_amount = round(((float) $deposit->amount * (float) $package->daily_return_percent) / 100, MONEY_SCALE);

		$investment_id = $this->CI->investment_model->insert(array(
			'user_id'              => $deposit->user_id,
			'package_id'           => $package->id,
			'deposit_id'           => $deposit->id,
			'amount'               => money_raw($deposit->amount),
			'daily_return_percent' => $package->daily_return_percent,
			'daily_amount'         => money_raw($daily_amount),
			'daily_ads'            => $package->daily_ads,
			'duration_days'        => $package->duration_days,
			'start_date'           => $start,
			'end_date'             => $end,
			'status'               => 'active',
		));

		// The deposit is spent on the package, so it is not withdrawable cash.
		$this->CI->wallet_lib->debit(
			$deposit->user_id, $deposit->amount, 'investment',
			'investments', $investment_id, 'Purchased package - '.$package->name
		);

		$this->pay_referral_commission($deposit);

		// The agent above this user, if any, earns on the same event. Inside
		// the same transaction, so a failure rolls the whole approval back.
		$this->CI->agent_lib->pay_deposit_commission($deposit);

		// Day 1 opens immediately so the user can start watching ads today.
		$this->ensure_day_row($investment_id, $deposit->user_id, $start, $daily_amount, $package->daily_ads);

		$db->trans_complete();

		if ($db->trans_status() === FALSE)
		{
			return array('ok' => FALSE, 'message' => 'Database error while approving the deposit. Nothing was changed.', 'investment_id' => NULL);
		}

		$this->CI->notification_model->push(
			$deposit->user_id,
			'Deposit approved',
			'Your '.money($deposit->amount).' deposit was approved and your '.$package->name
				.' plan is now active. Watch '.$package->daily_ads.' ads every day to unlock '
				.money($daily_amount).' daily.',
			'dashboard'
		);

		return array('ok' => TRUE, 'message' => 'Deposit approved and plan activated.', 'investment_id' => $investment_id);
	}

	public function reject_deposit($deposit_id, $admin_id, $note = NULL)
	{
		$deposit = $this->CI->deposit_model->find($deposit_id);

		if ( ! $deposit)
		{
			return array('ok' => FALSE, 'message' => 'Deposit not found.');
		}
		if ($deposit->status !== 'pending')
		{
			return array('ok' => FALSE, 'message' => 'This deposit has already been '.$deposit->status.'.');
		}

		$this->CI->deposit_model->update($deposit->id, array(
			'status'      => 'rejected',
			'admin_note'  => $note,
			'reviewed_by' => $admin_id,
			'reviewed_at' => date('Y-m-d H:i:s'),
		));

		$this->CI->notification_model->push(
			$deposit->user_id,
			'Deposit rejected',
			'Your '.money($deposit->amount).' deposit was rejected.'.($note ? ' Reason: '.$note : ''),
			'deposit/history'
		);

		return array('ok' => TRUE, 'message' => 'Deposit rejected. No balance was moved.');
	}

	/**
	 * One-time commission on the deposit amount, paid up the referral tree.
	 *
	 * Generation 1 is the depositor's own referrer, generation 2 the person who
	 * referred them, and so on for as many rows as `referral_levels` holds. The
	 * rates, which generations are switched on, and the two eligibility rules
	 * all come out of the database - nothing here is hard-coded.
	 *
	 * The walk climbs every generation that exists, not only the paying ones, so
	 * switching generation 2 off leaves 1 and 3 earning instead of cutting the
	 * tree short at 2. A generation with no rate is skipped silently.
	 *
	 * The unique index on (deposit_id, level) is the real guarantee against
	 * paying the same generation twice.
	 *
	 * @return int how many generations were paid
	 */
	protected function pay_referral_commission($deposit)
	{
		$user = $this->CI->user_model->find($deposit->user_id);

		if ( ! $user || ! $user->referred_by)
		{
			return 0;
		}

		$rates = $this->CI->referral_level_model->active_map();
		$depth = $this->CI->referral_level_model->max_level();

		if (empty($rates) || $depth < 1)
		{
			return 0;
		}

		$require_active   = (int) $this->CI->setting_model->get('referral_require_active_upline', 1) === 1;
		$require_invested = (int) $this->CI->setting_model->get('referral_require_upline_investment', 0) === 1;

		$paid    = 0;
		$current = $user;
		// A referred_by cycle would otherwise spin until the level cap. Seeded
		// with the depositor, so a tree pointing back at them also stops.
		$seen    = array((int) $user->id => TRUE);

		for ($level = 1; $level <= $depth; $level++)
		{
			if (empty($current->referred_by))
			{
				break;
			}

			$upline_id = (int) $current->referred_by;

			if (isset($seen[$upline_id]))
			{
				break;
			}
			$seen[$upline_id] = TRUE;

			$upline = $this->CI->user_model->find($upline_id);
			if ( ! $upline)
			{
				break;
			}

			$current = $upline;

			if ( ! isset($rates[$level]))
			{
				continue;
			}
			if ($require_active && $upline->status !== 'active')
			{
				continue;
			}
			if ($require_invested && ! $this->CI->investment_model->active_for_user($upline->id))
			{
				continue;
			}
			if ($this->CI->referral_model->paid_for_deposit($deposit->id, $level))
			{
				continue;
			}

			$percent = (float) $rates[$level];
			$amount  = round(((float) $deposit->amount * $percent) / 100, MONEY_SCALE);

			if ($amount <= 0)
			{
				continue;
			}

			$commission_id = $this->CI->referral_model->insert(array(
				'referrer_id' => $upline->id,
				'referred_id' => $user->id,
				'level'       => $level,
				'deposit_id'  => $deposit->id,
				'percent'     => $percent,
				'amount'      => money_raw($amount),
			));

			$this->CI->wallet_lib->credit(
				$upline->id, $amount, 'referral_bonus',
				'referral_commissions', $commission_id,
				$percent.'% generation '.$level.' commission from '.$user->username
			);

			$this->CI->notification_model->push(
				$upline->id,
				'Referral commission',
				'You earned '.money($amount).' ('.$percent.'%, generation '.$level.') from '
					.$user->username.'\'s deposit.',
				'referral'
			);

			$paid++;
		}

		return $paid;
	}

	/* =================================================================
	 | Ads -> daily profit
	 |================================================================= */

	/**
	 * Record one ad view and credit any day rows whose quota is now met.
	 * A single view counts toward every active investment the user holds.
	 *
	 * @return array{ok:bool,message:string,credited:float,watched:int,required:int}
	 */
	public function register_ad_view($user_id, $ad_id)
	{
		$today = date('Y-m-d');
		$db    = $this->CI->db;

		// The off day is enforced here, not only in the ad list: the list is a
		// page render, this is the only thing a POST can reach.
		if (is_off_day($today))
		{
			return $this->ad_result(FALSE, $this->off_day_message($today), 0, $user_id);
		}

		$ad = $this->CI->ad_model->find($ad_id);
		if ( ! $ad || $ad->status !== 'active')
		{
			return $this->ad_result(FALSE, 'That ad is not available.', 0, $user_id);
		}

		$active = $this->CI->investment_model->active_for_user($user_id);
		if (empty($active))
		{
			return $this->ad_result(FALSE, 'You need an active plan before watching ads.', 0, $user_id);
		}

		$already = (int) $db->where('user_id', (int) $user_id)->where('ad_id', (int) $ad_id)
			->where('view_date', $today)->count_all_results('ad_views');

		if ($already > 0)
		{
			return $this->ad_result(FALSE, 'You have already watched this ad today.', 0, $user_id);
		}

		$credited = 0.0;

		$db->trans_start();

		$db->insert('ad_views', array(
			'user_id'   => $user_id,
			'ad_id'     => $ad_id,
			'view_date' => $today,
		));

		$this->CI->ad_model->increment_views($ad_id);

		$watched = $this->CI->ad_model->watched_count($user_id, $today);

		foreach ($active as $inv)
		{
			$row = $this->ensure_day_row($inv->id, $user_id, $today, $inv->daily_amount, $inv->daily_ads);

			if ( ! $row || $row->status !== 'pending')
			{
				continue;
			}

			$db->where('id', $row->id)->update('daily_earnings', array('ads_watched' => $watched));

			if ($watched >= (int) $row->ads_required)
			{
				$this->credit_day_row($row, $inv);
				$credited += (float) $row->amount;
			}
		}

		$db->trans_complete();

		if ($db->trans_status() === FALSE)
		{
			return $this->ad_result(FALSE, 'Could not record that view. Please try again.', 0, $user_id);
		}

		$message = ($credited > 0)
			? 'Daily target complete. '.money($credited).' credited to your balance.'
			: 'Ad recorded.';

		return $this->ad_result(TRUE, $message, $credited, $user_id);
	}

	protected function ad_result($ok, $message, $credited, $user_id)
	{
		return array(
			'ok'       => $ok,
			'message'  => $message,
			'credited' => (float) $credited,
			'watched'  => $this->CI->ad_model->watched_count($user_id),
			'required' => $this->CI->investment_model->daily_ads_required($user_id),
		);
	}

	/** Pay one day row and advance the investment counters. */
	protected function credit_day_row($row, $investment)
	{
		$db = $this->CI->db;

		$db->where('id', $row->id)->where('status', 'pending')->update('daily_earnings', array(
			'status'      => 'credited',
			'credited_at' => date('Y-m-d H:i:s'),
		));

		if ($db->affected_rows() < 1)
		{
			return FALSE; // already credited by a concurrent request
		}

		$this->CI->wallet_lib->credit(
			$row->user_id, $row->amount, 'daily_profit',
			'daily_earnings', $row->id,
			'Daily profit for '.$row->earn_date
		);

		$db->set('days_credited', 'days_credited + 1', FALSE)
			->set('total_earned', 'total_earned + '.$db->escape(money_raw($row->amount)), FALSE)
			->where('id', $investment->id)->update('investments');

		// Guarded by the affected_rows() check above, so a concurrent request
		// that lost the race never reaches here and cannot double-pay.
		$this->CI->agent_lib->pay_profit_commission($row);

		return TRUE;
	}

	/**
	 * Create the day row if it is missing. The unique index on
	 * (investment_id, earn_date) makes this safe to call repeatedly.
	 */
	public function ensure_day_row($investment_id, $user_id, $date, $amount, $ads_required)
	{
		// An off day serves no ads, so a row for it could never be completed and
		// would only be marked missed by the cron. It is never opened at all:
		// the day pays nothing and costs nothing.
		if (is_off_day($date))
		{
			return NULL;
		}

		$existing = $this->CI->daily_earning_model->for_investment_date($investment_id, $date);
		if ($existing)
		{
			return $existing;
		}

		$this->CI->db->insert('daily_earnings', array(
			'investment_id' => $investment_id,
			'user_id'       => $user_id,
			'earn_date'     => $date,
			'amount'        => money_raw($amount),
			'ads_required'  => (int) $ads_required,
			'ads_watched'   => 0,
			'status'        => 'pending',
		));

		return $this->CI->daily_earning_model->for_investment_date($investment_id, $date);
	}

	/* =================================================================
	 | Daily cron
	 |================================================================= */

	/**
	 * Idempotent. Safe to run many times a day and safe to miss days: it
	 * backfills every date between the last materialised row and today.
	 *
	 * @return array counters for the CLI / admin output
	 */
	public function run_daily_cron()
	{
		$today  = date('Y-m-d');
		$report = array('processed' => 0, 'days_opened' => 0, 'marked_missed' => 0, 'completed' => 0);

		foreach ($this->CI->investment_model->due_for_processing() as $inv)
		{
			$report['processed']++;

			$last = $this->CI->daily_earning_model->last_date($inv->id);
			$from = $last
				? date('Y-m-d', strtotime($last.' +1 day'))
				: $inv->start_date;

			$limit = (strtotime($today) < strtotime($inv->end_date)) ? $today : $inv->end_date;

			for ($d = $from; strtotime($d) <= strtotime($limit); $d = date('Y-m-d', strtotime($d.' +1 day')))
			{
				if (strtotime($d) < strtotime($inv->start_date) || is_off_day($d))
				{
					continue;
				}
				$this->ensure_day_row($inv->id, $inv->user_id, $d, $inv->daily_amount, $inv->daily_ads);
				$report['days_opened']++;
			}

			// Any day before today that never met its quota is forfeited.
			$stale = $this->CI->db->where('investment_id', $inv->id)
				->where('status', 'pending')->where('earn_date <', $today)
				->get('daily_earnings')->result();

			foreach ($stale as $row)
			{
				$this->CI->db->where('id', $row->id)->where('status', 'pending')
					->update('daily_earnings', array('status' => 'missed'));

				if ($this->CI->db->affected_rows() > 0)
				{
					$this->CI->db->set('days_missed', 'days_missed + 1', FALSE)
						->where('id', $inv->id)->update('investments');
					$report['marked_missed']++;
				}
			}

			if (strtotime($today) > strtotime($inv->end_date))
			{
				$this->CI->investment_model->update($inv->id, array('status' => 'completed'));
				$report['completed']++;

				$this->CI->notification_model->push(
					$inv->user_id,
					'Plan completed',
					'Your plan has finished its '.$inv->duration_days.'-day term.',
					'packages'
				);
			}
		}

		return $report;
	}

	/**
	 * Per-user catch-up used when a page is loaded, so today's row exists even
	 * if the cron has not fired yet. Creates rows only; never credits.
	 */
	public function ensure_today_rows($user_id)
	{
		$today = date('Y-m-d');

		foreach ($this->CI->investment_model->active_for_user($user_id) as $inv)
		{
			if (strtotime($today) < strtotime($inv->start_date) || strtotime($today) > strtotime($inv->end_date))
			{
				continue;
			}
			$this->ensure_day_row($inv->id, $user_id, $today, $inv->daily_amount, $inv->daily_ads);
		}
	}

	/** Today's progress summary for the dashboard and ads page. */
	public function today_progress($user_id)
	{
		$today    = date('Y-m-d');
		$required = $this->CI->investment_model->daily_ads_required($user_id);
		$watched  = $this->CI->ad_model->watched_count($user_id, $today);
		$rows     = $this->CI->daily_earning_model->today_rows($user_id, $today);

		$pending = 0.0;
		$earned  = 0.0;
		foreach ($rows as $r)
		{
			if ($r->status === 'credited')
			{
				$earned += (float) $r->amount;
			}
			elseif ($r->status === 'pending')
			{
				$pending += (float) $r->amount;
			}
		}

		$off = is_off_day($today);

		return array(
			'required'  => $required,
			'watched'   => min($watched, max($required, $watched)),
			'remaining' => $off ? 0 : max(0, $required - $watched),
			'complete'  => ($required > 0 && $watched >= $required),
			'pending'   => $pending,
			'earned'    => $earned,
			'off_day'   => $off,
			'off_note'  => $off ? $this->off_day_message($today) : NULL,
			'resumes_on' => $off ? next_working_day($today) : NULL,
		);
	}

	/** Wording used by both the API and the ads page on an off day. */
	protected function off_day_message($date = NULL)
	{
		$date = $date ?: date('Y-m-d');

		return 'Today is '.date('l', strtotime($date)).', a scheduled off day - no ads run and no daily'
			.' target is due. Ads resume on '.date('l, d M Y', strtotime(next_working_day($date))).'.';
	}
}
