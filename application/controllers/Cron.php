<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily job runner.
 *
 *   CLI  :  php index.php cron run
 *   HTTP :  /cron/run?key=<cron_secret from admin settings>
 *
 * Both paths execute the same code. The job is idempotent — the unique index
 * on daily_earnings(investment_id, earn_date) means a double run cannot pay
 * anyone twice.
 *
 * There is a second, much lighter job here: agent_timeouts(). The float
 * system's escalation window is measured in hours, so it wants its own
 * schedule — every 15 minutes or so — rather than waiting for the nightly
 * run. The daily job calls it too, so a platform with only one cron entry
 * still escalates, just less promptly.
 *
 *   CLI  :  php index.php cron agent_timeouts
 *   HTTP :  /cron/agent-timeouts?key=<cron_secret>
 */
class Cron extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library(array('investment_lib', 'team_bonus_lib'));
	}

	public function index()
	{
		$this->run();
	}

	public function run()
	{
		$this->authorise();

		$started = microtime(TRUE);
		$report  = $this->investment_lib->run_daily_cron();

		// The team bonus counters only ever move up on deposit approval, so a
		// deposit edited or reversed by hand would leave a progress bar reading
		// too high forever. This rebuilds them from `deposits`, which is the
		// only authority, and re-checks anyone it corrected.
		$team = $this->team_bonus_lib->recompute();
		$report['team_scanned']   = $team['scanned'];
		$report['team_corrected'] = $team['corrected'];

		$expired = $this->expire_agent_requests();
		$report['agent_deposits_expired']    = $expired['deposits'];
		$report['agent_withdrawals_expired'] = $expired['withdrawals'];

		$report['date']        = date('Y-m-d H:i:s');
		$report['duration_ms'] = (int) round((microtime(TRUE) - $started) * 1000);

		log_message('info', 'Cron daily run: '.json_encode($report));

		if (is_cli())
		{
			echo "Daily cron finished\n";
			foreach ($report as $key => $value)
			{
				echo '  '.str_pad($key, 14).': '.$value."\n";
			}
			return;
		}

		$this->json(array('success' => TRUE, 'report' => $report));
	}

	/**
	 * Escalation sweep on its own schedule.
	 *
	 * Cheap enough to run every few minutes: two indexed lookups when there is
	 * nothing to do, and a status change plus a notification when there is. No
	 * balance moves, so a double run is a no-op rather than a double payment.
	 */
	public function agent_timeouts()
	{
		$this->authorise();

		$started = microtime(TRUE);
		$report  = $this->expire_agent_requests();

		$report['date']        = date('Y-m-d H:i:s');
		$report['duration_ms'] = (int) round((microtime(TRUE) - $started) * 1000);

		if ($report['deposits'] || $report['withdrawals'])
		{
			log_message('info', 'Cron agent timeouts: '.json_encode($report));
		}

		if (is_cli())
		{
			echo 'Agent timeout sweep: '.$report['deposits'].' deposit(s), '
				.$report['withdrawals']." withdrawal(s) escalated\n";
			return;
		}

		$this->json(array('success' => TRUE, 'report' => $report));
	}

	/** Shared by run() and agent_timeouts(). Skipped when the feature is off. */
	protected function expire_agent_requests()
	{
		if ($this->setting_model->get('agent_float_enabled', '0') !== '1')
		{
			return array('deposits' => 0, 'withdrawals' => 0);
		}

		$this->load->library('agent_settle_lib');

		return $this->agent_settle_lib->expire_stale();
	}

	/** CLI runs unauthenticated; HTTP needs the shared secret. */
	protected function authorise()
	{
		if (is_cli())
		{
			return;
		}

		$expected = (string) $this->setting_model->get('cron_secret', '');
		$provided = (string) $this->input->get('key', TRUE);

		if ($expected === '' || ! hash_equals($expected, $provided))
		{
			$this->output->set_status_header(403)
				->set_content_type('application/json')
				->set_output(json_encode(array('success' => FALSE, 'message' => 'Forbidden')));
			exit;
		}
	}
}
