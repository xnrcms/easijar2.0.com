<?php
class ModelAccountCustomerFollowSeller extends Model {
	public function addSellerFollow($seller_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_follow_seller WHERE customer_id = '" . (int)$this->customer->getId() . "' AND seller_id = '" . (int)$seller_id . "'");

		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_follow_seller SET customer_id = '" . (int)$this->customer->getId() . "', seller_id = '" . (int)$seller_id . "', date_added = NOW()");
	}

	public function deleteSellerFollow($seller_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_follow_seller WHERE customer_id = '" . (int)$this->customer->getId() . "' AND seller_id = '" . (int)$seller_id . "'");
	}

	public function deleteSellerFollows($seller_ids) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_follow_seller WHERE customer_id = '" . (int)$this->customer->getId() . "' AND seller_id IN (" . $seller_ids . ") ");
	}

	public function getSellerFollow() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_follow_seller WHERE customer_id = '" . (int)$this->customer->getId() . "'");

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
