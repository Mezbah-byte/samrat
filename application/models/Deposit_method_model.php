<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Deposit_method_model extends MY_Model {

	protected $table     = 'deposit_methods';
	protected $order_by  = 'sort_order';
	protected $order_dir = 'ASC';

	public function active()
	{
		return $this->db->where('status', 'active')->order_by('sort_order', 'ASC')->get($this->table)->result();
	}
}
