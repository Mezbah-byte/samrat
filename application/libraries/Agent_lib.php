<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Agent commission accrual.
 *
 * An agent earns on two events inside their team:
 *   - a team member's deposit is approved   -> agent_deposit_percent
 *   - a team member banks a day's profit    -> agent_profit_percent
 *
 * Both entry points are called from inside Investment_lib's existing DB
 * transaction, and both are idempotent: the unique index on
 * (agent_id, source, reference_id) is the real guard, the paid_for() lookup
 * just avoids a failed insert.
 *
 * Where the money lands: the ledger row is always written. It is credited to
 * a wallet only when the agent has a linked user - a standalone agent accrues
 * unsettled and an admin pays them out by hand.
 */
class Agent_lib {

	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
		$this->CI->load->model(array(
			'user_model', 'agent_model', 'agent_commission_model', 'setting_model',
		));
		$this->CI->load->library('wallet_lib');
	}

	/** @return float amount credited, 0 when nothing was owed */
	public function pay_deposit_commission($deposit)
	{
		return $this->accrue(
			$deposit->user_id,
			'deposit',
			$deposit->id,
			(float) $deposit->amount,
			'commission_deposit_percent',
			'agent_deposit_percent',
			'1',
			'Commission on deposit #'.$deposit->id
		);
	}

	/** @return float amount credited, 0 when nothing was owed */
	public function pay_profit_commission($earning)
	{
		return $this->accrue(
			$earning->user_id,
			'daily_profit',
			$earning->id,
			(float) $earning->amount,
			'commission_profit_percent',
			'agent_profit_percent',
			'0.5',
			'Commission on daily profit for '.$earning->earn_date
		);
	}

	/**
	 * Finds the agent above this user and books their cut.
	 *
	 * @param int    $user_id     the team member whose activity earned it
	 * @param string $source      'deposit' or 'daily_profit'
	 * @param int    $reference_id deposits.id or daily_earnings.id
	 * @param float  $base        the amount the percentage applies to
	 * @param string $agent_field per-agent override column
	 * @param string $setting_key platform default setting
	 * @param string $fallback    default when the setting row is missing
	 */
	protected function accrue($user_id, $source, $reference_id, $base, $agent_field, $setting_key, $fallback, $description)
	{
		if ($this->CI->setting_model->get('agent_panel_enabled', '1') !== '1')
		{
			return 0;
		}

		if ($base <= 0)
		{
			return 0;
		}

		$agent = $this->resolve_agent($user_id);

		if ( ! $agent)
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

		$settled = (bool) $agent->user_id;

		$commission_id = $this->CI->agent_commission_model->insert(array(
			'agent_id'     => $agent->id,
			'user_id'      => $user_id,
			'source'       => $source,
			'reference_id' => $reference_id,
			'base_amount'  => money_raw($base),
			'percent'      => $percent,
			'amount'       => money_raw($amount),
			'settled'      => $settled ? 1 : 0,
		));

		if ( ! $commission_id)
		{
			return 0;
		}

		$this->CI->agent_model->add_commission($agent->id, $amount);

		if ($settled)
		{
			$this->CI->wallet_lib->credit(
				$agent->user_id, $amount, 'agent_commission',
				'agent_commissions', $commission_id, $description
			);
		}

		return $amount;
	}

	/**
	 * Walks up the referral chain from a user until it finds one who holds an
	 * active agent account. That agent's team is, by definition, every account
	 * below them - so the first one found upward is the right one.
	 *
	 * Cycle-safe and depth-capped, the same way
	 * Investment_lib::pay_referral_commission() is.
	 *
	 * @return object|null
	 */
	protected function resolve_agent($user_id)
	{
		$user = $this->CI->user_model->find($user_id);

		if ( ! $user || ! $user->referred_by)
		{
			return NULL;
		}

		$depth = (int) $this->CI->setting_model->get('agent_team_depth', 20);

		if ($depth < 1)
		{
			return NULL;
		}

		$current = $user;
		$seen    = array((int) $user->id => TRUE);

		for ($level = 1; $level <= $depth; $level++)
		{
			if (empty($current->referred_by))
			{
				return NULL;
			}

			$upline_id = (int) $current->referred_by;

			if (isset($seen[$upline_id]))
			{
				return NULL;
			}
			$seen[$upline_id] = TRUE;

			$upline = $this->CI->user_model->find($upline_id);

			if ( ! $upline)
			{
				return NULL;
			}

			if ($agent = $this->CI->agent_model->active_by_user($upline->id))
			{
				return $agent;
			}

			$current = $upline;
		}

		return NULL;
	}
}
