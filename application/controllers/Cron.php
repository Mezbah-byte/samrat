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
