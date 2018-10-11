<?php
class ModelMultisellerSeller extends Model {
	public function addSeller($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller SET seller_id = '" . (int)$data['seller_id'] . "', seller_group_id = '" . (int)$data['seller_group_id'] . "', store_name = '" . $this->db->escape((string)$data['store_name']) . "', company = '" . $this->db->escape((string)$data['company']) . "', description = '" . $this->db->escape((string)$data['description']) . "', country_id = '" . (int)$data['country_id'] . "', zone_id = '" . (int)$data['zone_id'] . "', city_id = '" . (int)$data['city_id'] . "', county_id = '" . (int)$data['county_id'] . "', avatar = '" . $this->db->escape((string)$data['avatar']) . "', banner = '" . $this->db->escape((string)$data['banner']) . "', alipay = '" . $this->db->escape((string)$data['alipay'])  . "', chat_key = '" . $this->db->escape((string)$data['chat_key']). "', product_validation = '" . (int)$data['product_validation'] . "', status = '" . (int)$data['status'] . "', date_added = NOW(), date_modified = NOW()");

		return $data['seller_id'];
	}

	public function editSeller($seller_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "ms_seller SET seller_group_id = '" . (int)$data['seller_group_id'] . "', store_name = '" . $this->db->escape((string)$data['store_name']) . "', company = '" . $this->db->escape((string)$data['company']) . "', description = '" . $this->db->escape((string)$data['description']) . "', country_id = '" . (int)$data['country_id'] . "', zone_id = '" . (int)$data['zone_id'] . "', city_id = '" . (int)$data['city_id'] . "', county_id = '" . (int)$data['county_id'] . "', avatar = '" . $this->db->escape((string)$data['avatar']) . "', banner = '" . $this->db->escape((string)$data['banner']) . "', alipay = '" . $this->db->escape((string)$data['alipay']) . "', chat_key = '" . $this->db->escape((string)$data['chat_key']) . "', product_validation = '" . (int)$data['product_validation'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE seller_id = '" . (int)$seller_id . "'");
    }

	public function getSeller($seller_id) {
		$query = $this->db->query("SELECT DISTINCT s.*, c.fullname AS customer FROM " . DB_PREFIX . "ms_seller s LEFT JOIN `" . DB_PREFIX . "customer` c ON (s.seller_id = c.customer_id) WHERE s.seller_id = '" . (int)$seller_id . "'");

		return $query->row;
	}

	public function getSellers($data = array()) {
		$sql = "SELECT s.*, c.fullname AS name, sgd.name AS seller_group FROM " . DB_PREFIX . "ms_seller s LEFT JOIN " . DB_PREFIX . "ms_seller_group_description sgd ON (s.seller_group_id = sgd.seller_group_id) LEFT JOIN " . DB_PREFIX . "customer c ON (s.seller_id = c.customer_id) WHERE sgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND fullname LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
		}

		if (!empty($data['filter_store_name'])) {
			$sql .= " AND s.store_name LIKE '" . $this->db->escape((string)$data['filter_store_name']) . "%'";
		}

		if (!empty($data['filter_company'])) {
			$sql .= " AND s.company LIKE '" . $this->db->escape((string)$data['filter_company']) . "%'";
		}

		if (!empty($data['filter_seller_group_id'])) {
			$sql .= " AND s.seller_group_id = '" . (int)$data['filter_seller_group_id'] . "'";
		}
		
		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND s.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(s.date_added) = DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		$sort_data = array(
			'fullname',
			'store_name',
			'company',
			'seller_group_id',
			'status',
			'date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY fullname";
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

	public function getTotalSellers($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ms_seller s LEFT JOIN " . DB_PREFIX . "customer c ON (s.seller_id = c.customer_id) WHERE 1=1";

		if (!empty($data['filter_name'])) {
			$sql .= " AND fullname LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
		}

		if (!empty($data['filter_store_name'])) {
			$sql .= " AND store_name LIKE '" . $this->db->escape((string)$data['filter_store_name']) . "%'";
		}

		if (!empty($data['filter_company'])) {
			$sql .= " AND company LIKE '" . $this->db->escape((string)$data['filter_company']) . "%'";
		}

		if (!empty($data['filter_seller_group_id'])) {
			$sql .= " AND seller_group_id = '" . (int)$data['filter_seller_group_id'] . "'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND s.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(s.date_added) = DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalSellersBySellerGroupId($seller_group_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ms_seller WHERE seller_group_id = '" . (int)$seller_group_id . "'");

		return $query->row['total'];
	}

	public function addHistory($seller_id, $comment) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_history SET customer_id = '" . (int)$seller_id . "', comment = '" . $this->db->escape(strip_tags($comment)) . "', date_added = NOW()");
	}

	public function addTransaction($seller_id, $description = '', $amount = '', $order_product_id = 0, $order_id = 0, $product_id = 0) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller_transaction SET seller_id = '" . (int)$seller_id . "', order_product_id = '" . (int)$order_product_id . "', order_id = '" . (int)$order_id . "', product_id = '" . (int)$product_id . "', description = '" . $this->db->escape($description) . "', amount = '" . (float)$amount . "', date_added = NOW()");
	}

	public function deleteTransactionByOrderProductId($order_product_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ms_seller_transaction WHERE order_product_id = '" . (int)$order_product_id . "'");
	}

	public function getTransactions($seller_id, $start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ms_seller_transaction WHERE seller_id = '" . (int)$seller_id . "' ORDER BY date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}


	public function getTotalTransactions($seller_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total  FROM " . DB_PREFIX . "ms_seller_transaction WHERE seller_id = '" . (int)$seller_id . "'");

		return $query->row['total'];
	}

	public function getTransactionTotal($seller_id) {
		$query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "ms_seller_transaction WHERE seller_id = '" . (int)$seller_id . "'");

		return $query->row['total'];
	}

	public function deleteSeller($seller_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ms_seller WHERE seller_id = '" . (int)$seller_id . "'");
	}

	public function getSellerByProductId($product_id) {
		$query = $this->db->query("SELECT s.* FROM `" . DB_PREFIX . "ms_seller` s LEFT JOIN `" . DB_PREFIX . "ms_product_seller` ps ON ps.seller_id = s.seller_id WHERE ps.product_id = '" . (int)$product_id . "'");

		return $query->row;
	}

	public function approveSeller($seller_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "ms_seller` SET status = '1' WHERE seller_id = '" . (int)$seller_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_approval` WHERE customer_id = '" . (int)$seller_id . "' AND `type` = 'seller'");
	}
}
