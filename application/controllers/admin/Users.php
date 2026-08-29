<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('user_model', 'investment_model', 'deposit_model',
			'withdrawal_model', 'transaction_model', 'referral_model', 'notification_model'));
		$this->load->library('wallet_lib');
	}

	public function index()
	{
		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->user_model->paginate_users($per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('admin/users', array(
			'page_title'  => 'Users',
			'active_menu' => 'users',
			'rows'        => $result['rows'],
			'total'       => $result['total'],
			'per_page'    => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'stats'       => $this->user_model->platform_stats(),
		));
	}

	public function view($id)
	{
		$user = $this->user_model->find($id);

		if ( ! $user)
		{
			show_404();
		}

		$referrer = $user->referred_by ? $this->user_model->find($user->referred_by) : NULL;

		$this->render('admin/user_view', array(
			'page_title'   => $user->username,
			'active_menu'  => 'users',
			'u'            => $user,
			'referrer'     => $referrer,
			'investments'  => $this->investment_model->for_user($user->id, 10)['rows'],
			'deposits'     => $this->deposit_model->for_user($user->id, 10)['rows'],
			'withdrawals'  => $this->withdrawal_model->for_user($user->id, 10)['rows'],
			'transactions' => $this->transaction_model->all(array('user_id' => $user->id), 15),
			'downline'     => $this->user_model->direct_referrals($user->id, 20),
			'earned_ref'   => $this->referral_model->earned_total($user->id),
			'reconcile'    => $this->wallet_lib->reconcile($user->id),
			'countries'    => country_list(),
		));
	}

	public function edit($id)
	{
		$user = $this->user_model->find($id);

		if ( ! $user)
		{
			show_404();
		}

		if ($this->input->method() !== 'post')
		{
			redirect('admin/users/view/'.$id);
		}

		$this->form_validation->set_rules('full_name', 'Full Name', 'required|trim|max_length[120]');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[150]');
		$this->form_validation->set_rules('mobile', 'Mobile', 'trim|max_length[30]');
		$this->form_validation->set_rules('country', 'Country', 'trim|max_length[80]');
		$this->form_validation->set_rules('status', 'Status', 'required|in_list[active,pending,blocked]');

		if ( ! $this->form_validation->run())
		{
			$this->session->set_flashdata('error', validation_errors(' ', ' '));
			redirect('admin/users/view/'.$id);
		}

		$email = $this->input->post('email', TRUE);
		$taken = $this->user_model->find_by(array('email' => $email));

		if ($taken && (int) $taken->id !== (int) $user->id)
		{
			$this->session->set_flashdata('error', 'That email belongs to another account.');
			redirect('admin/users/view/'.$id);
		}

		$data = array(
			'full_name' => $this->input->post('full_name', TRUE),
			'email'     => $email,
			'mobile'    => $this->input->post('mobile', TRUE),
			'country'   => $this->input->post('country', TRUE),
			'status'    => $this->input->post('status', TRUE),
		);

		if ($new_password = $this->input->post('password'))
		{
			if (strlen($new_password) < 6)
			{
				$this->session->set_flashdata('error', 'New password must be at least 6 characters.');
				redirect('admin/users/view/'.$id);
			}
			$data['password'] = password_hash($new_password, PASSWORD_BCRYPT);
		}

		$this->user_model->update($user->id, $data);
		$this->log_action('Updated user', 'users', $user->id);

		$this->session->set_flashdata('success', 'User updated.');
		redirect('admin/users/view/'.$id);
	}

	/**
	 * Manual balance correction. Goes through Wallet_lib like everything else,
	 * so the ledger stays the source of truth.
	 */
	public function adjust($id)
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$user = $this->user_model->find($id);

		if ( ! $user)
		{
			show_404();
		}

		$amount    = round((float) $this->input->post('amount'), MONEY_SCALE);
		$direction = $this->input->post('direction', TRUE);
		$reason    = $this->input->post('reason', TRUE);

		if ($amount <= 0)
		{
			$this->session->set_flashdata('error', 'Enter an amount greater than zero.');
			redirect('admin/users/view/'.$id);
		}
		if ( ! in_array($direction, array('credit', 'debit'), TRUE))
		{
			$this->session->set_flashdata('error', 'Choose credit or debit.');
			redirect('admin/users/view/'.$id);
		}
		if ( ! $reason)
		{
			$this->session->set_flashdata('error', 'A reason is required for manual balance changes.');
			redirect('admin/users/view/'.$id);
		}

		$description = 'Admin '.$direction.' by '.$this->admin->username.': '.$reason;

		// Wrapped so the row lock Wallet_lib takes is held until the ledger
		// row is written; without it two admins could adjust concurrently.
		$this->db->trans_start();

		$ok = ($direction === 'credit')
			? $this->wallet_lib->credit($user->id, $amount, 'admin_credit', 'users', $user->id, $description)
			: $this->wallet_lib->debit($user->id, $amount, 'admin_debit', 'users', $user->id, $description);

		$this->db->trans_complete();

		if ( ! $ok || $this->db->trans_status() === FALSE)
		{
			$this->session->set_flashdata('error', 'Adjustment failed. A debit cannot take the balance below zero.');
			redirect('admin/users/view/'.$id);
		}

		$this->log_action('Balance '.$direction, 'users', $user->id, money($amount).' - '.$reason);
		$this->notification_model->push($user->id, 'Balance adjusted',
			ucfirst($direction).' of '.money($amount).'. '.$reason, 'transactions');

		$this->session->set_flashdata('success', 'Balance '.$direction.'ed by '.money($amount).'.');
		redirect('admin/users/view/'.$id);
	}

	public function status($id, $status)
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		if ( ! in_array($status, array('active', 'pending', 'blocked'), TRUE))
		{
			show_404();
		}

		$user = $this->user_model->find($id);

		if ( ! $user)
		{
			show_404();
		}

		$this->user_model->update($user->id, array('status' => $status));
		$this->log_action('Set user status to '.$status, 'users', $user->id);

		$this->session->set_flashdata('success', $user->username.' is now '.$status.'.');
		redirect('admin/users/view/'.$id);
	}
}
