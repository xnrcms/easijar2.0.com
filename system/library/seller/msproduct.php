<?php
/**
 * msproduct.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-06-06 10:28
 * @modified 2018-06-06 10:28
 */

namespace Seller;
use Models\Product;

require_once DIR_SYSTEM . 'models/product.php';

class MsProduct
{
    private $registry = null;
    private static $instance = null;
    private $seller_id = null;

    private function __construct($registry = null)
    {
        $this->registry = $registry;
        $this->seller_id = MsSeller::getInstance($registry)->sellerId();
    }

    private function __clone()
    {

    }

    public static function getInstance($registry = '')
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($registry);
        }

        return self::$instance;
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    public function getTotalProducts($filter)
    {
        $sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)";

        $sql .= "LEFT JOIN " . DB_PREFIX . "ms_product_seller mp on mp.product_id=p.product_id";

        $sql .= " WHERE mp.seller_id=" . (int)$this->seller_id . " and pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($filter['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape((string)$filter['filter_name']) . "%'";
        }

        if (!empty($filter['filter_model'])) {
            $sql .= " AND p.model LIKE '%" . $this->db->escape((string)$filter['filter_model']) . "%'";
        }

        if (isset($filter['filter_status']) && $filter['filter_status'] !== '') {
            $sql .= " AND p.status = '" . (int)$filter['filter_status'] . "'";
        }
        
        if (!empty($filter['filter_stock_quantity'])) {
            $stockQuantity = explode('-', $filter['filter_stock_quantity']);
            if (count($stockQuantity) == 2) {
                $sql .= " AND p.quantity >= " . (int)min($stockQuantity) . " AND p.quantity <= " . (int)max($stockQuantity);
            }
        }

        if (!isset($filter['filter_show_variant']) || !array_get($filter, 'filter_show_variant')) {
            $sql .= " AND p.parent_id = 0 ";
        }

        $query = $this->db->query($sql);
        wr($sql);
        return $query->row['total'];
    }

    public function getProducts($data)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "ms_product_seller mp ON mp.product_id=p.product_id";
        $sql .= " WHERE mp.seller_id=" . (int)$this->seller_id . " AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '%" . $this->db->escape((string)$data['filter_model']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!isset($data['filter_show_variant']) || !array_get($data, 'filter_show_variant')) {
            $sql .= " AND p.parent_id = 0 ";
        }

        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'p.product_id',
            'pd.name',
            'p.model',
            'p.price',
            'p.quantity',
            'p.status',
            'p.sort_order',
            'p.date_modified'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'pd.name') {
                $sql .= " order by convert(" . $data['sort'] . " using gbk)";
            } else {
                $sql .= " ORDER BY " . $data['sort'];
            }
        } else {
            $sql .= " order by convert(pd.name using gbk)";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getCategories($data = array()) {
        $sql = "SELECT c1.status, cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, c1.parent_id, c1.sort_order FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) 
                WHERE 1=1";
        if ($this->config->get('module_multiseller_seller_categories')) {
            $sql .= " AND cp.category_id IN (" . implode(',', $this->config->get('module_multiseller_seller_categories')) . ")";
        }
        $sql .= " AND cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND cd2.name LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] != '') {
            $sql .= " AND c1.status = '" . (int)$data['filter_status'] . "'";
        }

        $sql .= " GROUP BY cp.category_id";

        $sort_data = array(
            'name',
            'sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sort_order";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getAttributes($data = array()) {
        $sql = "SELECT *, (SELECT agd.name FROM " . DB_PREFIX . "attribute_group_description agd WHERE agd.attribute_group_id = a.attribute_group_id AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS attribute_group FROM " . DB_PREFIX . "attribute a LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND ad.name LIKE '" . $this->db->escape((string)$data['filter_name']) . "%'";
        }

        if (!empty($data['filter_attribute_group_id'])) {
            $sql .= " AND a.attribute_group_id = '" . (int)$data['filter_attribute_group_id'] . "'";
        }

        $sort_data = array(
            'ad.name',
            'attribute_group',
            'a.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY attribute_group, ad.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function saveUpload($data)
    {
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();

        $this->db->query("insert into " . DB_PREFIX . "download set filename='" . $this->db->escape($data['filename']) . "', mask='" . $this->db->escape($data['mask']) . "', date_added=NOW()");
        $download_id = $this->db->getLastId();

        foreach ($languages as $language) {
            $this->db->query("insert into " . DB_PREFIX . "download_description set download_id=" . (int)$download_id . ", language_id=" . (int)$language['language_id'] . ", name='" . $this->db->escape($data['download_name']) . "'");
        }

        return $download_id;
    }

    public function getCategory($category_id) {
        $query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id AND cp.category_id != cp.path_id) WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    public function getAttribute($attribute_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "attribute a LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE a.attribute_id = '" . (int)$attribute_id . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    public function getDownload($download_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "download d LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE d.download_id = '" . (int)$download_id . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    public function getTaxClasses($data = array()) {
        if ($data) {
            $sql = "SELECT * FROM " . DB_PREFIX . "tax_class";

            $sql .= " ORDER BY title";

            if (isset($data['order']) && ($data['order'] == 'DESC')) {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            if (isset($data['start']) || isset($data['limit'])) {
                if ($data['start'] < 0) {
                    $data['start'] = 0;
                }

                if ($data['limit'] < 1) {
                    $data['limit'] = 20;
                }

                $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
            }

            $query = $this->db->query($sql);

            return $query->rows;
        } else {
            $tax_class_data = $this->cache->get('tax_class');

            if (!$tax_class_data) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "tax_class");

                $tax_class_data = $query->rows;

                $this->cache->set('tax_class', $tax_class_data);
            }

            return $tax_class_data;
        }
    }

    public function getProductDescriptions($product_id) {
        $product_description_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

        foreach ($query->rows as $result) {
            $product_description_data[$result['language_id']] = array(
                'name'             => $result['name'],
                'description'      => $result['description'],
                'meta_title'       => $result['meta_title'],
                'meta_description' => $result['meta_description'],
                'meta_keyword'     => $result['meta_keyword'],
                'tag'              => $result['tag']
            );
        }

        return $product_description_data;
    }

    public function getProductCategories($product_id) {
        $product_category_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

        foreach ($query->rows as $result) {
            $product_category_data[] = $result['category_id'];
        }

        return $product_category_data;
    }

    public function getProductAttributes($product_id) {
        $product_attribute_data = array();

        $product_attribute_query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' GROUP BY attribute_id");

        foreach ($product_attribute_query->rows as $product_attribute) {
            $product_attribute_description_data = array();

            $product_attribute_description_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

            foreach ($product_attribute_description_query->rows as $product_attribute_description) {
                $product_attribute_description_data[$product_attribute_description['language_id']] = array('text' => $product_attribute_description['text']);
            }

            $product_attribute_data[] = array(
                'attribute_id'                  => $product_attribute['attribute_id'],
                'product_attribute_description' => $product_attribute_description_data
            );
        }

        return $product_attribute_data;
    }

    public function getProductDiscounts($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' ORDER BY quantity, priority, price");

        return $query->rows;
    }

    public function getStockStatuses($data = array()) {
        if ($data) {
            $sql = "SELECT * FROM " . DB_PREFIX . "stock_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'";

            $sql .= " ORDER BY name";

            if (isset($data['order']) && ($data['order'] == 'DESC')) {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            if (isset($data['start']) || isset($data['limit'])) {
                if ($data['start'] < 0) {
                    $data['start'] = 0;
                }

                if ($data['limit'] < 1) {
                    $data['limit'] = 20;
                }

                $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
            }

            $query = $this->db->query($sql);

            return $query->rows;
        } else {
            $stock_status_data = $this->cache->get('stock_status.' . (int)$this->config->get('config_language_id'));

            if (!$stock_status_data) {
                $query = $this->db->query("SELECT stock_status_id, name FROM " . DB_PREFIX . "stock_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name");

                $stock_status_data = $query->rows;

                $this->cache->set('stock_status.' . (int)$this->config->get('config_language_id'), $stock_status_data);
            }

            return $stock_status_data;
        }
    }
    
    public function deleteProduct($product_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_recurring WHERE product_id = " . (int)$product_id);
        $this->db->query("DELETE FROM " . DB_PREFIX . "review WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product WHERE product_id = '" . (int)$product_id . "'");

        $this->cache->delete('product');
    }

    public function getSellerProduct($product_id)
    {
        return $this->db->query("select * from " . DB_PREFIX . "ms_product_seller where product_id=" . (int)$product_id . " and seller_id=" . (int)$this->seller_id)->row;
    }

    public function getProductRelated($product_id) {
        $product_related_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");

        foreach ($query->rows as $result) {
            $product_related_data[] = $result['related_id'];
        }

        return $product_related_data;
    }

    public function getProductSpecials($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "' ORDER BY priority, price");

        return $query->rows;
    }

    public function getProduct($product_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p left join " . DB_PREFIX . "ms_product_seller mps on mps.product_id=p.product_id LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    public function getProductDownloads($product_id) {
        $product_download_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");

        foreach ($query->rows as $result) {
            $product_download_data[] = $result['download_id'];
        }

        return $product_download_data;
    }

    public function editProduct($product_id, $data)
    {
        $seller_product = $this->getSellerProduct($product_id);

        if (!$seller_product) {
            return false;
        }

        $approved = isset($seller_product['approved']) ? $seller_product['approved'] : 0;

        // Product status

        if (!$this->config->get('module_multiseller_product_validation')) {
            $status = isset($data['status']) ? $data['status'] : 0;
        } elseif ($this->config->get('module_multiseller_product_validation') && !$approved) {
            $status = 0;
        } else {
            $status = 0;
        }

        $shipping_require = $this->config->get('module_multiseller_seller_shipping');
        $shipping = 0;
        if (!$shipping_require || $shipping_require == 1) {
            if (!$shipping_require) {
                $shipping = 0;
            }
            if ($shipping_require) {
                $shipping = 1;
            }
        } else {
            $shipping = $data['shipping'];
        }

        // Main data
        $this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape((string)$data['model']) . "', sku = '" . $this->db->escape((string)$data['sku']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape((string)$data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$shipping . "', price = '" . (float)$data['price'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$status . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', date_added = NOW(), date_modified = NOW() where product_id=" . (int)$product_id);

        // Seller product
        $this->db->query("UPDATE " . DB_PREFIX . "ms_product_seller set seller_id=" . (int)$this->seller_id . ", date_until=DATE('" . $this->db->escape($data['date_until']) . "'), approved=" . (int)$approved . " where product_id=" . (int)$product_id);

        // Image
        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape((string)$data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
        }

        // Description
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        // Attribute
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    // Removes duplicates
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        // Discount
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_discount'])) {
            foreach ($data['product_discount'] as $product_discount) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
            }
        }

        // Special
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_special'])) {
            foreach ($data['product_special'] as $product_special) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
            }
        }

        // Images
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_image'])) {
            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
            }
        }

        // Downloads
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_download'])) {
            foreach ($data['product_download'] as $download_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
            }
        }

        // Categories
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
            }
        }

        // Related
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");
        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
            }
        }

        if (array_get($data, 'save_variant')) {
            Product::find((int)$product_id)->saveVariantDescriptions($data['product_description']);
        }

        $this->cache->delete('product');
        return $product_id;
    }

    public function addProduct($data)
    {
        // Main data
        // Product status
        if (!$this->config->get('module_multiseller_product_validation')) {
            $status = isset($data['status']) ? $data['status'] : 0;
            $approved = 1;
        } else {
            $status = 0;
            $approved = 0;
        }

        $shipping_require = $this->config->get('module_multiseller_seller_shipping');
        $shipping = 0;
        if (!$shipping_require || $shipping_require == 1) {
            if (!$shipping_require) {
                $shipping = 0;
            }
            if ($shipping_require) {
                $shipping = 1;
            }
        } else {
            $shipping = $data['shipping'];
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape((string)$data['model']) . "', sku = '" . $this->db->escape((string)$data['sku']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape((string)$data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$shipping . "', price = '" . (float)$data['price'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$status . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', date_added = NOW(), date_modified = NOW()");

        $product_id = $this->db->getLastId();

        // Seller product
        $this->db->query("INSERT INTO " . DB_PREFIX . "ms_product_seller set product_id=" . (int)$product_id . ", seller_id=" . (int)$this->seller_id . ", date_until='" . $this->db->escape((string)$data['date_until']) . "', approved=" . (int)$approved);

        // Image
        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape((string)$data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
        }

        // Description
        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        // Store
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id=0");

        // Attribute
        if (isset($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    // Removes duplicates
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        // Discount
        if (isset($data['product_discount'])) {
            foreach ($data['product_discount'] as $product_discount) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
            }
        }

        // Special
        if (isset($data['product_special'])) {
            foreach ($data['product_special'] as $product_special) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
            }
        }

        // Images
        if (isset($data['product_image'])) {
            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
            }
        }

        // Downloads
        if (isset($data['product_download'])) {
            foreach ($data['product_download'] as $download_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
            }
        }

        // Categories
        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
            }
        }

        // Related
        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
            }
        }

        $this->cache->delete('product');
        return $product_id;
    }

    public function batchUpdateProductPrice($data)
    {
        foreach ($data as $item) {
            $sql = "UPDATE " .DB_PREFIX. "product SET price = '". (float)$item['price']  ."' WHERE product_id = " . $item['product_id'];
            $this->db->query($sql);
        }
    }

    public function getBatchProductTotal(array $ids)
    {
        $implode = "";
        foreach ($ids as $id) {
            $implode .= sprintf("product_id = %d OR ", $id);
        }
        $sql = "SELECT * FROM " . DB_PREFIX . "product WHERE (" . substr($implode, 0, strlen($implode) - 3) . ")";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function batchUpdateProductQuantity($data)
    {
        foreach ($data as $item) {
            $sql = "UPDATE " . DB_PREFIX . "product SET quantity = '" . (int)$item['quantity'] . "' WHERE product_id = " . $item['product_id'];
            $this->db->query($sql);
        }
    }

    public function batchUpdateProductStatus($ids)
    {
        $data = "";
        foreach ($ids as $id) {
            $data .= sprintf("%d,", $id);
        }

        $sql = "UPDATE " . DB_PREFIX . "product SET status = 0 WHERE product_id IN (" . substr($data, 0, strlen($data) - 1) . ")";
        $this->db->query($sql);
    }

    public function getSellerProductApproved($product_id)
    {
        $row = $this->db->query("select approved from " . DB_PREFIX . "ms_product_seller where product_id=".(int)$product_id)->row;
        return $row ? $row['approved'] : 0;
    }
}