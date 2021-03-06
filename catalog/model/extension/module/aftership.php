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

    public function getOrderLogisticsTotals()
    {
        $logistics_query = $this->db->query("SELECT COUNT(*) AS total FROM " . get_tabname('aftership') . " af LEFT JOIN " . get_tabname('ms_suborder') . " mssu ON (mssu.seller_id = af.seller_id AND mssu.order_id = af.order_id) LEFT JOIN " . get_tabname('ms_order_product') . " msop ON (msop.seller_id = af.seller_id AND msop.order_id = af.order_id) LEFT JOIN " . get_tabname('order') . " o ON o.order_id = af.order_id LEFT JOIN " . get_tabname('order_product') . " op ON op.order_product_id = msop.order_product_id WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND op.name IS NOT NULL");

        return $logistics_query->row['total'];
    }

    public function getOrderLogisticsTotalsForRead()
    {
        $logistics_query = $this->db->query("SELECT COUNT(*) AS total FROM " . get_tabname('aftership') . " af LEFT JOIN " . get_tabname('ms_suborder') . " mssu ON (mssu.seller_id = af.seller_id AND mssu.order_id = af.order_id) LEFT JOIN " . get_tabname('ms_order_product') . " msop ON (msop.seller_id = af.seller_id AND msop.order_id = af.order_id) LEFT JOIN " . get_tabname('order') . " o ON o.order_id = af.order_id LEFT JOIN " . get_tabname('order_product') . " op ON op.order_product_id = msop.order_product_id WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND is_read = '0' AND op.name IS NOT NULL");

        return $logistics_query->row['total'];
    }

    public function updateOrderLogisticsReadStatus($aftership_ids)
    {
        $aftership_ids  = is_array($aftership_ids) ? $aftership_ids : [$aftership_ids];
        $this->db->query("UPDATE " . get_tabname('aftership') . " SET is_read = '1' WHERE id IN ('" . implode("','",$aftership_ids) . "')");
    }

    public function getOrderLogistics($data = [])
    {
        $field  = 'af.tracking_name,af.tracking_code,af.order_id,af.seller_id,af.tracking_number,af.tracking_name,af.date_added,op.name,op.image,mssu.order_sn';
        $sql    = "SELECT " . $field . " FROM " . get_tabname('aftership') . " af LEFT JOIN " . get_tabname('ms_suborder') . " mssu ON (mssu.seller_id = af.seller_id AND mssu.order_id = af.order_id) LEFT JOIN " . get_tabname('ms_order_product') . " msop ON (msop.seller_id = af.seller_id AND msop.order_id = af.order_id) LEFT JOIN " . get_tabname('order') . " o ON o.order_id = af.order_id LEFT JOIN " . get_tabname('order_product') . " op ON op.order_product_id = msop.order_product_id WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND op.name IS NOT NULL";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " ORDER BY af.date_added DESC LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $logistics_query = $this->db->query($sql);

        return $logistics_query->rows;
    }
}