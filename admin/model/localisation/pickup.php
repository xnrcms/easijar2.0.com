<?php
class ModelLocalisationPickup extends Model {
	public function addPickup($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "pickup SET name = '" . $this->db->escape((string)$data['name']) . "', address = '" . $this->db->escape((string)$data['address']) . "', geocode = '" . $this->db->escape((string)$data['geocode']) . "', telephone = '" . $this->db->escape((string)$data['telephone']) . "', country_id = '" . (int)$data['country_id'] . "', zone_id = '" . (int)$data['zone_id'] . "', open = '" . $this->db->escape((string)$data['open']) . "', comment = '" . $this->db->escape((string)$data['comment']) . "'");
	
		return $this->db->getLastId();
	}

	public function editPickup($pickup_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "pickup SET name = '" . $this->db->escape((string)$data['name']) . "', address = '" . $this->db->escape((string)$data['address']) . "', geocode = '" . $this->db->escape((string)$data['geocode']) . "', telephone = '" . $this->db->escape((string)$data['telephone']) . "', country_id = '" . (int)$data['country_id'] . "', zone_id = '" . (int)$data['zone_id'] . "', open = '" . $this->db->escape((string)$data['open']) . "', comment = '" . $this->db->escape((string)$data['comment']) . "' WHERE pickup_id = '" . (int)$pickup_id . "'");
	}

	public function deletePickup($pickup_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "pickup WHERE pickup_id = " . (int)$pickup_id);
	}

	public function getPickup($pickup_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "pickup WHERE pickup_id = '" . (int)$pickup_id . "'");

		return $query->row;
	}

	public function getPickups($data = array()) {
		$sql = "SELECT pickup_id, name, address FROM " . DB_PREFIX . "pickup";

		$sort_data = array(
			'name',
			'address',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
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

	public function getTotalPickups() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "pickup");

		return $query->row['total'];
	}
}
