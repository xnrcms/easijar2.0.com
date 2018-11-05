<?php
class ModelAccountProductBrowseRecords extends Model {
	public function addProductBrowseRecords($product_ids) {
		if (!empty($product_ids)) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_browse_records WHERE customer_id = '" . (int)$this->customer->getId() . "' AND product_id IN (" . implode(',', $product_ids) . ")");

			$sql 	= "INSERT INTO " . DB_PREFIX . "product_browse_records (customer_id,product_id,browse_date) VALUES ";
			foreach ($product_ids as $value) {
				$sql .= "('" . (int)$this->customer->getId() . "','" . (int)$value . "',NOW()),";
			}

			$sql 		= trim($sql,',');
			wr($sql);
			$this->db->query($sql);
		}
	}

	public function deleteSellerFollow($seller_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_follow_seller WHERE customer_id = '" . (int)$this->customer->getId() . "' AND seller_id = '" . (int)$seller_id . "'");
	}

	public function deleteSellerFollows($seller_ids) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_follow_seller WHERE customer_id = '" . (int)$this->customer->getId() . "' AND seller_id IN (" . $seller_ids . ") ");
	}

	public function getProductBrowseRecords() {
        $query = $this->db->query("SELECT pbr.product_id,pbr.browse_date,pd.name AS name, p.tax_class_id,p.price, p.image, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special FROM " . DB_PREFIX . "product_browse_records pbr LEFT JOIN " . DB_PREFIX . "product p ON (pbr.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = pbr.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() ORDER BY records_id DESC");

		return $query->rows;
	}

	public function getTotalWishlist() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_follow_seller WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		return $query->row['total'];
	}

	public function getSellerFollowBySellerId($seller_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_follow_seller WHERE customer_id = '" . (int)$this->customer->getId() . "' AND seller_id = '" . (int)$seller_id . "'");

		return $query->row['total'];
	}
}
