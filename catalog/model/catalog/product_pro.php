<?php
/**
 * multi_product.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-04-09 15:48
 * @modified   2018-04-09 15:48
 */

class ModelCatalogProductPro extends ModelCatalogProduct
{
    private $useCache = false;
    private $useIndex = true;
    private $filterCache = null;
    private $products = array();
    private $defaultGroupId;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $config = $this->config;
        $this->useIndex = $config->get('module_multi_filter_status');
        $this->useCache = $config->get('module_multi_filter_cache_status');
        $cacheExpired = (int)$config->get('module_multi_filter_cache_expired') * 3600;
        if ($cacheExpired) {
            $this->filterCache = new \Cache($config->get('cache_engine'), $cacheExpired);
        }
        $this->load->model('setting/setting');
        $this->defaultGroupId = $this->model_setting_setting->getSettingValue('config_customer_group_id');
    }

    private function setCacheData($cacheKey, $data)
    {
        if (!$this->useCache || !$this->filterCache) {
            return;
        }
        $this->filterCache->set($cacheKey, $data);
    }

    private function getCacheData($cacheKey)
    {
        if (!$this->useCache || !$this->filterCache) {
            return null;
        }
        return $result = $this->filterCache->get($cacheKey);
    }

    private function getProductSql($productIds)
    {
        if (empty($productIds)) {
            return '';
        }
        if (is_array($productIds)) {
            $productIds = "(" . implode(',', $productIds) . ")";
            $productCondition = "p.product_id in " . $productIds;
        } else {
            $productCondition = "p.product_id = '" . (int)$productIds . "'";
        }
        $sql = "SELECT DISTINCT *, pd.name AS name, p.*, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00 00:00:00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00 00:00:00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->defaultGroupId . "' AND ((ps.date_start = '0000-00-00 00:00:00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00 00:00:00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS default_special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "order_product_review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_product_review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE " . $productCondition . " AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

        return $sql;
    }

    private function handleProduct($productRow)
    {
        $special = $productRow['special'];
        $defaultSpecial = $productRow['default_special'];
        if ($special && $defaultSpecial && $special > $defaultSpecial) {
            $special = $defaultSpecial;
        }
        $product_data = array(
            'product_id' => $productRow['product_id'],
            'name' => $productRow['name'],
            'description' => $productRow['description'],
            'meta_title' => $productRow['meta_title'],
            'meta_description' => $productRow['meta_description'],
            'meta_keyword' => $productRow['meta_keyword'],
            'tag' => $productRow['tag'],
            'model' => $productRow['model'],
            'sku' => $productRow['sku'],
            'upc' => $productRow['upc'],
            'ean' => $productRow['ean'],
            'jan' => $productRow['jan'],
            'isbn' => $productRow['isbn'],
            'mpn' => $productRow['mpn'],
            'location' => $productRow['location'],
            'quantity' => $productRow['quantity'],
            'stock_status' => $productRow['stock_status'],
            'image' => $productRow['image'],
            'manufacturer_id' => $productRow['manufacturer_id'],
            'manufacturer' => $productRow['manufacturer'],
            'price' => ($productRow['discount'] ? $productRow['discount'] : $productRow['price']),
            'special' => $special,
            'reward' => $productRow['reward'],
            'points' => $productRow['points'],
            'tax_class_id' => $productRow['tax_class_id'],
            'date_available' => $productRow['date_available'],
            'weight' => $productRow['weight'],
            'weight_class_id' => $productRow['weight_class_id'],
            'length' => $productRow['length'],
            'width' => $productRow['width'],
            'height' => $productRow['height'],
            'length_class_id' => $productRow['length_class_id'],
            'subtract' => $productRow['subtract'],
            'rating' => round($productRow['rating']),
            'reviews' => $productRow['reviews'] ? $productRow['reviews'] : 0,
            'minimum' => $productRow['minimum'],
            'sales' => $productRow['sales'],
            'sort_order' => $productRow['sort_order'],
            'status' => $productRow['status'],
            'date_added' => $productRow['date_added'],
            'date_modified' => $productRow['date_modified'],
            'viewed' => $productRow['viewed'],
            'twig' => $productRow['twig'],
        );

        return $product_data;
    }

    private function buildFilterJoinSql($sql, $data)
    {
        $languageId = (int)$this->config->get('config_language_id');
        if (!empty($data['filter_name'])) {
            //$sql .= ", pa.text";
        }

        if (!empty($data['filter_category_id'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
            } else {
                $sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
            }

            if (!empty($data['filter_filter'])) {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
            } else {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
            }
        } else {
            $sql .= " FROM " . DB_PREFIX . "product p";
        }

        if (!empty($data['filter_name']) || !empty($data['filter_attributes'])) {
            //$sql .= " LEFT JOIN " . DB_PREFIX . "product_attribute pa ON (pa.product_id = p.product_id)";
        }

        if (!empty($data['filter_attributes'])) {
            //$sql .= " LEFT JOIN " . DB_PREFIX . "attribute attr ON (attr.attribute_id = pa.attribute_id)";
            //$sql .= " LEFT JOIN " . DB_PREFIX . "attribute_description attrd ON (attrd.attribute_id = attr.attribute_id AND attrd.language_id = '" . $languageId . "')";
        }

        if (!empty($data['filter_option_value_ids'])) {
            //$sql .= " LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (pov.product_id = p.product_id)";
            //$sql .= " LEFT JOIN " . DB_PREFIX . "option_description od ON (od.option_id = pov.option_id AND od.language_id = '" . $languageId . "')";
            //$sql .= " LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ovd.option_value_id = pov.option_value_id AND ovd.language_id = '" . $languageId . "')";
        }

        if (!empty($data['filter_variant_value_ids'])) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_variant pvv ON (pvv.product_id = p.product_id)";
            $sql .= " LEFT JOIN " . DB_PREFIX . "variant_description vd ON (vd.variant_id = pvv.variant_id AND vd.language_id = '" . $languageId . "')";
            $sql .= " LEFT JOIN " . DB_PREFIX . "variant_value_description vvd ON (vvd.variant_value_id = pvv.variant_value_id AND vvd.language_id = '" . $languageId . "')";
        }

        $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id) LEFT JOIN " . DB_PREFIX . "category cate ON (ptc.category_id = cate.category_id AND cate.status = 1)";

        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)";

        $sql .= " LEFT JOIN " . DB_PREFIX . "manufacturer m ON (m.manufacturer_id = p.manufacturer_id)";

        return $sql;
    }

    private function buildFilterWhereSql($sql, $data)
    {
        $currentDatetime = date('Y-m-d H:i:s');
        $sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= '{$currentDatetime}' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

        if (!array_get($data, 'filter_variant_value_ids')) {
            //$sql .= " AND p.parent_id = 0";
        }

        if (!empty($data['filter_category_id'])) {
            $filterCategoryId = $data['filter_category_id'];
            if (!empty($data['filter_sub_category'])) {
                if (is_array($filterCategoryId)) {
                    $filterCategoryIds = implode(',', $filterCategoryId);
                    $sql .= " AND cp.path_id in (" . $filterCategoryIds . ")";
                } else {
                    $sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
                }
            } else {
                if (is_array($filterCategoryId)) {
                    $filterCategoryIds = implode(',', $filterCategoryId);
                    $sql .= " AND p2c.category_id in (" . $filterCategoryIds . ")";
                } else {
                    $sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
                }
            }

            if (!empty($data['filter_filter'])) {
                $implode = array();

                $filters = explode(',', $data['filter_filter']);

                foreach ($filters as $filter_id) {
                    $implode[] = (int)$filter_id;
                }

                $sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
            }
        }

        if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
            $sql .= " AND (";

            if (!empty($data['filter_name'])) {
                $implode = array();

                $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

                foreach ($words as $word) {
                    $implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
                    //$implode[] = "(pd.name LIKE '%" . $this->db->escape($word) . "%' OR pa.text LIKE '%" . $this->db->escape($word) . "%')";
                }

                if ($implode) {
                    $sql .= " " . implode(" AND ", $implode) . "";
                }

                if (!empty($data['filter_description'])) {
                    $sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
                }
            }

            if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
                $sql .= " OR ";
            }

            if (!empty($data['filter_tag'])) {
                $implode = array();

                $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

                foreach ($words as $word) {
                    $implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
                }

                if ($implode) {
                    $sql .= " " . implode(" AND ", $implode) . "";
                }
            }

            if (!empty($data['filter_name'])) {
                /*$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";*/
            }

            $sql .= ")";
        }

        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }

        if (isset($data['parent_id'])) {
            $sql .= " AND p.parent_id = '" . (int)$data['parent_id'] . "'";
        }
        
        if (!empty($data['filter_brand_ids'])) {
            $brandIds = '(' . implode(',', $data['filter_brand_ids']) . ')';
            $sql .= " AND p.manufacturer_id in " . $brandIds;
        }

        if (!empty($data['filter_option_value_ids'])) {
            $optionValueIds = '(' . implode(',', $data['filter_option_value_ids']) . ')';
            $sql .= " AND pov.option_value_id in " . $optionValueIds;
        }

        if (!empty($data['filter_variant_value_ids'])) {
            $variantValueIds = '(' . implode(',', $data['filter_variant_value_ids']) . ')';
            $sql .= " AND pvv.variant_value_id in " . $variantValueIds;
        }

        if (!empty($data['filter_attributes'])) {
            $productAttributes = '(' . implode(',', array_keys($data['filter_attributes'])) . ')';
            $sql .= " AND pa.attribute_id in " . $productAttributes;

            $attrValues = $this->mergeAttributeValues($data['filter_attributes']);
            $productAttributes = '("' . implode('","', $attrValues) . '")';
            $sql .= " AND pa.text in " . $productAttributes;
        }

        if (!empty($data['filter_stock_status_ids'])) {
            $stockStatusId = '(' . implode(',', $data['filter_stock_status_ids']) . ')';
            $sql .= " AND p.stock_status_id in " . $stockStatusId;
        }

        if (isset($data['filter_in_stock'])) {
            if (array_get($data, 'filter_in_stock')) {
                $sql .= " AND p.quantity > 0";
            } else {
                $sql .= " AND p.quantity <= 0";
            }
        }

        if (!empty($data['filter_price']) && count($data['filter_price']) == 2) {
            $sql .= " AND p.price > {$data['filter_price'][0]} and p.price <= {$data['filter_price'][1]}";
        }

        return $sql;
    }

    private function buildFilterSql($sql, $data)
    {
        $sql = $this->buildFilterJoinSql($sql, $data);
        $sql = $this->buildFilterWhereSql($sql, $data);
        return $sql;
    }

    private function mergeAttributeValues($values)
    {
        $result = array();
        foreach (array_values($values) as $value) {
            $result = array_merge($result, $value);
        }
        return $result;
    }


    public function getProduct($product_id)
    {
        $product_data = isset($this->products[$product_id]) ? $this->products[$product_id] : null;
        if (is_array($product_data) && empty($product_data)) {
            return false;
        } elseif ($product_data) {
            return $product_data;
        }
        $cacheKey = 'product.detail_' . $this->getCacheKey($product_id);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $sql = $this->getProductSql($product_id);
        $query = $this->db->query($sql);

        if ($query->num_rows) {
            $product_data = $this->handleProduct($query->row);
            $this->products[$product_id] = $product_data;
            $this->setCacheData($cacheKey, $product_data);
            return $product_data;
        } else {
            $product_data = array();
            $this->products[$product_id] = $product_data;
            $this->setCacheData($cacheKey, $product_data);
            return false;
        }
    }

    public function getProducts($data = array(), $withDetail = true)
    {
        $cacheKey = 'product.products_' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $sql = "SELECT p.product_id";
        if ($withDetail) {
            if ($this->useIndex) {
                $sql .= ", `index`.rating, `index`.sales, `index`.discount, `index`.special ";
            } else {
                $sql .= ", (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "order_product_review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT SUM(quantity) AS total FROM " . DB_PREFIX . "order_product op WHERE op.product_id = p.product_id GROUP BY op.product_id) AS sales, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00 00:00:00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00 00:00:00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";
            }
        }

        $sql = $this->buildFilterJoinSql($sql, $data);
        if ($this->useIndex) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_index `index` ON (p.product_id = `index`.product_id AND `index`.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "')";
        }
        if (array_get($data, 'sort') == 'pdis.date_start') {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_discount pdis ON (pdis.product_id = p.product_id AND pdis.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((pdis.date_start = '0000-00-00' OR pdis.date_start < NOW()) AND (pdis.date_end = '0000-00-00' OR pdis.date_end > NOW())))";
        }

        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.quantity',
            'p.price',
            'rating',
            'p.sort_order',
            'p.date_added',
            'p.viewed',
            'sales',
            'm.name',
            'p.date_available',
            'pdis.date_start'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
                $sql .= " ORDER BY CONVERT(LCASE(" . $data['sort'] . ") USING GBK)";
            } elseif ($data['sort'] == 'p.price') {
                $sql .= " ORDER BY (CASE WHEN special > 0 THEN special WHEN discount > 0 THEN discount ELSE p.price END)";
            } elseif ($data['sort'] == 'p.date_available') {
                $sql .= " ORDER BY p.date_available";
            } else {
                $sql .= " ORDER BY " . $data['sort'];
            }
        } else {
            $sql .= " ORDER BY p.sort_order";
        }

        $sort = array_get($data, 'sort');
        $specialSorts = ['pdis.date_start', 'sales', 'p.viewed', 'p.price', 'm.name'];
        if ($sort == 'p.date_available') {
            $secondSort = 'p.viewed DESC';
        } elseif (in_array($sort, $specialSorts)) {
            $secondSort = 'p.date_available DESC';
        } else {
            if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
                $secondSort = 'CONVERT(LCASE(pd.name) USING GBK) DESC';
            } else {
                $secondSort = 'CONVERT(LCASE(pd.name) USING GBK) ASC';
            }
        }

        if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
            $sql .= " DESC, {$secondSort}";
        } else {
            $sql .= " ASC, {$secondSort}";
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

        $productIds = $productData = $productList = array();
        foreach ($query->rows as $result) {
            $productIds[] = $result['product_id'];
        }

        $productData = $this->getProductsByIds(['product'=>$productIds]);
        foreach ($productIds as $productId) {
            $productList[] = array_get($productData, $productId);
        }
        
        $this->setCacheData($cacheKey, $productList);
        return $productList;
    }

    public function getProductsByIds($data)
    {   
        $productIds     = (isset($data['product']) && !empty($data['product'])) ? $data['product'] : [];
        if (empty($productIds))  return [];

        $cacheKey       = 'product.ids_' . $this->getCacheKey($data);
        if ($result     = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $sql            = $this->getProductSql($productIds);
        $query          = $this->db->query($sql);
        $products       = [];

        if ($query->num_rows) {
            $detailArray = $this->switchKey($query->rows, 'product_id');
            foreach ($productIds as $productId) {
                $product = array_get($detailArray, $productId);
                if (!empty($product)) {
                    $products[$product['product_id']] = $this->handleProduct($product);
                }
            }
        }

        $this->setCacheData($cacheKey, $products);
        return $products;
    }

    public function getTotalProducts($data = array())
    {
        $cacheKey = 'product.total_' . $this->getCacheKey($data);
        $total = $this->getCacheData($cacheKey);
        if ($total || $total === 0) {
            //return $total;
        }

        $filterCategoryId = array_get($data, 'filter_category_id');
        $subCategory = array_get($data, 'filter_sub_category');
        if (is_array($filterCategoryId)) {
            if ($subCategory) {
                $sql = "SELECT cp.path_id as id, COUNT(DISTINCT p.product_id) AS total";
            } else {
                $sql = "SELECT p2c.category_id as id, COUNT(DISTINCT p.product_id) AS total";
            }
        } else {
            $sql = "SELECT COUNT(DISTINCT p.product_id) AS total";
        }
        $sql = $this->buildFilterSql($sql, $data);
        if (is_array($filterCategoryId)) {
            if ($subCategory) {
                $sql .= " GROUP BY cp.path_id";
            } else {
                $sql .= " GROUP BY p2c.category_id";
            }
        }

        $query = $this->db->query($sql);
        if (is_array($filterCategoryId)) {
            $total = $query->rows;
        } else {
            $total = (int)$query->row['total'];
        }

        $this->setCacheData($cacheKey, $total);

        return $total;
    }

    public function getTotalProductsFromAllCategories($subCategory = true)
    {
        $config = $this->config;
        $cache = new \Cache($config->get('cache_engine'), $config->get('cache_expire'));
        $cacheKey = 'product.total_all_category';
        $result = $cache->get($cacheKey);
        if ($result) {
            return $result;
        }

        $productTotals = $categoryIds = $result = array();
        $this->load->model('catalog/category');
        $categories = $this->model_catalog_category->getAllCategoryIds();
        foreach ($categories as $category) {
            $categoryIds[] = $category['category_id'];
        }

        if ($this->config->get('config_product_count') && $categoryIds) {
            $filter_data = array(
                'filter_category_id' => $categoryIds,
                'filter_sub_category' => $subCategory
            );
            $productTotals = $this->model_catalog_product_pro->getTotalProducts($filter_data);
        }

        $result = array();
        foreach ($productTotals as $total) {
            $result[$total['id']] = $total['total'];
        }
        $cache->set($cacheKey, $result);
        return $result;
    }

    public function getProductTotalGroupBrand($data = array())
    {
        $cacheKey = 'product.brand_total_' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $data['filter_brand_ids'] = array();
        $sql = "SELECT p.manufacturer_id, ma.name, ma.image, COUNT(DISTINCT p.product_id) AS total";
        $sql = $this->buildFilterJoinSql($sql, $data);
        $sql .= " INNER JOIN " . DB_PREFIX . "manufacturer ma ON (p.manufacturer_id = ma.manufacturer_id)";
        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " GROUP BY p.manufacturer_id";
        $query = $this->db->query($sql);
        $result = $query->rows;

        $this->setCacheData($cacheKey, $result);

        return $result;
    }

    public function getProductTotalGroupProductStockStatus($data = array())
    {
        $cacheKey = 'product.stock_total_' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $data['filter_stock_status_ids'] = array();
        $sql = "SELECT ss.stock_status_id, ss.name, COUNT(DISTINCT p.product_id) AS total";
        $sql = $this->buildFilterJoinSql($sql, $data);
        $sql .= " LEFT JOIN " . DB_PREFIX . "stock_status ss ON (p.stock_status_id = ss.stock_status_id)";
        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " GROUP BY p.stock_status_id";
        $query = $this->db->query($sql);
        $result = $query->rows;

        $this->setCacheData($cacheKey, $result);

        return $result;
    }

    public function getProductTotalGroupStockStatus($data = array())
    {
        $stockStatusTotals = array(
            'in_stock' => $this->getProductTotalByInStock($data),
            'out_of_stock' => $this->getProductTotalByStockOut($data)
        );
        return $stockStatusTotals;
    }

    private function getProductTotalByInStock($data)
    {
        $data['in_stock'] = 1;
        $total = $this->getProductTotalByStock($data);
        return $total;
    }

    private function getProductTotalByStockOut($data)
    {
        $data['in_stock'] = 0;
        $total = $this->getProductTotalByStock($data);
        return $total;
    }

    private function getProductTotalByStock($data)
    {
        unset($data['filter_in_stock']);
        $sql = "SELECT COUNT(DISTINCT p.product_id) AS total";
        $sql = $this->buildFilterJoinSql($sql, $data);
        $sql = $this->buildFilterWhereSql($sql, $data);
        if (array_get($data, 'in_stock')) {
            $sql .= " AND p.quantity > 0";
        } else {
            $sql .= " AND p.quantity <= 0";
        }

        $query = $this->db->query($sql);
        $result = $query->row['total'];
        return $result;
    }

    public function getProductTotalGroupCategory($data = array())
    {
        $cacheKey = 'product.category_total_' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $sql = "SELECT cate.category_id,COUNT(DISTINCT p.product_id) AS total";
        $sql = $this->buildFilterJoinSql($sql, $data);
        $sql .= " LEFT JOIN " . DB_PREFIX . "category_description cd ON (cate.category_id = cd.category_id)";
        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        $sql .= " GROUP BY ptc.category_id";
        $query = $this->db->query($sql);
        $result = $query->rows;

        $this->setCacheData($cacheKey, $result);

        return $result;
    }

    public function getProductPriceRange($data = array())
    {
        $cacheKey = 'product.price_range_' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $data['filter_price'] = array();
        $sql = "SELECT DISTINCT p.product_id, p.price";
        $sql = $this->buildFilterJoinSql($sql, $data);
        $sql .= " LEFT JOIN " . DB_PREFIX . "category_description cd ON (cate.category_id = cd.category_id)";
        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        $sql .= " ORDER BY p.price";

        $query = $this->db->query($sql);
        $result = $query->rows;
        $ranges = [];
        if (count($result) >= 2) {
            $ranges[] = (int)array_first($result)['price'];
            $ranges[] = (int)array_last($result)['price'];
        }
        $this->setCacheData($cacheKey, $ranges);

        return $ranges;
    }

    public function getProductTotalGroupAttrValues($data)
    {
        $attributes = $this->getProductTotalGroupAttributes($data);
        foreach ($attributes as $key => $attribute) {
            $data['attribute_id'] = $attribute['attribute_id'];
            $attributes[$key]['values'] = $this->getProductTotalGroupAttrValue($data);
        }
        return $attributes;
    }

    private function getProductTotalGroupAttributes($data = array())
    {
        $cacheKey = 'product.attribute_total_' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $data['filter_attributes'] = array();
        $sql = "SELECT pa.attribute_id, attrd.name, COUNT(DISTINCT p.product_id) AS total";
        $sql = $this->buildFilterJoinSql($sql, $data);
        if (!array_get($data, 'filter_name')) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_attribute pa ON (pa.product_id = p.product_id)";
        }
        $sql .= " LEFT JOIN " . DB_PREFIX . "attribute attr ON (attr.attribute_id = pa.attribute_id)";
        $sql .= " LEFT JOIN " . DB_PREFIX . "attribute_description attrd ON (attrd.attribute_id = attr.attribute_id AND attrd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        $sql .= " GROUP BY pa.attribute_id";
        $sql .= " ORDER BY attr.sort_order ASC";

        $query = $this->db->query($sql);
        $result = $query->rows;

        $this->setCacheData($cacheKey, $result);

        return $result;
    }

    private function getProductTotalGroupAttrValue($data = array())
    {
        $cacheKey = 'product.av_total_' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $data['filter_attributes'] = array();
        $sql = "SELECT pa.attribute_id, attrd.name as attribute_name, TRIM(pa.text) as text, COUNT(DISTINCT p.product_id) AS total";
        $sql = $this->buildFilterJoinSql($sql, $data);
        if (!array_get($data, 'filter_name')) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_attribute pa ON (pa.product_id = p.product_id)";
        }
        $sql .= " LEFT JOIN " . DB_PREFIX . "attribute attr ON (attr.attribute_id = pa.attribute_id)";
        $sql .= " LEFT JOIN " . DB_PREFIX . "attribute_description attrd ON (attrd.attribute_id = attr.attribute_id AND attrd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " AND pa.attribute_id = '" . (int)$data['attribute_id'] . "'";
        $sql .= " AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        $sql .= " GROUP BY text";

        $query = $this->db->query($sql);
        $result = $query->rows;

        $this->setCacheData($cacheKey, $result);

        return $result;
    }

    public function getProductTotalGroupOptionValues($data = array())
    {
        $cacheKey = 'product.option_total_' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $data['filter_option_value_ids'] = array();
        $languageId = (int)$this->config->get('config_language_id');
        $sql = "SELECT pov.option_id, od.name as option_name, pov.option_value_id, ovd.name as option_value_name, COUNT(DISTINCT p.product_id) AS total";
        $sql = $this->buildFilterJoinSql($sql, $data);
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (pov.product_id = p.product_id)";
        $sql .= " LEFT JOIN " . DB_PREFIX . "option_description od ON (od.option_id = pov.option_id AND od.language_id = '" . $languageId . "')";
        $sql .= " LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ovd.option_value_id = pov.option_value_id AND ovd.language_id = '" . $languageId . "')";
        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " AND ovd.language_id = '" . $languageId . "'";
        $sql .= " GROUP BY pov.option_value_id";

        $query = $this->db->query($sql);

        $optionGroups = array();
        foreach ($query->rows as $item) {
            $optionId = $item['option_id'];
            $options[$optionId][] = array(
                'option_value_id' => $item['option_value_id'],
                'option_value_name' => $item['option_value_name'],
                'total' => $item['total']
            );
            $optionGroups[$optionId] = array(
                'option_id' => $optionId,
                'name' => $item['option_name'],
                'options' => $options[$optionId]
            );
        }
        $this->setCacheData($cacheKey, $optionGroups);
        return $optionGroups;
    }

    public function getProductTotalGroupVariantValues($data = array())
    {
        $cacheKey = 'product.variant_total_' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $data['filter_variant_value_ids'] = array();
        $languageId = (int)$this->config->get('config_language_id');
        $sql = "SELECT pvv.variant_id, vd.name as variant_name, pvv.variant_value_id, vvd.name as variant_value_name, COUNT(DISTINCT p.product_id) AS total";
        $sql = $this->buildFilterJoinSql($sql, $data);
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_variant pvv ON (pvv.product_id = p.product_id)";
        $sql .= " LEFT JOIN " . DB_PREFIX . "variant_description vd ON (vd.variant_id = pvv.variant_id AND vd.language_id = '" . $languageId . "')";
        $sql .= " LEFT JOIN " . DB_PREFIX . "variant_value_description vvd ON (vvd.variant_value_id = pvv.variant_value_id AND vvd.language_id = '" . $languageId . "')";
        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " AND vvd.language_id = '" . $languageId . "'";
        $sql .= " GROUP BY pvv.variant_value_id";

        $query = $this->db->query($sql);

        $variantGroups = array();
        foreach ($query->rows as $item) {
            $variantId = $item['variant_id'];
            $variants[$variantId][] = array(
                'variant_value_id' => $item['variant_value_id'],
                'variant_value_name' => $item['variant_value_name'],
                'total' => $item['total']
            );
            $variantGroups[$variantId] = array(
                'variant_id' => $variantId,
                'name' => $item['variant_name'],
                'variants' => $variants[$variantId]
            );
        }
        $this->setCacheData($cacheKey, $variantGroups);
        return $variantGroups;
    }

    public function getProductsForApi($data = [], $withDetail = true)
    {
        $cacheKey = 'product.products_forapi' . $this->getCacheKey($data);
        if ($result = $this->getCacheData($cacheKey)) {
            return $result;
        }

        $sql = "SELECT p.product_id";
        if ($withDetail) {
            if ($this->useIndex) {
                $sql .= ", `index`.rating, `index`.sales, `index`.discount, `index`.special ";
            } else {
                $sql .= ", (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "order_product_review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_product_review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews,(SELECT SUM(quantity) AS total FROM " . DB_PREFIX . "order_product op WHERE op.product_id = p.product_id GROUP BY op.product_id) AS sales, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00 00:00:00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00 00:00:00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";
            }
        }

        $sql = $this->buildFilterJoinSql($sql, $data);
        if ($this->useIndex) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_index `index` ON (p.product_id = `index`.product_id AND `index`.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "')";
        }
        if (array_get($data, 'sort') == 'pdis.date_start') {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_discount pdis ON (pdis.product_id = p.product_id AND pdis.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((pdis.date_start = '0000-00-00' OR pdis.date_start < NOW()) AND (pdis.date_end = '0000-00-00' OR pdis.date_end > NOW())))";
        }
        $sql = $this->buildFilterWhereSql($sql, $data);
        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.quantity',
            'p.price',
            'rating',
            'p.sort_order',
            'p.date_added',
            'p.viewed',
            'sales',
            'm.name',
            'p.date_available',
            'pdis.date_start'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
                $sql .= " ORDER BY CONVERT(LCASE(" . $data['sort'] . ") USING GBK)";
            } elseif ($data['sort'] == 'p.price') {
                $sql .= " ORDER BY (CASE WHEN special > 0 THEN special WHEN discount > 0 THEN discount ELSE p.price END)";
            } elseif ($data['sort'] == 'p.date_available') {
                $sql .= " ORDER BY p.date_available";
            } else {
                $sql .= " ORDER BY " . $data['sort'];
            }
        } else {
            $sql .= " ORDER BY p.sort_order";
        }

        $sort = array_get($data, 'sort');
        $specialSorts = ['pdis.date_start', 'sales', 'p.viewed', 'p.price', 'm.name'];
        if ($sort == 'p.date_available') {
            $secondSort = 'p.viewed DESC';
        } elseif (in_array($sort, $specialSorts)) {
            $secondSort = 'p.date_available DESC';
        } else {
            if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
                $secondSort = 'CONVERT(LCASE(pd.name) USING GBK) DESC';
            } else {
                $secondSort = 'CONVERT(LCASE(pd.name) USING GBK) ASC';
            }
        }

        if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
            $sql .= " DESC, {$secondSort}";
        } else {
            $sql .= " ASC, {$secondSort}";
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

        $productIds = $productData = $productList = array();
        foreach ($query->rows as $result) {
            $productIds[] = $result['product_id'];
        }

        $productData = $this->getProductsByIds(['product'=>$productIds]);
        foreach ($productIds as $productId) {
            $productList[] = array_get($productData, $productId);
        }

        $this->setCacheData($cacheKey, $productList);
        return $productList;
    }
}
