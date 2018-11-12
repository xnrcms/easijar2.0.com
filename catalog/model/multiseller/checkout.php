<?php
class ModelMultisellerCheckout extends Model {
	public function addOrderSeller($order_id) {
	    $this->load->model('checkout/order');
	    $this->load->model('multiseller/seller');
	    $order_product = $this->model_checkout_order->getOrderProducts($order_id);
        foreach ($order_product as $product) {
            $seller_info = $this->model_multiseller_seller->getSellerByProductId($product['product_id']);
            if ($seller_info) {
                $seller_group_info = $this->model_multiseller_seller->getSellerGroup($seller_info['seller_group_id']);
                $seller_amount = $product['total'] - ($product['total'] * $seller_group_info['fee_sale_percent'] / 100 + $seller_group_info['fee_sale_flat']);
                $this->db->query("INSERT INTO " . DB_PREFIX . "ms_order_product SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$product['order_product_id'] . "', seller_id = '" . (int)$seller_info['seller_id'] . "', store_commission_flat = '" . (float)$seller_group_info['fee_sale_flat'] . "', store_commission_percent = '" . (float)$seller_group_info['fee_sale_percent'] . "', seller_amount = '" . (float)$seller_amount . "'");
            }
        }

        $sellers = $this->getOrderSellers($order_id);
        foreach ($sellers as $seller) {
            $total = 0;
            $products = $this->getSubOrderProducts($order_id, $seller['seller_id']);
            foreach ($products as $product) {
                $total += $product['total'];
            }
            $order_totals = $this->getSubOrderTotals($order_id, $seller['seller_id']);
            foreach ($order_totals as $order_total) {
                if ($order_total['code'] == 'multiseller_coupon') {
                    $total -= $order_total['value'];
                }else{
                    $total += $order_total['value'];
                }
            }

            $order_sn   = date('YmdHis',time()) . '2' . random_string(10);

            $this->db->query("INSERT INTO " . DB_PREFIX . "ms_suborder SET order_id = '" . (int)$order_id . "', seller_id = '" . (int)$seller['seller_id'] . "', total = '" . (float)$total . "', order_status_id = '0',order_sn = '" . $order_sn . "'");
        }
    }

	public function getOrderSellers($order_id) {
		$query = $this->db->query("SELECT DISTINCT seller_id FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "ms_order_product` mop ON mop.order_product_id = op.order_product_id WHERE op.order_id = '" . (int)$order_id . "'");

		return $query->rows;
    }

	public function addSubOrderHistory($order_id, $seller_id, $order_status_id, $comment = '', $notify = false, $not_update_master = false) {
        $this->load->model('multiseller/order');
		$order_info = $this->model_multiseller_order->getSuborder($order_id, $seller_id);

		if ($order_info) {
            // If current order status is not complete but new status is complete then add the seller transaction balance
            if (!in_array($order_info['order_status_id'], $this->config->get('config_complete_status')) && in_array($order_status_id, $this->config->get('config_complete_status'))) {
                $this->load->model('multiseller/transaction');

                $this->model_multiseller_transaction->addOrderTransaction($seller_id, $order_id);
            }

            // Update the DB with the new statuses
            $this->db->query("UPDATE `" . DB_PREFIX . "ms_suborder` SET order_status_id = '" . (int)$order_status_id . "',date_modified = NOW() WHERE order_id = '" . (int)$order_id . "' AND seller_id = '" . $seller_id . "'");

            $this->db->query("INSERT INTO " . DB_PREFIX . "ms_suborder_history SET order_id = '" . (int)$order_id . "', seller_id = '" . (int)$seller_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");

            // Remove commission if sale is linked to affiliate referral.
            if (in_array($order_info['order_status_id'], $this->config->get('config_complete_status')) && !in_array($order_status_id, $this->config->get('config_complete_status'))) {
                $this->load->model('multiseller/transaction');

                $this->model_multiseller_transaction->deleteOrderTransactionByOrderId($seller_id, $order_id);
            }

            $suborders = $this->model_multiseller_order->getSuborders($order_id);
            $all_shipped = true;
            foreach ($suborders as $suborder) {
                if ($suborder['order_status_id'] != $this->config->get('config_shipped_status_id')) {
                    $all_shipped = false;
                }
            }

            if ($order_status_id == $this->config->get('config_shipped_status_id') && $all_shipped && !$not_update_master) {
                $this->load->model('checkout/order');
                // 最后一个参数代表是否不更新子订单，如为true表示不更新子订单，因更新子订单历史而更新主订单的时候不应再更新子订单，否则会导致死循环。
                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, 'All suborder shipped', $notify, false, true);
            }
        }

        $this->cache->delete('product');
	}

	public function getSubOrderProducts($order_id, $seller_id) {
		$query = $this->db->query("SELECT op.* FROM " . DB_PREFIX . "order_product op LEFT JOIN " . DB_PREFIX . "ms_order_product mop ON mop.order_product_id = op.order_product_id WHERE op.order_id = '" . (int)$order_id . "' AND mop.seller_id = '" . (int)$seller_id . "'");

		return $query->rows;
	}

    public function getSubOrderTotals($order_id, $seller_id) {
        $query = $this->db->query("SELECT ot.* FROM " . DB_PREFIX . "order_total ot
                                    LEFT JOIN " . DB_PREFIX . "ms_order_total mot ON mot.order_total_id = ot.order_total_id
                                    WHERE ot.order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$seller_id . "' ORDER BY sort_order");

        return $query->rows;
    }
}