<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controllers.
 *
 *   MY_Controller     shared bootstrap (settings, timezone, layout renderer)
 *   Public_Controller guests + logged-in users, honours maintenance mode
 *   User_Controller   requires an active user session
 *   Admin_Controller  requires an admin session (separate session key)
 *   API_Controller    JSON in/out, bearer-token auth
 */
class MY_Controller extends CI_Controller {

	/** @var string view file rendered inside the layout */
	protected $layout = 'layouts/public';

	/** @var array data merged into every render() call */
	protected $view_data = array();

	public function __construct()
	{
		parent::__construct();

		$this->load->model('setting_model');
		date_default_timezone_set($this->setting_model->get('timezone', 'Asia/Dhaka'));

		$this->view_data['settings']     = $this->setting_model->all_keyed();
		$this->view_data['company_name'] = $this->setting_model->get('company_name', 'Investment Platform');
		$this->view_data['page_title']   = '';
		$this->view_data['active_menu']  = '';
	}

	/**
	 * Render a view inside a layout. The layout is expected to include the
	 * partial itself via `$this->load->view($_content)`, which inherits all
	 * variables passed here.
	 */
	protected function render($view, $data = array(), $layout = NULL)
	{
		$data = array_merge($this->view_data, $data);
		$data['_content'] = $view;
		$this->load->view($layout ?: $this->layout, $data);
	}

	protected function json($payload, $status = 200)
	{
		$this->output
			->set_status_header($status)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($payload));
	}

	protected function is_ajax()
	{
		return $this->input->is_ajax_request();
	}
}

/* ===================================================================== */

class Public_Controller extends MY_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->layout = 'layouts/public';

		if ($this->setting_model->get('maintenance_mode', '0') === '1' && ! $this->session->userdata('admin_id'))
		{
			$this->output->set_status_header(503);
			$this->load->view('errors/maintenance', array(
				'company_name' => $this->view_data['company_name'],
				'message'      => $this->setting_model->get('maintenance_message', 'We will be back shortly.'),
			));
			$this->output->_display();
			exit;
		}

		$this->view_data['current_user'] = NULL;
		if ($uid = $this->session->userdata('user_id'))
		{
			$this->load->model('user_model');
			$this->view_data['current_user'] = $this->user_model->find($uid);
		}
	}
}

/* ===================================================================== */

class User_Controller extends MY_Controller {

	/** @var object logged-in user row */
	protected $user;

	public function __construct()
	{
		parent::__construct();

		$this->layout = 'layouts/user';

		$this->load->model(array('user_model', 'notification_model'));
		$this->load->library(array('wallet_lib', 'investment_lib'));

		$uid = $this->session->userdata('user_id');
		if ( ! $uid)
		{
			$this->session->set_flashdata('error', 'Please log in to continue.');
			redirect('login');
		}

		$this->user = $this->user_model->find($uid);

		if ( ! $this->user)
		{
			$this->session->sess_destroy();
			redirect('login');
		}

		if ($this->user->status !== 'active')
		{
			$reason = ($this->user->status === 'blocked')
				? 'Your account has been blocked. Contact support.'
				: 'Your account is pending approval.';
			$this->session->sess_destroy();
			$this->session->set_flashdata('error', $reason);
			redirect('login');
		}

		if ($this->setting_model->get('maintenance_mode', '0') === '1')
		{
			$this->output->set_status_header(503);
			$this->load->view('errors/maintenance', array(
				'company_name' => $this->view_data['company_name'],
				'message'      => $this->setting_model->get('maintenance_message', 'We will be back shortly.'),
			));
			$this->output->_display();
			exit;
		}

		$this->view_data['user']         = $this->user;
		$this->view_data['current_user'] = $this->user;
		$this->view_data['unread_count'] = $this->notification_model->unread_count($this->user->id);

		// The shell shows the outstanding ad count and the notice ticker on
		// every screen, so both are resolved here rather than per controller.
		$this->load->model('notice_model');
		$today_progress = $this->investment_lib->today_progress($this->user->id);
		$this->view_data['ads_remaining']  = $today_progress['remaining'];
		$this->view_data['ticker_notices'] = $this->notice_model->published(5);
	}
}

