<?php
class ModelUserUser extends Model {
	public function addUser($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "user` SET username = '" . $this->db->escape((string)$data['username']) . "', user_group_id = '" . (int)$data['user_group_id'] . "', salt = '', password = '" . $this->db->escape(password_hash($data['password'], PASSWORD_DEFAULT)) . "', fullname = '" . $this->db->escape((string)$data['fullname']) . "', email = '" . $this->db->escape((string)$data['email']) . "', image = '" . $this->db->escape((string)$data['image']) . "', status = '" . (int)$data['status'] . "', default_language = '" . $data['default_language'] . "', date_added = NOW()");
	
		return $this->db->getLastId();
	}

	public function editUser($user_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user` SET username = '" . $this->db->escape((string)$data['username']) . "', user_group_id = '" . (int)$data['user_group_id'] . "', fullname = '" . $this->db->escape((string)$data['fullname']) . "', email = '" . $this->db->escape((string)$data['email']) . "', image = '" . $this->db->escape((string)$data['image']) . "', status = '" . (int)$data['status'] . "', default_language = '" . $data['default_language'] . "' WHERE user_id = '" . (int)$user_id . "'");
		if ((int)$user_id == 1) {
			$data['status'] = 1;
		}

		if ($data['password']) {
			$this->db->query("UPDATE `" . DB_PREFIX . "user` SET salt = '', password = '" . $this->db->escape(password_hash($data['password'], PASSWORD_DEFAULT)) . "' WHERE user_id = '" . (int)$user_id . "'");
		}
	}

	public function editPassword($user_id, $password) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user` SET salt = '', password = '" . $this->db->escape(password_hash($password, PASSWORD_DEFAULT)) . "', code = '' WHERE user_id = '" . (int)$user_id . "'");
	}

	public function editCode($email, $code) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user` SET code = '" . $this->db->escape($code) . "' WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");
	}

	public function deleteUser($user_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "user` WHERE user_id = '" . (int)$user_id . "'");
	}

	public function getUser($user_id) {
		$query = $this->db->query("SELECT *, (SELECT ug.name FROM `" . DB_PREFIX . "user_group` ug WHERE ug.user_group_id = u.user_group_id) AS user_group FROM `" . DB_PREFIX . "user` u WHERE u.user_id = '" . (int)$user_id . "'");

		return $query->row;
	}

	public function getUserByUsername($username) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "user` WHERE username = '" . $this->db->escape($username) . "'");

		return $query->row;
	}

	public function getUserByEmail($email) {
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "user` WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row;
	}

	public function getUserByCode($code) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "user` WHERE code = '" . $this->db->escape($code) . "' AND code != ''");

		return $query->row;
	}

	public function getUsers($data = array()) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "user` where 1";

		$sort_data = array(
			'username',
			'status',
			'date_added'
		);

		if (!empty($data['filter_name'])) {
			$sql .= " AND fullname LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
			$sql .= " OR username LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
		}

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY username";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalUsers() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "user`");

		return $query->row['total'];
	}

	public function getTotalUsersByGroupId($user_group_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "user` WHERE user_group_id = '" . (int)$user_group_id . "'");

		return $query->row['total'];
	}

	public function getTotalUsersByEmail($email) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "user` WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row['total'];
	}
}
