<?php
/**
 * after_ship.php
 *
 * @copyright 2018 OpenCart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-07-03 17:26
 * @modified 2018-07-03 17:26
 */

class ModelExtensionModuleAftership extends Model
{
    public function getOrderShippingTrack($order_id)
    {
        $order_track_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "aftership` WHERE order_id = '" . (int)$order_id . "'");

        return $order_track_query->rows;
    }

    public function getTrackingNameByCode($code)
    {
        $kd_tracking_data = $this->config->get('module_aftership_data');

        foreach ($kd_tracking_data as $item) {
            if ($item['code'] == $code) {
                return $item['name'];
            }
        }
        return $code;
    }

    public function getShippingSellerName($seller_id)
    {
        if (!$seller_id) {
            return '';
        }
        $row = $this->db->query("select store_name from " . DB_PREFIX . "ms_seller where seller_id=" . (int)$seller_id)->row;
        return array_get($row, 'store_name', '');
    }

    public function getOrderShippingTrackForMs($order_id = 0,$seller_id = 0)
    {
        $order_track_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "aftership` WHERE order_id = '" . (int)$order_id . "' AND seller_id = '" . $seller_id . "'");

        return $order_track_query->rows;
    }
}