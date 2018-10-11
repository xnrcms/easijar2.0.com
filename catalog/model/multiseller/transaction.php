<?php
class ModelMultisellerTransaction extends Model {
	public function getTransactions($data = array()) {
		$sql = "SELECT t.*, c.fullname AS name, s.store_name AS store_name FROM " . DB_PREFIX . "ms_seller_transaction t 
		        LEFT JOIN " . DB_PREFIX . "ms_seller s ON (s.seller_id = t.seller_id) 
		        LEFT JOIN " . DB_PREFIX . "customer c ON (t.seller_id = c.customer_id) 
		        WHERE t.seller_id = '" . $this->customer->getId() . "'";

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
		        WHERE t.seller_id = '" . $this->customer->getId() . "'";

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

	public function getTransactionTotal() {
		$sql = "SELECT SUM(amount) AS total FROM " . DB_PREFIX . "ms_seller_transaction
		        WHERE seller_id = '" . $this->customer->getId() . "'";

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getSubOrderTotals($order_id, $seller_id) {
		$query = $this->db->query("SELECT ot.* FROM `" . DB_PREFIX . "order_total` ot
		                            LEFT JOIN `" . DB_PREFIX . "ms_order_total` mot ON mot.order_total_id = ot.order_total_id
		                            WHERE order_id = '" . (int)$order_id . "' AND mot.seller_id = '" . (int)$seller_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

    /**
     * @param $seller_id
     * @param $description
     * @param string $amount
     * @param int $order_id  商品销售费用时不为空
     * @param int $withdraw_id  资金提现时不为空（目前没有提现功能）
     */
	public function addOrderTransaction($seller_id, $order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ms_order_product WHERE order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$seller_id . "'");

		$this->load->language('seller/common', 'seller');
		foreach ($query->rows as $order_product) {
		    $transaction_query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ms_seller_transaction WHERE seller_id = '" . (int)$seller_id . "' AND order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product['order_product_id'] . "'");
		    if ($transaction_query->row['total']) {
		        continue;
            }

		    $description = sprintf($this->language->get('seller')->get('text_transaction_desc'), $order_id);
		    $this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller_transaction SET seller_id = '" . (int)$seller_id . "', order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product['order_product_id'] . "', description = '" . $this->db->escape($description) . "', amount = '" . (float)$order_product['seller_amount'] . "', date_added = NOW()");
        }

        $suborder_total = $this->getSubOrderTotals($order_id, $seller_id);

		foreach ($suborder_total as $total) {
		    if ((float)$total['value'] <= 0) {
		        continue;
            }
		    $description = sprintf($this->language->get('seller')->get('text_order'), $order_id) . $total['title'];
		    $this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller_transaction SET seller_id = '" . (int)$seller_id . "', order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($description) . "', amount = '" . (float)$total['value'] . "', date_added = NOW()");
        }
	}

	public function deleteOrderTransactionByOrderId($seller_id, $order_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ms_seller_transaction WHERE seller_id = '" . (int)$seller_id . "' AND order_id = '" . (int)$order_id . "'");
	}
}
