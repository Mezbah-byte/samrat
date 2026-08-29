<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Daily_earning_model extends MY_Model {

	protected $table = 'daily_earnings';

	public function for_investment_date($investment_id, $date)
	{
		return $this->db->get_where($this->table, array(
			'investment_id' => (int) $investment_id,
			'earn_date'     => $date,
		), 1)->row();
	}

	/** Today's rows for every active investment the user holds. */
	public function today_rows($user_id, $date = NULL)
	{
		$date = $date ?: date('Y-m-d');
		return $this->db->where('user_id', (int) $user_id)->where('earn_date', $date)
			->order_by('id', 'ASC')->get($this->table)->result();
	}

	/** Latest earn_date already materialised for an investment, or NULL. */
	public function last_date($investment_id)
	{
		$row = $this->db->select_max('earn_date', 'd')
			->where('investment_id', (int) $investment_id)->get($this->table)->row();
		return ($row && $row->d) ? $row->d : NULL;
	}

	public function for_user($user_id, $limit, $offset = 0)
	{
		$this->db->select('e.*, p.name AS package_name')
			->from('daily_earnings e')
			->join('investments i', 'i.id = e.investment_id', 'left')
			->join('packages p', 'p.id = i.package_id', 'left')
			->where('e.user_id', (int) $user_id)
			->order_by('e.earn_date', 'DESC')->order_by('e.id', 'DESC')
			->limit((int) $limit, (int) $offset);
		$rows  = $this->db->get()->result();
		$total = (int) $this->db->where('user_id', (int) $user_id)->count_all_results($this->table);

		return array('rows' => $rows, 'total' => $total);
	}

	public function credited_total($user_id)
	{
		$row = $this->db->select_sum('amount', 'total')
			->where('user_id', (int) $user_id)->where('status', 'credited')
			->get($this->table)->row();
		return (float) ($row->total ?: 0);
	}
}
