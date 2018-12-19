<?php
class ModelRongcloudRongcloud extends Model {
	public function addUser($data = []) {
		$this->db->query("INSERT INTO " . get_tabname('customer_rongcloud') . " SET customer_id = '" . (int)$data['customer_id'] . 
			"', rongcloud_avatar = '" . $this->db->escape((string)$data['rongcloud_avatar']) . 
			"', rongcloud_token = '" . $this->db->escape((string)$data['rongcloud_token']) . 
			"', validity_time = '" . (int)$data['validity_time'] . 
			"', rongcloud_uid = '" . $this->db->escape((string)$data['rongcloud_uid']) . 
			"', rongcloud_nickname = '" . $this->db->escape((string)$data['rongcloud_nickname']) . 
			"', date_added = NOW()" .
			", date_modified = NOW()");
	}

	public function getUser($data = []) {
		$query = $this->db->query("SELECT * FROM " . get_tabname('customer_rongcloud') . " WHERE customer_id = '" . (int)$data['customer_id'] . "'");

		return $query->row;
	}

	public function updateUser($data = []) {
		$this->db->query("UPDATE " . get_tabname('customer_rongcloud') . " SET date_modified = 'NOW()" . 
			(isset($data['rongcloud_avatar']) ? "', rongcloud_avatar = '" . $this->db->escape((string)$data['rongcloud_avatar']) : '') .
			(isset($data['rongcloud_token']) ? "', rongcloud_token = '" . $this->db->escape((string)$data['rongcloud_token']) : '') .
			"', validity_time = '" . (int)$data['validity_time'] . 
			(isset($data['rongcloud_uid']) ? "', rongcloud_uid = '" . $this->db->escape((string)$data['rongcloud_uid']) : '') .
			(isset($data['rongcloud_nickname']) ? "', rongcloud_nickname = '" . $this->db->escape((string)$data['rongcloud_nickname']) : '') .
			"' WHERE customer_id = '" . (int)$data['customer_id'] . "'");
	}
}
