<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Per-account admin grants.
 *
 * One row per granted capability. A `super_admin` never has rows - the guard
 * short-circuits before it reaches this table - so an empty result for a super
 * admin means "everything", and for anyone else it means "nothing".
 */
class Admin_permission_model extends MY_Model {

	protected $table       = 'admin_permissions';
	protected $primary_key = 'admin_id';

	/** @var array|null flat list of every key in the catalogue, memoised */
	private $catalogue = NULL;

	/** Flat list of the permissions granted to one account. @return string[] */
	public function for_admin($admin_id)
	{
		$rows = $this->db->select('permission')
			->where('admin_id', (int) $admin_id)
			->get($this->table)
			->result();

		$out = array();
		foreach ($rows as $row)
		{
			$out[] = $row->permission;
		}

		return $out;
	}

	/** admin_id => count, for the listing screen. @return array */
	public function counts()
	{
		$rows = $this->db->select('admin_id, COUNT(*) AS total')
			->group_by('admin_id')
			->get($this->table)
			->result();

		$out = array();
		foreach ($rows as $row)
		{
			$out[(int) $row->admin_id] = (int) $row->total;
		}

		return $out;
	}

	/**
	 * Replaces an account's grants wholesale.
	 *
	 * Anything not in the catalogue is dropped, so a hand-crafted `perms[]`
	 * post cannot invent a capability. Delete-then-insert runs in one
	 * transaction: a half-applied matrix would silently widen or narrow access.
	 *
	 * @return int number of grants stored
	 */
	public function sync($admin_id, $perms)
	{
		$admin_id = (int) $admin_id;
		$perms    = array_values(array_unique(array_intersect(
			array_map('strval', (array) $perms),
			$this->catalogue()
		)));

		$this->db->trans_start();

		$this->db->where('admin_id', $admin_id)->delete($this->table);

		if ($perms)
		{
			$batch = array();
			foreach ($perms as $perm)
			{
				$batch[] = array('admin_id' => $admin_id, 'permission' => $perm);
			}
			$this->db->insert_batch($this->table, $batch);
		}

		$this->db->trans_complete();

		return count($perms);
	}

	/** Drops every grant an account holds. Used when it is promoted to super admin. */
	public function clear($admin_id)
	{
		$this->db->where('admin_id', (int) $admin_id)->delete($this->table);
	}

	/** Seeds an account from its role preset. @return int */
	public function apply_preset($admin_id, $role)
	{
		return $this->sync($admin_id, self::preset($role));
	}

	/**
	 * The checkbox set a role starts with. `super_admin` maps to '*' in the
	 * config and stores nothing, so it comes back empty here on purpose.
	 *
	 * @return string[]
	 */
	public static function preset($role)
	{
		$presets = get_instance()->config->item('admin_role_presets');
		$preset  = isset($presets[$role]) ? $presets[$role] : array();

		return is_array($preset) ? $preset : array();
	}

	/** Every permission key the catalogue defines. @return string[] */
	public function catalogue()
	{
		if ($this->catalogue === NULL)
		{
			$this->catalogue = array();
			foreach ((array) $this->config->item('admin_permissions') as $module)
			{
				foreach (array_keys($module['perms']) as $key)
				{
					$this->catalogue[] = $key;
				}
			}
		}

		return $this->catalogue;
	}
}
