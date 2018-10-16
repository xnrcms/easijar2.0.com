<?php
class ModelMultisellerSeller extends Model {
	public function addSeller($seller_id, $data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller SET `seller_id` = '" . (int)$seller_id . "', `store_name` = '" . $this->db->escape((string)$data['store_name']) . "', `company` = '" . $this->db->escape((string)$data['company']) . "', `seller_group_id` = '" . (int)$this->config->get('module_multiseller_seller_group') . "', `alipay` = '" . $this->db->escape((string)$data['alipay']) . "', product_validation = '" . (int)!$this->config->get('module_multiseller_product_validation') . "', country_id = '" . (int)$data['country_id'] . "', zone_id = '" . (int)$data['zone_id'] . "', city_id = '" . (int)$data['city_id'] . "', county_id = '" . (int)$data['county_id'] . "', `status` = '" . (int)!$this->config->get('module_multiseller_seller_approval') . "', date_added = NOW(), date_modified = NOW()");

		if ($this->config->get('module_multiseller_seller_approval')) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_approval` SET customer_id = '" . (int)$seller_id . "', type = 'seller', date_added = NOW()");
		}
	}

	public function editSeller($seller_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "ms_seller SET store_name = '" . $this->db->escape((string)$data['store_name']) . "', company = '" . $this->db->escape((string)$data['company']) . "', description = '" . $this->db->escape((string)$data['description']) . "', country_id = '" . (int)$data['country_id'] . "', zone_id = '" . (int)$data['zone_id'] . "', city_id = '" . (int)$data['city_id'] . "', county_id = '" . (int)$data['county_id'] . "', avatar = '" . $this->db->escape((string)$data['avatar']) . "', banner = '" . $this->db->escape((string)$data['banner']) . "', alipay = '" . $this->db->escape((string)$data['alipay']) . "', date_modified = NOW() WHERE `seller_id` = '" . (int)$seller_id . "'");
	}

	public function getSeller($seller_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ms_seller` WHERE `seller_id` = '" . (int)$seller_id . "'");

		return $query->row;
	}

	public function getSellerByProductId($product_id) {
		$query = $this->db->query("SELECT s.* FROM `" . DB_PREFIX . "ms_seller` s LEFT JOIN `" . DB_PREFIX . "ms_product_seller` ps ON ps.seller_id = s.seller_id WHERE ps.product_id = '" . (int)$product_id . "'");

		return $query->row;
	}

	public function getSellerGroup($seller_group_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ms_seller_group` WHERE seller_group_id = '" . (int)$seller_group_id . "'");

		return $query->row;
	}

	public function getSellerProducts($seller_id, $data = array()) {
		$sql = "SELECT DISTINCT ps.product_id, 
                    (
                        SELECT AVG(rating) 
                        FROM " . DB_PREFIX . "order_product_review r1 
                        LEFT JOIN " . DB_PREFIX . "order_product op ON (r1.order_product_id = op.order_product_id) 
                        WHERE op.product_id = ps.product_id AND r1.status = '1' GROUP BY op.product_id
                    ) AS rating, 
                    (
                        SELECT price 
                        FROM " . DB_PREFIX . "product_discount pd2 
                        WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) 
                        ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1
                    ) AS discount, 
                    (
                        SELECT price FROM " . DB_PREFIX . "product_special ps1 
                        WHERE ps.product_id = ps1.product_id AND ps1.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps1.date_start = '0000-00-00' OR ps1.date_start < NOW()) AND (ps1.date_end = '0000-00-00' OR ps1.date_end > NOW())) 
                        ORDER BY ps1.priority ASC, ps1.price ASC LIMIT 1
                    ) AS special
                FROM " . DB_PREFIX . "ms_product_seller ps
                LEFT JOIN " . DB_PREFIX . "product p ON (p.product_id = ps.product_id) 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = ps.product_id) 
                LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (ps.product_id = p2s.product_id) 
                WHERE ps.seller_id = '" . $seller_id . "' AND (ps.date_until <= CURDATE() OR ps.date_until = '0000-00-00') AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND status = '1' AND p.date_available <= CURDATE()". (isset($data['parent_id']) ? " AND p.parent_id = '" .$data['parent_id']. "'" : "")." AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
                GROUP BY .ps.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'p.price',
			'rating',
			'p.sort_order',
            'p.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} elseif ($data['sort'] == 'p.price') {
				$sql .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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

		$product_data = array();

		$query = $this->db->query($sql);

		$this->load->model('catalog/product');
		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
		}

		return $product_data;
	}

	public function getTotalSellerProducts($seller_id) {
		$sql = "SELECT COUNT(ps.product_id) AS total FROM " . DB_PREFIX . "ms_product_seller ps
                LEFT JOIN " . DB_PREFIX . "product p ON (p.product_id = ps.product_id) 
                LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (ps.product_id = p2s.product_id) 
                WHERE ps.seller_id = '" . $seller_id . "' AND (ps.date_until >= NOW() OR ps.date_until = '0000-00-00') AND status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalOrdersBySellerId($seller_id) {
	    $status = array_merge($this->config->get('config_complete_status'), $this->config->get('config_processing_status'));
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "ms_suborder`
		                            WHERE seller_id = '" . (int)$seller_id . "' AND order_status_id IN (" . implode(', ', $status) . ")");

		return $query->row['total'];
	}

