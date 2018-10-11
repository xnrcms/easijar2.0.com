<?php
class ModelMultisellerTransaction extends Model {
	public function getTransactions($data = array()) {
		$sql = "SELECT t.*, c.fullname AS name, s.store_name AS store_name FROM " . DB_PREFIX . "ms_seller_transaction t 
		        LEFT JOIN " . DB_PREFIX . "ms_seller s ON (s.seller_id = t.seller_id) 
		        LEFT JOIN " . DB_PREFIX . "customer c ON (t.seller_id = c.customer_id) 
		        WHERE 1=1";

		if (!empty($data['filter_name'])) {
			$sql .= " AND c.fullname LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
		}

		if (!empty($data['filter_store_name'])) {
			$sql .= " AND s.store_name LIKE '" . $this->db->escape((string)$data['filter_store_name']) . "%'";
		}

		if (!empty($data['filter_description'])) {
			$sql .= " AND t.description LIKE '" . $this->db->escape((string)$data['filter_description']) . "%'";
		}

		if (!empty($data['filter_amount'])) {
			$sql .= " AND t.amount = '" . (int)$data['filter_amount'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(t.date_added) = DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		$sort_data = array(
			'c.fullname',
			's.store_name',
			't.amount',
			't.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY c.fullname";
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

	public function getTotalTransactions($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ms_seller_transaction t
		        LEFT JOIN " . DB_PREFIX . "ms_seller s ON (s.seller_id = t.seller_id) 
		        LEFT JOIN " . DB_PREFIX . "customer c ON (t.seller_id = c.customer_id) 
		        WHERE 1=1";

		if (!empty($data['filter_name'])) {
			$sql .= " AND c.fullname LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
		}

		if (!empty($data['filter_store_name'])) {
			$sql .= " AND s.store_name LIKE '" . $this->db->escape((string)$data['filter_store_name']) . "%'";
		}

		if (!empty($data['filter_description'])) {
			$sql .= " AND t.description LIKE '" . $this->db->escape((string)$data['filter_description']) . "%'";
		}

		if (!empty($data['filter_amount'])) {
			$sql .= " AND t.amount = '" . (int)$data['filter_amount'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(t.date_added) = DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}
}
