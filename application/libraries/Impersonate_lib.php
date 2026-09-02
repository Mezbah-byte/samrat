<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin "log in as" support for the user and agent panels.
 *
 * The admin session key is never touched. Admins, users and agents live under
 * separate session keys, so an impersonating admin stays signed into the admin
 * panel throughout and stopping is only a matter of dropping the borrowed keys
 * again - no re-login, no second password prompt.
 *
 * Session shape while impersonating:
 *   imp_admin_id      the real admin, for the audit trail
 *   imp_admin_name    for the banner
 *   imp_type          'user' | 'agent'
 *   imp_target_id     the borrowed account id
 *   imp_target_label  the borrowed username, for the banner
 *   imp_started_at    unix timestamp
 */
class Impersonate_lib {

	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	public function active()
	{
		return (bool) $this->CI->session->userdata('imp_admin_id');
	}

	/**
	 * Banner payload, or NULL when this is an ordinary session.
	 */
	public function context()
	{
		if ( ! $this->active())
		{
			return NULL;
		}

		return array(
			'admin_id'   => (int) $this->CI->session->userdata('imp_admin_id'),
			'admin_name' => $this->CI->session->userdata('imp_admin_name'),
			'type'       => $this->CI->session->userdata('imp_type'),
			'target_id'  => (int) $this->CI->session->userdata('imp_target_id'),
			'label'      => $this->CI->session->userdata('imp_target_label'),
			'started_at' => (int) $this->CI->session->userdata('imp_started_at'),
		);
	}

	/* =================================================================
	 | Starting
	 |================================================================= */

	/**
	 * @param object $admin   the admin row doing the impersonating
	 * @return array{ok:bool,message:string}
	 */
	public function start_user($admin, $user_id)
	{
		$this->CI->load->model('user_model');
		$user = $this->CI->user_model->find($user_id);

		if ( ! $user)
		{
			return array('ok' => FALSE, 'message' => 'That user does not exist.');
		}

		// User_Controller bounces anything that is not active, so borrowing a
		// blocked or pending account would just dump the admin straight back out.
		if ($user->status !== 'active')
		{
			return array('ok' => FALSE, 'message' => 'Only active accounts can be impersonated. Activate it first.');
		}

		$this->mark($admin, 'user', $user->id, $user->username);

		$this->CI->session->set_userdata(array(
			'user_id'       => $user->id,
			'user_username' => $user->username,
		));

		return array('ok' => TRUE, 'message' => 'Now viewing as '.$user->username.'.');
	}

	/**
	 * @return array{ok:bool,message:string}
	 */
	public function start_agent($admin, $agent_id)
	{
		$this->CI->load->model(array('agent_model', 'setting_model'));
		$agent = $this->CI->agent_model->find($agent_id);

		if ( ! $agent)
		{
			return array('ok' => FALSE, 'message' => 'That agent does not exist.');
		}
		if ($agent->status !== 'active')
		{
			return array('ok' => FALSE, 'message' => 'Only active agents can be impersonated.');
		}

		// Agent_Controller enforces the same switch on every request.
		if ($this->CI->setting_model->get('agent_panel_enabled', '1') !== '1')
		{
			return array('ok' => FALSE, 'message' => 'The agent panel is disabled. Enable it before impersonating an agent.');
		}

		$this->mark($admin, 'agent', $agent->id, $agent->username);

		$this->CI->session->set_userdata(array(
			'agent_id'       => $agent->id,
			'agent_name'     => $agent->name,
			'agent_username' => $agent->username,
		));

		return array('ok' => TRUE, 'message' => 'Now viewing as agent '.$agent->username.'.');
	}

	/* =================================================================
	 | Stopping
	 |================================================================= */

	/**
	 * Drops the borrowed session, leaving the admin session intact.
	 *
	 * @return array{type:?string,target_id:int}
	 */
	public function stop()
	{
		$context = $this->context();

		$this->CI->session->unset_userdata(array(
			'user_id', 'user_username', 'user_remember',
			'agent_id', 'agent_name', 'agent_username',
			'imp_admin_id', 'imp_admin_name', 'imp_type',
			'imp_target_id', 'imp_target_label', 'imp_started_at',
		));

		return array(
			'type'      => $context ? $context['type'] : NULL,
			'target_id' => $context ? $context['target_id'] : 0,
		);
	}

	/**
	 * Where an admin should land once impersonation ends.
	 *
	 * @param array $stopped the return value of stop()
	 */
	public function return_url($stopped)
	{
		if ($stopped['type'] === 'user' && $stopped['target_id'])
		{
			return 'admin/users/view/'.$stopped['target_id'];
		}
		if ($stopped['type'] === 'agent')
		{
			return 'admin/agents';
		}
		return 'admin/dashboard';
	}

	/* =================================================================
	 | Audit
	 |================================================================= */

	/**
	 * One admin_logs row per write made while impersonating, attributed to the
	 * real admin. Impersonation is full-access, so without this a withdrawal
	 * requested by an admin is indistinguishable from one the account holder
	 * requested themselves.
	 */
	public function log_request($uri, $detail = NULL)
	{
		if ( ! $this->active())
		{
			return;
		}

		$this->CI->db->insert('admin_logs', array(
			'admin_id'     => (int) $this->CI->session->userdata('imp_admin_id'),
			// admin_logs.action is VARCHAR(120).
			'action'       => mb_substr('Impersonated write: '.$uri, 0, 120),
			'module'       => 'impersonate',
			'reference_id' => (int) $this->CI->session->userdata('imp_target_id'),
			'detail'       => $detail !== NULL ? mb_substr($detail, 0, 500) : NULL,
			'ip_address'   => $this->CI->input->ip_address(),
		));
	}

	protected function mark($admin, $type, $target_id, $label)
	{
		$this->CI->session->set_userdata(array(
			'imp_admin_id'     => $admin->id,
			'imp_admin_name'   => $admin->name,
			'imp_type'         => $type,
			'imp_target_id'    => $target_id,
			'imp_target_label' => $label,
			'imp_started_at'   => time(),
		));
	}
}