	public function getOrdersTotalBySellerId($seller_id) {
	    $status = array_merge($this->config->get('config_complete_status'), $this->config->get('config_processing_status'));
		$query = $this->db->query("SELECT SUM(op.total) AS total FROM `" . DB_PREFIX . "ms_order_product` mop
		                            LEFT JOIN `" . DB_PREFIX . "order_product` op ON op.order_product_id = mop.order_product_id
		                            LEFT JOIN `" . DB_PREFIX . "ms_suborder` o ON o.order_id = mop.order_id AND o.seller_id = mop.seller_id
		                            WHERE o.seller_id = '" . (int)$seller_id . "' AND o.order_status_id IN (" . implode(', ', $status) . ")");

		return $query->row['total'];
	}

	public function getTotalOrdersByMonth($seller_id) {
		$implode = array();

		foreach ($this->config->get('config_complete_status') as $order_status_id) {
			$implode[] = "'" . (int)$order_status_id . "'";
		}

		$order_data = array();

		for ($i = 1; $i <= date('t'); $i++) {
			$date = date('Y') . '-' . date('m') . '-' . $i;

			$order_data[date('j', strtotime($date))] = array(
				'day'   => date('d', strtotime($date)),
				'amount' => 0,
				'count' => 0
			);
		}

		$query = $this->db->query("SELECT COUNT(*) AS total, date_added 
                                    FROM `" . DB_PREFIX . "ms_suborder` so
                                    LEFT JOIN `" . DB_PREFIX . "order` o ON o.order_id = so.order_id
                                    WHERE so.seller_id = '" . $seller_id . "' AND so.order_status_id IN (" . implode(",", $implode) . ") AND DATE(o.date_added) >= '" . $this->db->escape(date('Y') . '-' . date('m') . '-1') . "' GROUP BY DATE(o.date_added)");

		foreach ($query->rows as $result) {
			$order_data[date('j', strtotime($result['date_added']))]['count'] = $result['total'];
		}

		$query = $this->db->query("SELECT SUM(op.seller_amount) AS amount, date_added 
                                    FROM `" . DB_PREFIX . "ms_order_product` op
                                    LEFT JOIN `" . DB_PREFIX . "ms_suborder` so ON so.order_id = op.order_id
                                    LEFT JOIN `" . DB_PREFIX . "order` o ON o.order_id = op.order_id
                                    WHERE so.seller_id = '" . $seller_id . "' AND so.order_status_id IN (" . implode(",", $implode) . ") AND DATE(o.date_added) >= '" . $this->db->escape(date('Y') . '-' . date('m') . '-1') . "' GROUP BY DATE(o.date_added)");

		foreach ($query->rows as $result) {
			$order_data[date('j', strtotime($result['date_added']))]['amount'] = $result['amount'];
		}

		return $order_data;
	}

	public function getSellerByProductIdForOne($product_id) {
		$query = $this->db->query("SELECT s.*,
		(
            SELECT AVG(rating) 
            FROM " . DB_PREFIX . "order_product_review r1 
            LEFT JOIN " . DB_PREFIX . "order_product op ON (r1.order_product_id = op.order_product_id) 
            WHERE op.product_id = ps.product_id AND r1.status = '1' GROUP BY op.product_id
        ) AS rating,
        (
            SELECT COUNT(product_id) 
            FROM " . DB_PREFIX . "ms_product_seller AS mps WHERE mps.seller_id =s.seller_id
        ) AS total FROM `" . DB_PREFIX . "ms_seller` s LEFT JOIN `" . DB_PREFIX . "ms_product_seller` ps ON ps.seller_id = s.seller_id WHERE ps.product_id = '" . (int)$product_id . "'");

		return $query->row;
	}
}