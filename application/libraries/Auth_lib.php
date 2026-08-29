<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Registration, login, throttling and password resets.
 *
 * Users and admins live in separate tables and separate session keys, so a
 * user session can never satisfy the admin guard and vice versa.
 */
class Auth_lib {

	const MAX_ATTEMPTS  = 6;
	const LOCKOUT_MINS  = 15;
	const RESET_TTL_MIN = 60;

	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
		$this->CI->load->model(array('user_model', 'admin_model'));
	}

	/* =================================================================
	 | Users
	 |================================================================= */

	/**
	 * @param array $input full_name, username, email, mobile, country,
	 *                     password, referral_code (optional)
	 * @return array{ok:bool,message:string,user_id:?int}
	 */
	public function register($input)
	{
		$referrer_id = NULL;

		if ( ! empty($input['referral_code']))
		{
			$referrer = $this->CI->user_model->by_referral_code(strtoupper(trim($input['referral_code'])));
			if ( ! $referrer)
			{
				return array('ok' => FALSE, 'message' => 'That referral ID does not exist.', 'user_id' => NULL);
			}
			if ($referrer->status !== 'active')
			{
				return array('ok' => FALSE, 'message' => 'That referral ID belongs to an inactive account.', 'user_id' => NULL);
			}
			$referrer_id = $referrer->id;
		}

		$user_id = $this->CI->user_model->insert(array(
			'full_name'     => $input['full_name'],
			'username'      => $input['username'],
			'email'         => $input['email'],
			'mobile'        => isset($input['mobile']) ? $input['mobile'] : NULL,
			'country'       => isset($input['country']) ? $input['country'] : NULL,
			'password'      => password_hash($input['password'], PASSWORD_BCRYPT),
			'referral_code' => $this->CI->user_model->generate_referral_code(),
			'referred_by'   => $referrer_id,
			'status'        => 'active',
		));

		if ( ! $user_id)
		{
			return array('ok' => FALSE, 'message' => 'Could not create the account. Please try again.', 'user_id' => NULL);
		}

		if ($referrer_id)
		{
			$this->CI->load->model('notification_model');
			$this->CI->notification_model->push(
				$referrer_id,
				'New referral joined',
				$input['username'].' signed up using your referral ID. You earn a commission when they make their first deposit.',
				'referral'
			);
		}

		return array('ok' => TRUE, 'message' => 'Account created.', 'user_id' => $user_id);
	}

	/**
	 * @return array{ok:bool,message:string,user:?object}
	 */
	public function login($identity, $password, $remember = FALSE)
	{
		$identity = trim($identity);

		if ($this->is_locked_out('user', $identity))
		{
			return array('ok' => FALSE, 'message' => 'Too many failed attempts. Try again in '.self::LOCKOUT_MINS.' minutes.', 'user' => NULL);
		}

		$user = $this->CI->user_model->by_login($identity);

		if ( ! $user || ! password_verify($password, $user->password))
		{
			$this->record_attempt('user', $identity);
			return array('ok' => FALSE, 'message' => 'Incorrect username or password.', 'user' => NULL);
		}

		if ($user->status === 'blocked')
		{
			return array('ok' => FALSE, 'message' => 'This account has been blocked. Contact support.', 'user' => NULL);
		}
		if ($user->status === 'pending')
		{
			return array('ok' => FALSE, 'message' => 'This account is pending approval.', 'user' => NULL);
		}

		// Opportunistic rehash if the cost factor has moved on.
		if (password_needs_rehash($user->password, PASSWORD_BCRYPT))
		{
			$this->CI->user_model->update($user->id, array('password' => password_hash($password, PASSWORD_BCRYPT)));
		}

		$this->clear_attempts('user', $identity);

		$this->CI->user_model->update($user->id, array(
			'last_login_at' => date('Y-m-d H:i:s'),
			'last_login_ip' => $this->CI->input->ip_address(),
		));

		$this->CI->session->set_userdata(array(
			'user_id'       => $user->id,
			'user_username' => $user->username,
		));

		if ($remember)
		{
			$this->CI->session->set_userdata('user_remember', 1);
		}

		return array('ok' => TRUE, 'message' => 'Welcome back.', 'user' => $user);
	}

	public function logout()
	{
		$this->CI->session->unset_userdata(array('user_id', 'user_username', 'user_remember'));
	}

	/* =================================================================
	 | Admins
	 |================================================================= */

	public function admin_login($identity, $password)
	{
		$identity = trim($identity);

		if ($this->is_locked_out('admin', $identity))
		{
			return array('ok' => FALSE, 'message' => 'Too many failed attempts. Try again in '.self::LOCKOUT_MINS.' minutes.', 'admin' => NULL);
		}

		$admin = $this->CI->admin_model->by_login($identity);

		if ( ! $admin || ! password_verify($password, $admin->password))
		{
			$this->record_attempt('admin', $identity);
			return array('ok' => FALSE, 'message' => 'Incorrect username or password.', 'admin' => NULL);
		}

		if ($admin->status !== 'active')
		{
			return array('ok' => FALSE, 'message' => 'This admin account is blocked.', 'admin' => NULL);
		}

		$this->clear_attempts('admin', $identity);

		$this->CI->admin_model->update($admin->id, array(
			'last_login_at' => date('Y-m-d H:i:s'),
			'last_login_ip' => $this->CI->input->ip_address(),
		));

		$this->CI->session->set_userdata(array(
			'admin_id'   => $admin->id,
			'admin_name' => $admin->name,
			'admin_role' => $admin->role,
		));

		return array('ok' => TRUE, 'message' => 'Welcome back.', 'admin' => $admin);
	}

	public function admin_logout()
	{
		$this->CI->session->unset_userdata(array('admin_id', 'admin_name', 'admin_role'));
	}

	/* =================================================================
	 | Throttling
	 |================================================================= */

	protected function is_locked_out($scope, $identity)
	{
		$since = date('Y-m-d H:i:s', strtotime('-'.self::LOCKOUT_MINS.' minutes'));

		$count = (int) $this->CI->db
			->where('scope', $scope)->where('identity', $identity)
			->where('created_at >=', $since)
			->count_all_results('login_attempts');

		return $count >= self::MAX_ATTEMPTS;
	}

	protected function record_attempt($scope, $identity)
	{
		$this->CI->db->insert('login_attempts', array(
			'scope'      => $scope,
			'identity'   => $identity,
			'ip_address' => $this->CI->input->ip_address(),
		));

		// Opportunistic cleanup so the table does not grow without bound.
		$this->CI->db->where('created_at <', date('Y-m-d H:i:s', strtotime('-1 day')))->delete('login_attempts');
	}

	protected function clear_attempts($scope, $identity)
	{
		$this->CI->db->where('scope', $scope)->where('identity', $identity)->delete('login_attempts');
	}

	/* =================================================================
	 | Password reset
	 |================================================================= */

	/**
	 * Issues a reset token. Returns the token so the caller can mail it; with
	 * no mail transport configured the admin can hand it over manually.
	 */
	public function create_reset_token($email)
	{
		$user = $this->CI->user_model->find_by(array('email' => $email));
		if ( ! $user)
		{
			return NULL;
		}

		$token = bin2hex(random_bytes(24));

		$this->CI->db->insert('password_resets', array(
			'email'      => $email,
			'token'      => $token,
			'expires_at' => date('Y-m-d H:i:s', strtotime('+'.self::RESET_TTL_MIN.' minutes')),
		));

		return $token;
	}

	public function find_valid_reset($token)
	{
		return $this->CI->db->where('token', $token)
			->where('used_at', NULL)
			->where('expires_at >=', date('Y-m-d H:i:s'))
			->get('password_resets', 1)->row();
	}

	public function consume_reset($token, $new_password)
	{
		$row = $this->find_valid_reset($token);
		if ( ! $row)
		{
			return FALSE;
		}

		$user = $this->CI->user_model->find_by(array('email' => $row->email));
		if ( ! $user)
		{
			return FALSE;
		}

		$this->CI->user_model->update($user->id, array('password' => password_hash($new_password, PASSWORD_BCRYPT)));
		$this->CI->db->where('id', $row->id)->update('password_resets', array('used_at' => date('Y-m-d H:i:s')));

		return TRUE;
	}

	/* =================================================================
	 | API tokens
	 |================================================================= */

	public function issue_api_token($user_id)
	{
		$token = bin2hex(random_bytes(32));
		$this->CI->user_model->update($user_id, array(
			'api_token'    => $token,
			'api_token_at' => date('Y-m-d H:i:s'),
		));
		return $token;
	}

	public function revoke_api_token($user_id)
	{
		$this->CI->user_model->update($user_id, array('api_token' => NULL, 'api_token_at' => NULL));
	}
}
