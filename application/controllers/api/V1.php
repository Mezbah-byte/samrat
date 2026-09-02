<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal JSON API for a future mobile client.
 *
 * The web panels talk to the models directly; this layer exists only for
 * out-of-browser clients. Auth is a bearer token stored on users.api_token.
 * CSRF is disabled for api/v1/* in config.php since there is no cookie session.
 */
class V1 extends API_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('auth_lib');
		$this->load->model(array('package_model', 'notice_model'));
	}

	public function index()
	{
		$this->ok(array(
			'name'      => $this->setting_model->get('company_name', 'API'),
			'version'   => 'v1',
			'endpoints' => array(
				'POST /api/v1/register', 'POST /api/v1/login', 'POST /api/v1/logout',
				'GET  /api/v1/packages', 'GET  /api/v1/notices', 'GET  /api/v1/deposit/methods',
				'GET  /api/v1/profile', 'GET  /api/v1/dashboard', 'GET  /api/v1/transactions',
				'POST /api/v1/deposit', 'POST /api/v1/withdraw',
				'GET  /api/v1/ads', 'POST /api/v1/ads/watch', 'GET  /api/v1/referral',
			),
		), 'API is up.');
	}

	/* =================================================================
	 | Public
	 |================================================================= */

	public function packages()
	{
		$rows = array();

		foreach ($this->package_model->active() as $p)
		{
			$rows[] = array(
				'id'            => (int) $p->id,
				'name'          => $p->name,
				'deposit'       => (float) $p->price,
				'daily_percent' => (float) $p->daily_return_percent,
				'daily_income'  => round((float) $p->price * (float) $p->daily_return_percent / 100, 8),
				'duration_days' => (int) $p->duration_days,
				'daily_ads'     => (int) $p->daily_ads,
				'min_withdraw'  => (float) $p->min_withdraw,
				'description'   => $p->description,
			);
		}

		$this->ok(array('packages' => $rows));
	}

	public function notices()
	{
		$rows = array();

		foreach ($this->notice_model->published(30) as $n)
		{
			$rows[] = array(
				'id'           => (int) $n->id,
				'title'        => $n->title,
				'slug'         => $n->slug,
				'type'         => $n->type,
				'content'      => $n->content,
				'is_pinned'    => (bool) $n->is_pinned,
				'published_at' => $n->published_at,
			);
		}

		$this->ok(array('notices' => $rows));
	}

	public function deposit_methods()
	{
		$this->load->model('deposit_method_model');

		$rows = array();

		foreach ($this->deposit_method_model->active() as $m)
		{
			$rows[] = array(
				'id'             => (int) $m->id,
				'name'           => $m->name,
				'network'        => $m->network,
				'currency'       => $m->currency,
				'wallet_address' => $m->wallet_address,
				'qr_image'       => $m->qr_image ? upload_url('qr', $m->qr_image) : NULL,
				'min_amount'     => (float) $m->min_amount,
				'instructions'   => $m->instructions,
			);
		}

		$this->ok(array('methods' => $rows));
	}

	/* =================================================================
	 | Auth
	 |================================================================= */

	public function register()
	{
		if ( ! $this->method_is('POST')) return;

		if ($this->setting_model->get('registration_open', '1') !== '1')
		{
			return $this->fail('Registration is currently closed.', 403);
		}

		$body = $this->body();

		$required = array('full_name', 'username', 'email', 'mobile', 'country', 'password');
		foreach ($required as $field)
		{
			if (empty($body[$field]))
			{
				return $this->fail('Missing required field: '.$field, 422);
			}
		}

		if ( ! filter_var($body['email'], FILTER_VALIDATE_EMAIL))
		{
			return $this->fail('Email address is not valid.', 422);
		}
		if (strlen($body['password']) < 6)
		{
			return $this->fail('Password must be at least 6 characters.', 422);
		}
		if ($this->user_model->find_by(array('username' => $body['username'])))
		{
			return $this->fail('That username is taken.', 409);
		}
		if ($this->user_model->find_by(array('email' => $body['email'])))
		{
			return $this->fail('That email is already registered.', 409);
		}

		$result = $this->auth_lib->register(array(
			'full_name'     => $body['full_name'],
			'username'      => $body['username'],
			'email'         => $body['email'],
			'mobile'        => $body['mobile'],
			'country'       => $body['country'],
			'password'      => $body['password'],
			'referral_code' => isset($body['referral_code']) ? $body['referral_code'] : NULL,
		));

		if ( ! $result['ok'])
		{
			return $this->fail($result['message'], 422);
		}

		$token = $this->auth_lib->issue_api_token($result['user_id']);
		$user  = $this->user_model->find($result['user_id']);

		$this->ok(array('token' => $token, 'user' => $this->user_payload($user)), 'Account created.', 201);
	}

	public function login()
	{
		if ( ! $this->method_is('POST')) return;

		$identity = $this->field('identity', $this->field('username'));
		$password = $this->field('password');

		if ( ! $identity || ! $password)
		{
			return $this->fail('identity and password are required.', 422);
		}

		$user = $this->user_model->by_login(trim($identity));

		if ( ! $user || ! password_verify($password, $user->password))
		{
			return $this->fail('Incorrect username or password.', 401);
		}
		if ($user->status !== 'active')
		{
			return $this->fail('This account is '.$user->status.'.', 403);
		}

		$token = $this->auth_lib->issue_api_token($user->id);

		$this->user_model->update($user->id, array(
			'last_login_at' => date('Y-m-d H:i:s'),
			'last_login_ip' => $this->input->ip_address(),
		));

		$this->ok(array('token' => $token, 'user' => $this->user_payload($this->user_model->find($user->id))), 'Logged in.');
	}

	public function logout()
	{
		if ( ! $this->require_auth()) return;

		$this->auth_lib->revoke_api_token($this->api_user->id);
		$this->ok(array(), 'Logged out.');
	}

	/* =================================================================
	 | Authenticated
	 |================================================================= */

	public function profile()
	{
		if ( ! $this->require_auth()) return;

		$this->ok(array('user' => $this->user_payload($this->api_user)));
	}

	public function dashboard()
	{
		if ( ! $this->require_auth()) return;

		$this->load->model(array('investment_model', 'referral_model'));
		$this->load->library('investment_lib');

		$this->investment_lib->ensure_today_rows($this->api_user->id);
		$progress = $this->investment_lib->today_progress($this->api_user->id);

		$plans = array();
		foreach ($this->investment_model->active_for_user($this->api_user->id) as $inv)
		{
			$plans[] = array(
				'id'            => (int) $inv->id,
				'package'       => $inv->package_name,
				'amount'        => (float) $inv->amount,
				'daily_amount'  => (float) $inv->daily_amount,
				'daily_ads'     => (int) $inv->daily_ads,
				'days_credited' => (int) $inv->days_credited,
				'days_missed'   => (int) $inv->days_missed,
				'duration_days' => (int) $inv->duration_days,
				'total_earned'  => (float) $inv->total_earned,
				'start_date'    => $inv->start_date,
				'end_date'      => $inv->end_date,
			);
		}

		$this->ok(array(
			'balance'         => (float) $this->api_user->balance,
			'total_deposit'   => (float) $this->api_user->total_deposit,
			'total_earned'    => (float) $this->api_user->total_earned,
			'total_withdrawn' => (float) $this->api_user->total_withdrawn,
			'referral_bonus'  => (float) $this->api_user->total_referral_bonus,
			'referral_count'  => $this->user_model->referral_count($this->api_user->id),
			'today'           => $progress,
			'active_plans'    => $plans,
		));
	}

	public function transactions()
	{
		if ( ! $this->require_auth()) return;

		$this->load->model('transaction_model');

		$limit  = min(100, max(1, (int) $this->input->get('limit') ?: 25));
		$page   = max(1, (int) $this->input->get('page'));
		$result = $this->transaction_model->for_user($this->api_user->id, $limit, ($page - 1) * $limit, $this->input->get('type', TRUE) ?: '');

		$rows = array();
		foreach ($result['rows'] as $t)
		{
			$rows[] = array(
				'id'            => (int) $t->id,
				'type'          => $t->type,
				'label'         => tx_label($t->type),
				'amount'        => (float) $t->amount,
				'balance_after' => (float) $t->balance_after,
				'description'   => $t->description,
				'created_at'    => $t->created_at,
			);
		}

		$this->ok(array('transactions' => $rows, 'total' => $result['total'], 'page' => $page, 'limit' => $limit));
	}

	public function deposit()
	{
		if ( ! $this->require_auth()) return;
		if ( ! $this->method_is('POST')) return;

		if ($this->setting_model->get('deposit_enabled', '1') !== '1')
		{
			return $this->fail('Deposits are temporarily disabled.', 403);
		}

		$this->load->model(array('deposit_model', 'deposit_method_model'));

		$package_id = (int) $this->field('package_id');
		$method_id  = (int) $this->field('deposit_method_id');
		$txid       = trim((string) $this->field('txid'));

		$package = $this->package_model->find($package_id);
		$method  = $this->deposit_method_model->find($method_id);

		if ( ! $package || $package->status !== 'active')
		{
			return $this->fail('Invalid package.', 422);
		}
		if ( ! $method || $method->status !== 'active')
		{
			return $this->fail('Invalid deposit method.', 422);
		}
		if (strlen($txid) < 10)
		{
			return $this->fail('A valid transaction hash is required.', 422);
		}
		if ($this->deposit_model->txid_exists($txid))
		{
			return $this->fail('That transaction hash has already been submitted.', 409);
		}

		$id = $this->deposit_model->insert(array(
			'user_id'           => $this->api_user->id,
			'package_id'        => $package->id,
			'deposit_method_id' => $method->id,
			'amount'            => money_raw($package->price),
			'network'           => $method->network,
			'txid'              => $txid,
			'status'            => 'pending',
		));

		$this->ok(array('deposit_id' => $id, 'status' => 'pending'), 'Deposit submitted for review.', 201);
	}

	public function withdraw()
	{
		if ( ! $this->require_auth()) return;
		if ( ! $this->method_is('POST')) return;

		if ($this->setting_model->get('withdrawal_enabled', '1') !== '1')
		{
			return $this->fail('Withdrawals are temporarily disabled.', 403);
		}

		$this->load->model(array('withdrawal_model', 'investment_model'));
		$this->load->library('wallet_lib');

		$amount  = round((float) $this->field('amount'), MONEY_SCALE);
		$network = (string) $this->field('network');
		$wallet  = trim((string) $this->field('wallet_address'));

		if ($amount <= 0)
		{
			return $this->fail('Amount must be greater than zero.', 422);
		}
		if ( ! array_key_exists($network, network_list()))
		{
			return $this->fail('Unsupported network.', 422);
		}
		if (strlen($wallet) < 20)
		{
			return $this->fail('A valid wallet address is required.', 422);
		}

		$floor = $this->investment_model->withdraw_floor($this->api_user->id);

		if ($floor <= 0)
		{
			return $this->fail('You need an active package before you can withdraw.', 403);
		}
		if ($amount < $floor)
		{
			return $this->fail('Minimum withdrawal for your package is '.money($floor).'.', 422);
		}

		$balance = $this->wallet_lib->balance($this->api_user->id);

		if ($amount > $balance)
		{
			return $this->fail('Amount exceeds your available balance.', 422);
		}

		$fee_percent = (float) $this->setting_model->get('withdrawal_fee_percent', 5);
		$fee         = round($amount * $fee_percent / 100, MONEY_SCALE);
		$net         = round($amount - $fee, MONEY_SCALE);

		$this->db->trans_start();

		$id = $this->withdrawal_model->insert(array(
			'user_id'        => $this->api_user->id,
			'amount'         => money_raw($amount),
			'fee_percent'    => $fee_percent,
			'fee'            => money_raw($fee),
			'net_amount'     => money_raw($net),
			'network'        => $network,
			'wallet_address' => $wallet,
			'status'         => 'pending',
		));

		$this->wallet_lib->debit($this->api_user->id, $net, 'withdrawal', 'withdrawals', $id, 'Withdrawal request #'.$id);

		if ($fee > 0)
		{
			$this->wallet_lib->debit($this->api_user->id, $fee, 'withdrawal_fee', 'withdrawals', $id, $fee_percent.'% withdrawal fee');
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE)
		{
			return $this->fail('Could not place the request. Your balance was not changed.', 500);
		}

		$this->ok(array(
			'withdrawal_id' => $id,
			'amount'        => $amount,
			'fee'           => $fee,
			'net_amount'    => $net,
			'status'        => 'pending',
		), 'Withdrawal requested.', 201);
	}

	public function ads()
	{
		if ( ! $this->require_auth()) return;

		$this->load->model(array('ad_model', 'investment_model'));
		$this->load->library('investment_lib');

		$this->investment_lib->ensure_today_rows($this->api_user->id);

		$progress = $this->investment_lib->today_progress($this->api_user->id);
		$watched  = $this->ad_model->watched_ids($this->api_user->id);

		// No active plan means no quota, and ads_watch would reject every view -
		// so the list stays empty rather than inviting views that pay nothing.
		$live = $progress['required'] > 0
			? $this->ad_model->daily_task_ads($progress['required'] + 5)
			: array();

		$rows = array();
		foreach ($live as $ad)
		{
			$rows[] = array(
				'id'            => (int) $ad->id,
				'title'         => $ad->title,
				'type'          => $ad->type,
				'source'        => $ad->source,
				'media'         => $ad->media ? upload_url('ads', $ad->media) : ($ad->media_url ?: NULL),
				'vast_url'      => $ad->source === 'vast' ? $ad->vast_url : NULL,
				'embed_code'    => $ad->source === 'embed' ? $ad->embed_code : NULL,
				'target_url'    => $ad->target_url,
				'body'          => $ad->body,
				'watch_seconds' => (int) $ad->watch_seconds,
				'watched_today' => in_array((int) $ad->id, $watched, TRUE),
			);
		}

		$this->ok(array('today' => $progress, 'ads' => $rows));
	}

	public function ads_watch()
	{
		if ( ! $this->require_auth()) return;
		if ( ! $this->method_is('POST')) return;

		$this->load->library('investment_lib');

		$ad_id = (int) $this->field('ad_id');

		if ( ! $ad_id)
		{
			return $this->fail('ad_id is required.', 422);
		}

		$result = $this->investment_lib->register_ad_view($this->api_user->id, $ad_id);

		if ( ! $result['ok'])
		{
			return $this->fail($result['message'], 422, $result);
		}

		$this->ok($result, $result['message']);
	}

	public function referral()
	{
		if ( ! $this->require_auth()) return;

		$this->load->model(array('referral_model', 'referral_level_model'));

		$ladder        = $this->referral_level_model->ladder();
		$earned_levels = $this->referral_model->earned_by_level($this->api_user->id);
		$gen_counts    = $this->user_model->generation_counts($this->api_user->id, $this->referral_level_model->max_level());

		$generations = array();
		foreach ($ladder as $g)
		{
			$lvl = (int) $g->level;
			$generations[] = array(
				'level'   => $lvl,
				'percent' => (float) $g->percent,
				'active'  => $g->status === 'active',
				'people'  => isset($gen_counts[$lvl]) ? $gen_counts[$lvl] : 0,
				'earned'  => isset($earned_levels[$lvl]) ? $earned_levels[$lvl]['earned'] : 0.0,
			);
		}

		$downline = array();
		foreach ($this->user_model->direct_referrals($this->api_user->id, 100) as $r)
		{
			$downline[] = array(
				'username'      => $r->username,
				'full_name'     => $r->full_name,
				'country'       => $r->country,
				'status'        => $r->status,
				'total_deposit' => (float) $r->total_deposit,
				'joined_at'     => $r->created_at,
			);
		}

		$this->ok(array(
			'referral_code' => $this->api_user->referral_code,
			'referral_link' => base_url('register/'.$this->api_user->referral_code),
			// `percent` is generation 1, kept for clients written before the
			// ladder existed; `generations` is the full picture.
			'percent'       => isset($generations[0]) ? $generations[0]['percent'] : 0.0,
			'generations'   => $generations,
			'team_size'     => array_sum($gen_counts),
			'total_count'   => count($downline),
			'earned_total'  => $this->referral_model->earned_total($this->api_user->id),
			'downline'      => $downline,
		));
	}

	/**
	 * Team volume bonus: the ladder, where this user stands on it, and what is
	 * waiting to be claimed. Claiming itself stays web-only for now.
	 */
	public function team_bonus()
	{
		if ( ! $this->require_auth()) return;

		$this->load->library('team_bonus_lib');

		if ( ! $this->team_bonus_lib->enabled())
		{
			$this->ok(array('enabled' => FALSE, 'tiers' => array()));
			return;
		}

		$this->team_bonus_lib->sync_unlocks($this->api_user->id);

		$this->ok($this->team_bonus_lib->progress($this->api_user->id));
	}

	/* ----------------------------------------------------------------- */

	protected function user_payload($user)
	{
		return array(
			'id'                   => (int) $user->id,
			'full_name'            => $user->full_name,
			'username'             => $user->username,
			'email'                => $user->email,
			'mobile'               => $user->mobile,
			'country'              => $user->country,
			'avatar'               => $user->avatar ? upload_url('avatars', $user->avatar) : NULL,
			'referral_code'        => $user->referral_code,
			'balance'              => (float) $user->balance,
			'total_deposit'        => (float) $user->total_deposit,
			'total_earned'         => (float) $user->total_earned,
			'total_withdrawn'      => (float) $user->total_withdrawn,
			'total_referral_bonus' => (float) $user->total_referral_bonus,
			'status'               => $user->status,
			'created_at'           => $user->created_at,
		);
	}
}
