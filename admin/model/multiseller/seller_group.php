<?php
class ModelMultisellerSellerGroup extends Model {
	public function addSellerGroup($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller_group SET fee_show_flat = '" . (float)$data['fee_show_flat'] . "', fee_show_percent = '" . (float)$data['fee_show_percent'] . "', fee_sale_flat = '" . (float)$data['fee_sale_flat'] . "', fee_sale_percent = '" . (float)$data['fee_sale_percent'] . "', product_quantity = '" . (int)$data['product_quantity'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW(), date_modified = NOW()");

		$seller_group_id = $this->db->getLastId();

		foreach ($data['seller_group_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller_group_description SET seller_group_id = '" . (int)$seller_group_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "'");
		}
		
		return $seller_group_id;
	}

	public function editSellerGroup($seller_group_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "ms_seller_group SET fee_show_flat = '" . (float)$data['fee_show_flat'] . "', fee_show_percent = '" . (float)$data['fee_show_percent'] . "', fee_sale_flat = '" . (float)$data['fee_sale_flat'] . "', fee_sale_percent = '" . (float)$data['fee_sale_percent'] . "', product_quantity = '" . (int)$data['product_quantity'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE seller_group_id = '" . (int)$seller_group_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "ms_seller_group_description WHERE seller_group_id = '" . (int)$seller_group_id . "'");

		foreach ($data['seller_group_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller_group_description SET seller_group_id = '" . (int)$seller_group_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "'");
		}
	}

	public function deleteSellerGroup($seller_group_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ms_seller_group WHERE seller_group_id = '" . (int)$seller_group_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "ms_seller_group_description WHERE seller_group_id = '" . (int)$seller_group_id . "'");
	}

	public function getSellerGroup($seller_group_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "ms_seller_group sg LEFT JOIN " . DB_PREFIX . "ms_seller_group_description sgd ON (sg.seller_group_id = sgd.seller_group_id) WHERE sg.seller_group_id = '" . (int)$seller_group_id . "' AND sgd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getSellerGroups($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "ms_seller_group sg LEFT JOIN " . DB_PREFIX . "ms_seller_group_description sgd ON (sg.seller_group_id = sgd.seller_group_id) WHERE sgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		$sort_data = array(
			'sgd.name',
			'sg.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY sgd.name";
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

	public function getSellerGroupDescriptions($seller_group_id) {
		$seller_group_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ms_seller_group_description WHERE seller_group_id = '" . (int)$seller_group_id . "'");

		foreach ($query->rows as $result) {
			$seller_group_data[$result['language_id']] = array(
				'name'        => $result['name'],
				'description' => $result['description']
			);
		}

		return $seller_group_data;
	}

	public function getTotalSellerGroups() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ms_seller_group");

		return $query->row['total'];
	}
}