/* ===================================================================== */

class Admin_Controller extends MY_Controller {

	/** @var object logged-in admin row */
	protected $admin;

	public function __construct()
	{
		parent::__construct();

		$this->layout = 'layouts/admin';

		$this->load->model(array('admin_model', 'admin_log_model'));

		$aid = $this->session->userdata('admin_id');
		if ( ! $aid)
		{
			$this->session->set_flashdata('error', 'Please log in to continue.');
			redirect('admin/login');
		}

		$this->admin = $this->admin_model->find($aid);

		if ( ! $this->admin || $this->admin->status !== 'active')
		{
			$this->session->unset_userdata(array('admin_id', 'admin_role', 'admin_name'));
			$this->session->set_flashdata('error', 'Admin session is no longer valid.');
			redirect('admin/login');
		}

		$this->view_data['admin']       = $this->admin;
		$this->view_data['admin_stats'] = $this->admin_model->sidebar_badges();
	}

	/** Hard gate for destructive / privileged screens. */
	protected function require_role($roles)
	{
		$roles = (array) $roles;
		if ( ! in_array($this->admin->role, $roles, TRUE))
		{
			$this->session->set_flashdata('error', 'You do not have permission for that action.');
			redirect('admin/dashboard');
		}
	}

	protected function log_action($action, $module, $reference_id = NULL, $detail = NULL)
	{
		$this->admin_log_model->insert(array(
			'admin_id'     => $this->admin->id,
			'action'       => $action,
			'module'       => $module,
			'reference_id' => $reference_id,
			'detail'       => $detail !== NULL ? mb_substr($detail, 0, 500) : NULL,
			'ip_address'   => $this->input->ip_address(),
		));
	}
}

/* ===================================================================== */

class API_Controller extends MY_Controller {

	/** @var object|null authenticated user, set by require_auth() */
	protected $api_user = NULL;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('user_model');
		$this->output->set_content_type('application/json', 'utf-8');
	}

	/** Decoded JSON body, falling back to form-encoded input. */
	protected function body()
	{
		$raw = $this->input->raw_input_stream;
		if ($raw !== '' && $raw !== NULL)
		{
			$decoded = json_decode($raw, TRUE);
			if (is_array($decoded))
			{
				return $decoded;
			}
		}
		return $this->input->post() ?: array();
	}

	protected function field($key, $default = NULL)
	{
		$body = $this->body();
		return isset($body[$key]) ? $body[$key] : $default;
	}

	protected function ok($data = array(), $message = 'OK', $status = 200)
	{
		return $this->respond(TRUE, $message, $data, $status);
	}

	protected function fail($message = 'Request failed', $status = 400, $data = array())
	{
		return $this->respond(FALSE, $message, $data, $status);
	}

	protected function respond($success, $message, $data, $status)
	{
		$this->output
			->set_status_header($status)
			->set_output(json_encode(array(
				'success' => (bool) $success,
				'message' => $message,
				'data'    => $data,
			)));
		return FALSE;
	}

	/** Resolves `Authorization: Bearer <token>`. Halts with 401 when absent. */
	protected function require_auth()
	{
		$header = $this->input->get_request_header('Authorization', TRUE);
		$token  = '';

		if ($header && stripos($header, 'Bearer ') === 0)
		{
			$token = trim(substr($header, 7));
		}
		elseif ($alt = $this->input->get_request_header('X-Api-Token', TRUE))
		{
			$token = trim($alt);
		}

		if ($token === '')
		{
			$this->fail('Authentication token missing.', 401);
			return FALSE;
		}

		$user = $this->user_model->find_by(array('api_token' => $token));

		if ( ! $user)
		{
			$this->fail('Invalid or expired token.', 401);
			return FALSE;
		}

		if ($user->status !== 'active')
		{
			$this->fail('Account is not active.', 403);
			return FALSE;
		}

		$this->api_user = $user;
		return TRUE;
	}

	protected function method_is($verb)
	{
		if (strtoupper($this->input->method(TRUE)) !== strtoupper($verb))
		{
			$this->fail('Method not allowed. Use '.strtoupper($verb).'.', 405);
			return FALSE;
		}
		return TRUE;
	}
}
