<?php
class ModelMultisellerOrder extends Model {
	public function getSuborderStatusId($order_id, $seller_id) {
		$query = $this->db->query("SELECT s.*, os.name AS order_status_name FROM `" . DB_PREFIX . "ms_suborder` s LEFT JOIN `" . DB_PREFIX . "order_status` os ON os.order_status_id = s.order_status_id WHERE s.order_id = '" . (int)$order_id . "' AND s.seller_id = '" . (int)$seller_id . "' AND os.language_id = '" . $this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getOrderSellers($order_id) {
		$query = $this->db->query("SELECT DISTINCT s.* FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "ms_order_product` mop ON mop.order_product_id = op.order_product_id LEFT JOIN `" . DB_PREFIX . "ms_seller` s ON s.seller_id = mop.seller_id WHERE op.order_id = '" . (int)$order_id . "'");

		return $query->rows;
    }

    public function getOrderProductIdsForMs($order_id, $seller_id)
    {
    	$query = $this->db->query("SELECT `order_product_id` FROM `" . DB_PREFIX . "ms_order_product` WHERE order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$seller_id . "'");

		return $query->rows;
    }

    public function addOrderHistoryByReturn($order_id, $seller_id = 0, $order_status_id, $comment = '')
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "ms_suborder` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$seller_id . "'");

        $this->db->query("INSERT INTO " . DB_PREFIX . "ms_suborder_history SET order_id = '" . (int)$order_id . "', seller_id = '" . (int)$seller_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '0', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
    }
}
