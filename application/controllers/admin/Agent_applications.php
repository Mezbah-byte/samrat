<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Review queue for agentship applications.
 *
 * Approving does not create the agent here: it hands off to
 * admin/Agents::create($application_id), where the admin types the password
 * by hand. That is deliberate - no password is ever generated or mailed.
 */
class Agent_applications extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('agent_application_model', 'notification_model'));
	}

	public function index()
	{
		$this->require_role(array('super_admin', 'admin'));

		$per_page = 20;
		$page     = max(1, (int) $this->input->get('page'));
		$status   = $this->input->get('status', TRUE) ?: '';
		$search   = $this->input->get('q', TRUE) ?: '';

		$result = $this->agent_application_model->paginate_admin($per_page, ($page - 1) * $per_page, $status, $search);

		$this->render('admin/agent_applications', array(
			'page_title'    => 'Agentship Applications',
			'active_menu'   => 'agent_applications',
			'rows'          => $result['rows'],
			'total'         => $result['total'],
			'per_page'      => $per_page,
			'page'          => $page,
			'status'        => $status,
			'search'        => $search,
			'pending_count' => $this->agent_application_model->pending_count(),
			'threshold'     => (int) $this->setting_model->get('agent_min_team_size', 50),
		));
	}

	public function view($id)
	{
		$this->require_role(array('super_admin', 'admin'));

		$application = $this->agent_application_model->find_detailed($id);

		if ( ! $application)
		{
			show_404();
		}

		// The snapshot is what they had at submission; this is where they are
		// now. A team that has since shrunk is worth seeing before approving.
		$this->load->model('user_model');

		$this->render('admin/agent_application_view', array(
			'page_title'  => 'Application #'.$application->id,
			'active_menu' => 'agent_applications',
			'application' => $application,
			'team_now'    => $this->user_model->active_downline_count($application->user_id),
			'threshold'   => (int) $this->setting_model->get('agent_min_team_size', 50),
		));
	}

	/**
	 * Hands off to the agent create form, prefilled. The application is only
	 * marked approved once that form is saved, so abandoning it halfway leaves
	 * the application pending rather than approved-with-no-agent.
	 */
	public function approve($id)
	{
		$this->require_role(array('super_admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$application = $this->agent_application_model->find($id);

		if ( ! $application)
		{
			show_404();
		}

		if ($application->status !== 'pending')
		{
			$this->session->set_flashdata('error', 'This application is already '.$application->status.'.');
			redirect('admin/agent-applications/view/'.$id);
		}

		redirect('admin/agents/create/'.$id);
	}

	public function reject($id)
	{
		$this->require_role(array('super_admin', 'admin'));

		if ($this->input->method() !== 'post')
		{
			show_error('Method not allowed.', 405);
		}

		$application = $this->agent_application_model->find($id);

		if ( ! $application)
		{
			show_404();
		}

		if ($application->status !== 'pending')
		{
			$this->session->set_flashdata('error', 'This application is already '.$application->status.'.');
			redirect('admin/agent-applications/view/'.$id);
		}

		$note = $this->input->post('admin_note', TRUE);

		if ( ! $note)
		{
			$this->session->set_flashdata('error', 'Give a reason so the applicant knows what to fix.');
			redirect('admin/agent-applications/view/'.$id);
		}

		$this->agent_application_model->update($id, array(
			'status'      => 'rejected',
			'admin_note'  => $note,
			'reviewed_by' => $this->admin->id,
			'reviewed_at' => date('Y-m-d H:i:s'),
		));

		$this->log_action('Rejected agentship application', 'agent_applications', $id, $note);

		$this->notification_model->push(
			$application->user_id,
			'Agentship application declined',
			'Your agentship application was not approved. Reason: '.$note.' You can apply again.',
			'agentship'
		);

		$this->session->set_flashdata('success', 'Application rejected and the applicant notified.');
		redirect('admin/agent-applications');
	}

	/**
	 * Serves one NID scan from behind the admin session. uploads/nid/ is
	 * blocked at the webserver, so this is the only way to read one.
	 *
	 * $side picks between two fixed column names; it never touches the path.
	 */
	public function nid($id, $side = 'front')
	{
		$this->require_role(array('super_admin', 'admin'));

		$application = $this->agent_application_model->find($id);

		if ( ! $application)
		{
			show_404();
		}

		stream_nid($application, $side);
	}
}
