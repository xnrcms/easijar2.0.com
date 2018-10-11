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
 * @created 2018-09-18 16:26
 * @modified 2018-09-18 16:26
 */

namespace Seller;
class Aftership
{
    private static $instance = null;
    private $registry = null;
    private $seller = null;

    public function __construct()
    {
        $this->registry = \Registry::getSingleton();
        $this->seller = MsSeller::getInstance($this->registry);
    }
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __get($key)
    {
        // TODO: Implement __get() method.
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        // TODO: Implement __set() method.
        $this->registry->set($key, $value);
    }

    public function getOrderShippingTracks($order_id, $start = 0, $limit = 10)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 10;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "aftership WHERE seller_id=" . (int)$this->seller->sellerId() . " and  order_id = '" . (int)$order_id . "' ORDER BY date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

        return $query->rows;
    }

    public function getTotalOrderShippingTracks($order_id)
    {
        $row = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "aftership WHERE seller_id=" . (int)$this->seller->sellerId() . " and order_id = '" . (int)$order_id . "'")->row;

        return $row ? $row['total'] : 0;
    }

    public function addOrderShippingTrack($order_id, $data)
    {
        if (!$data['tracking_code']) {
            throw new Exception('Invalid tracking number');
        }

        $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "aftership WHERE tracking_number='" . trim($data['tracking_number']) . "'")->row;
        if (!empty($result)) {
            return false;
        }

        return $this->db->query("INSERT INTO " . DB_PREFIX . "aftership SET seller_id=" . (int)$this->seller->sellerId() . ", order_id = '" . (int)$order_id . "', tracking_code = '" . trim($data['tracking_code']) . "', tracking_number = '" . trim($data['tracking_number']) . "', tracking_name = '" . trim($data['tracking_name']) . "', comment = '" . trim($data['comment']) . "', date_added = NOW()");
    }

    public function delOrderShippingTrack($id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "aftership WHERE id = '" . (int)$id . "'");
    }

    public function getSellerShipping($id)
    {
        return $this->db->query("select * from " . DB_PREFIX . "aftership where seller_id=" . (int)$this->seller->sellerId() . " and id=" . (int)$id)->row;
    }
}