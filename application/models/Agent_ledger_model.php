<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read side of the agent ledger.
 *
 * Rows are written only by Agent_wallet_lib, never here - this model has no
 * insert of its own on purpose, so there is exactly one way an agent balance
 * can change.
 */
class Agent_ledger_model extends MY_Model {

	protected $table = 'agent_ledger';

	/**
	 * Paged history for one agent, optionally narrowed to one wallet or one
	 * movement type.
	 */
	public function for_agent($agent_id, $limit, $offset = 0, $wallet = '', $type = '')
	{
		$build = function () use ($agent_id, $wallet, $type) {
			$this->db->from($this->table)->where('agent_id', (int) $agent_id);

			if ($wallet !== '')
			{
				$this->db->where('wallet', $wallet);
			}
			if ($type !== '')
			{
				$this->db->where('type', $type);
			}
		};

		$build();
		$total = (int) $this->db->count_all_results();

		$build();
		$rows = $this->db->order_by('id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result();

		return array('rows' => $rows, 'total' => $total);
	}

	/** The newest few, for the agent dashboard. */
	public function recent($agent_id, $limit = 10)
	{
		return $this->db->where('agent_id', (int) $agent_id)
			->order_by('id', 'DESC')->limit((int) $limit)
			->get($this->table)->result();
	}

	/**
	 * Signed total for one movement type on one wallet. Used by the agent
	 * earnings screen, which reports lifetime commission separately from the
	 * balance still sitting in the wallet.
	 */
	public function sum_type($agent_id, $wallet, $type)
	{
		$row = $this->db->select_sum('amount', 'total')
			->where('agent_id', (int) $agent_id)
			->where('wallet', $wallet)
			->where('type', $type)
			->get($this->table)->row();

		return (float) ($row->total ?: 0);
	}

	/**
	 * Has this exact movement already been written?
	 *
	 * The reference pair is what makes a settle idempotent: a double-submitted
	 * accept must not debit the float twice. Callers check this inside the
	 * same transaction as the write.
	 */
	public function exists_for($agent_id, $type, $ref_table, $ref_id)
	{
		return (int) $this->db
			->where('agent_id', (int) $agent_id)
			->where('type', $type)
			->where('reference_table', $ref_table)
			->where('reference_id', (int) $ref_id)
			->count_all_results($this->table) > 0;
	}
}
