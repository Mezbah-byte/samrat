<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The contact channels shown on the Support page, the dashboard card and the
 * public footer. One row per channel, ordered and switchable by an admin.
 */
class Support_link_model extends MY_Model {

	protected $table     = 'support_links';
	protected $order_by  = 'sort_order';
	protected $order_dir = 'ASC';

	/**
	 * Published channels, in display order.
	 *
	 * Ordered by sort_order then id so two rows sharing an order still come
	 * out in a stable sequence rather than whatever the storage engine feels
	 * like returning.
	 */
	public function active($limit = NULL)
	{
		$this->db->where('status', 'active')
			->order_by('sort_order', 'ASC')->order_by('id', 'ASC');

		if ($limit !== NULL)
		{
			$this->db->limit((int) $limit);
		}

		return $this->db->get($this->table)->result();
	}

	/** Used by the admin form to keep two channels from sharing a name. */
	public function label_taken($label, $ignore_id = NULL)
	{
		$this->db->where('label', $label);

		if ($ignore_id)
		{
			$this->db->where('id !=', (int) $ignore_id);
		}

		return (int) $this->db->count_all_results($this->table) > 0;
	}
}
