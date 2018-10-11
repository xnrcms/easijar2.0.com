<?php

/**
 * flash_sale.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author StifferChen <chenlin@opencart.cn>
 * @created 2018-01-05 20:18
 * @modified 2018-01-05 20:18
 */
class ModelExtensionModuleFlashSale extends Model
{
    public function getSellProductCount($product_id, $start, $end)
    {
        $row = $this->db->query("select sum(`count`) `count` from " . DB_PREFIX . "flash_sale_product where product_id=" . (int)$product_id . " and date_added > '" . $this->db->escape($start) . "' and date_added < '" . $this->db->escape($end) . "'")->row;
        return $row && $row['count'] ? $row['count'] : 0;

    }

    public function getSaleCounts($products_id, $date_start, $date_end)
    {
        if (!$date_start || !$date_end) {
            return array();
        }
        $id_str = $this->convertIdToString($products_id);
        if (!$id_str) {
            return array();
        }
        $results = $this->db->query("select sum(`count`) `count`, product_id from " . DB_PREFIX . "flash_sale_product where product_id in (" . $id_str . ") and date_added > '" . $this->db->escape($date_start) . "' and date_added < '" . $this->db->escape($date_end) . "' group by product_id")->rows;
        $counts = array();
        if ($results) {
            foreach ($results as $result) {
                $counts[$result['product_id']] = $result['count'];
            }
        }
        return $counts;
    }

    public function getProductsInfo($products_id)
    {
        $id_str = $this->convertIdToString($products_id);
        if (!$id_str) {
            return array();
        }
        $sql = "SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "order_product_review r1 LEFT JOIN " . DB_PREFIX . "order_product op ON (r1.order_product_id = op.order_product_id) WHERE op.product_id = p.product_id AND r1.status = '1' GROUP BY op.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id in (" . $id_str . ") AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $results = $this->db->query($sql)->rows;
        if (!$results) {
            return array();
        }
        $products_info = array();
        foreach ($results as $result) {
            $product_id = $result['product_id'];
            $result['flash'] = array_get(Flash::getSingleton()->getFlashPriceAndCount($product_id), 'price', false);
            $products_info[$product_id] = $result;
        }

        return $products_info;
    }

    private function convertIdToString($products_id)
    {
        if (!$products_id || !is_array($products_id)) {
            return '';
        }
        return implode(',', $products_id);
    }
}