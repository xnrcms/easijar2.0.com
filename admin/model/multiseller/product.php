<?php
class ModelMultisellerProduct extends Model {
	public function getSeller($product_id) {
		$query = $this->db->query("SELECT ps.*, s.store_name FROM " . DB_PREFIX . "ms_product_seller ps LEFT JOIN " . DB_PREFIX . "ms_seller s ON s.seller_id = ps.seller_id WHERE ps.product_id = '" . (int)$product_id . "'");

		return $query->row;
	}

	public function approve($product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "ms_product_seller SET approved = '1' WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("UPDATE " . DB_PREFIX . "product SET status = '1' WHERE product_id = '" . (int)$product_id . "'");
    }
}
