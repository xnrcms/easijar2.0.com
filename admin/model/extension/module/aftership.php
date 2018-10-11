<?php

/**
 * aftership.php
 *
 * @copyright 2018 OpenCart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-07-03 09:27
 * @modified 2018-07-03 09:27
 */
class ModelExtensionModuleAftership extends Model
{
    public function install()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "aftership(
              id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
              order_id INT UNSIGNED NOT NULL DEFAULT 0,
              seller_id INT UNSIGNED NOT NULL DEFAULT 0,
              tracking_code VARCHAR(64) NOT NULL DEFAULT '',
              tracking_number VARCHAR(64) NOT NULL DEFAULT '',
              tracking_name VARCHAR(64) NOT NULL DEFAULT '',
              comment VARCHAR(255) NOT NULL DEFAULT '',
              date_added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )ENGINE=MyISAM DEFAULT CHARSET UTF8;
        ");
    }

    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "aftership");
    }

    public function clearSettingTracking()
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = 0 AND `key` = 'tracking_data'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = 0 AND `key` = 'tracking_key'");
    }

    public function updateSettingTracking($data, $key)
    {
        $sql = "INSERT INTO " . DB_PREFIX . "setting SET store_id = 0, `code` = 'tracking', `key` = 'tracking_data', `value` = '" . json_encode($data) . "' , `serialized` = 1 ";
        $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = 0, `code` = 'tracking', `key` = 'tracking_key', `value` = '" . $key . "' , `serialized` = 0 ");
        return $this->db->query($sql);
    }

    public function getOrderShippingTracks($order_id, $start = 0, $limit = 10)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 10;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "aftership WHERE order_id = '" . (int)$order_id . "' ORDER BY date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

        return $query->rows;
    }

    public function getTotalOrderShippingTracks($order_id)
    {
        $row = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "aftership WHERE order_id = '" . (int)$order_id . "'")->row;

        return $row ? $row['total'] : 0;
    }

    public function addOrderShippingTrack($order_id, $data)
    {
        $this->load->model('sale/order');

        if (!$data['tracking_code']) {
            throw new Exception('Invalid tracking number');
        }

        $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "aftership WHERE tracking_number='" . trim($data['tracking_number']) . "'")->row;
        if (!empty($result)) {
            return false;
        }

        return $this->db->query("INSERT INTO " . DB_PREFIX . "aftership SET order_id = '" . (int)$order_id . "', tracking_code = '" . trim($data['tracking_code']) . "', tracking_number = '" . trim($data['tracking_number']) . "', tracking_name = '" . trim($data['tracking_name']) . "', comment = '" . trim($data['comment']) . "', date_added = NOW()");
    }

    public function delOrderShippingTrack($id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "aftership WHERE id = '" . (int)$id . "'");
    }
}