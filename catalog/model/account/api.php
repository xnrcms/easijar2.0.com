<?php
class ModelAccountApi extends Model {
	public function login($username, $key) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "api` WHERE `username` = '" . $this->db->escape($username) . "' AND `key` = '" . $this->db->escape($key) . "' AND status = '1'");

		return $query->row;
	}

	public function addApiSession($api_id, $session_id, $ip) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "api_session` SET api_id = '" . (int)$api_id . "', session_id = '" . $this->db->escape($session_id) . "', ip = '" . $this->db->escape($ip) . "', date_added = NOW(), date_modified = NOW()");

		return $this->db->getLastId();
	}

	public function getApiIps($api_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "api_ip` WHERE api_id = '" . (int)$api_id . "'");

		return $query->rows;
	}

	public function addApi($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "api` SET username = '" . $this->db->escape((string)$data['username']) . "', `key` = '" . $this->db->escape((string)$data['key']) . "', status = '" . (int)$data['status'] . "',code='" . $this->db->escape((string)$data['code']) . "', date_added = NOW(), date_modified = NOW()");

		$api_id = $this->db->getLastId();

		if (isset($data['api_ip'])) {
			foreach ($data['api_ip'] as $ip) {
				if ($ip) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "api_ip` SET api_id = '" . (int)$api_id . "', ip = '" . $this->db->escape($ip) . "'");
				}
			}
		}
		
		return $api_id;
	}

	public function delApiSession($api_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "api_session WHERE api_id = '" . (int)$api_id . "'");
	}

	public function getApiInfoByCode($code){
		$query = $this->db->query("SELECT `api_id`,`username`,`key`,`status` FROM `" . DB_PREFIX . "api` WHERE `code` = '" . $this->db->escape($code) . "' AND status = '1'");
		return $query->row;
	}

	public function getApiIdByToken($token = ''){
		$api_query = $this->db->query("SELECT DISTINCT a.`api_id` FROM " . get_tabname('api') . " `a` LEFT JOIN " . get_tabname('api_session') . " `as` ON (a.api_id = as.api_id) LEFT JOIN " . get_tabname('api_ip') . " `ai` ON (a.api_id = ai.api_id) WHERE a.status = '1' AND `as`.`session_id` = '" . $this->db->escape((string)$token) . "'");
		return isset($api_query->row['api_id']) ? (int)$api_query->row['api_id'] : '';
	}
}
