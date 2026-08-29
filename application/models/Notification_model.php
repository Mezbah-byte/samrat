<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends MY_Model {

	protected $table = 'notifications';

	public function unread_count($user_id)
	{
		return (int) $this->db->where('user_id', (int) $user_id)->where('is_read', 0)->count_all_results($this->table);
	}

	public function for_user($user_id, $limit, $offset = 0)
	{
		return $this->paginate($limit, $offset, array('user_id' => (int) $user_id));
	}

	public function push($user_id, $title, $message, $link = NULL)
	{
		return $this->insert(array(
			'user_id' => $user_id,
			'title'   => $title,
			'message' => $message,
			'link'    => $link,
		));
	}

	/** Broadcast: one row per active user so read state stays per-user. */
	public function broadcast($title, $message, $link = NULL)
	{
		$users = $this->db->select('id')->where('status', 'active')->get('users')->result();
		if (empty($users))
		{
			return 0;
		}
		$batch = array();
		foreach ($users as $u)
		{
			$batch[] = array('user_id' => $u->id, 'title' => $title, 'message' => $message, 'link' => $link);
		}
		$this->db->insert_batch($this->table, $batch);
		return count($batch);
	}

	public function mark_read($id, $user_id)
	{
		$this->db->where('id', (int) $id)->where('user_id', (int) $user_id)
			->update($this->table, array('is_read' => 1));
	}

	public function mark_all_read($user_id)
	{
		$this->db->where('user_id', (int) $user_id)->update($this->table, array('is_read' => 1));
	}
}
