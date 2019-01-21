<?php
class ModelCatalogCategory extends Model {
    public function getCategory($category_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

        return $query->row;
    }

    public function getCategoryByIds($category_ids) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) WHERE c.category_id IN (" . implode(',', $category_ids) . ") AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1'");

        return $query->rows;
    }

    public function getCategoriesByParentIds($parent_ids) {
        $cacheKey = 'category.categories_by_parent_ids_' . $this->getCacheKey($parent_ids);
        $result = $this->cache->get($cacheKey);
        if (is_array($result)) {
            return $result;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) WHERE c.parent_id IN (" . implode(',', $parent_ids) . ") AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name)");

        $result = $query->rows;
        $this->cache->set($cacheKey, $result);

        return $result;
    }

    public function getCategories($parent_id = 0) {
        $cacheKey = 'category.categories_' . $this->getCacheKey($parent_id);
        $result = $this->cache->get($cacheKey);
        if (is_array($result)) {
            return $result;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name)");

        $result = $query->rows;
        $this->cache->set($cacheKey, $result);

        return $result;
    }

    public function getCategoryFilters($category_id) {
        $implode = array();

        $query = $this->db->query("SELECT filter_id FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");

        foreach ($query->rows as $result) {
            $implode[] = (int)$result['filter_id'];
        }

        $filter_group_data = array();

        if ($implode) {
            $filter_group_query = $this->db->query("SELECT DISTINCT f.filter_group_id, fgd.name, fg.sort_order FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_group fg ON (f.filter_group_id = fg.filter_group_id) LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY f.filter_group_id ORDER BY fg.sort_order, LCASE(fgd.name)");

            foreach ($filter_group_query->rows as $filter_group) {
                $filter_data = array();

                $filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY f.sort_order, LCASE(fd.name)");

                foreach ($filter_query->rows as $filter) {
                    $filter_data[] = array(
                        'filter_id' => $filter['filter_id'],
                        'name'      => $filter['name']
                    );
                }

                if ($filter_data) {
                    $filter_group_data[] = array(
                        'filter_group_id' => $filter_group['filter_group_id'],
                        'name'            => $filter_group['name'],
                        'filter'          => $filter_data
                    );
                }
            }
        }

        return $filter_group_data;
    }

    public function getCategoryLayoutId($category_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

        if ($query->num_rows) {
            return (int)$query->row['layout_id'];
        } else {
            return 0;
        }
    }

    public function getTotalCategoriesByCategoryId($parent_id = 0) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

        return $query->row['total'];
    }

    public function getParentCategories($category_id) {
        $sql = "SELECT d.category_id, d.name FROM `" . DB_PREFIX . "category_path` p left JOIN oc_category_description d on (d.category_id = p.path_id AND d.language_id = '" . $this->config->get('config_language_id') . "')  WHERE p.category_id = '" . (int)$category_id . "' ORDER BY p.level ASC";
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getAllCategoryIds()
    {
        $sql = "SELECT c.category_id FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order, c.category_id, LCASE(cd.name)";
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getAllCategories()
    {
        $sql = "SELECT c.category_id, cd.name FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order, c.category_id, LCASE(cd.name)";
        $query = $this->db->query($sql);

        $categories = [];
        foreach ($query->rows as $item) {
            $categories[$item['category_id']] = $item;
        }
        return $categories;
    }

    public function getThreeLevelCategories()
    {
        $cacheKey = 'product.three_categories' . $this->getCacheKey();
        $result = $this->cache->get($cacheKey);
        if ($result) {
            return $result;
        }

        $categories = array();
        $categories_1 = $this->model_catalog_category->getCategories(0);
        foreach ($categories_1 as $category_1) {
            $level_2_data = array();
            $categories_2 = $this->model_catalog_category->getCategories($category_1['category_id']);
            foreach ($categories_2 as $category_2) {
                $level_3_data = array();
                $categories_3 = $this->model_catalog_category->getCategories($category_2['category_id']);
                foreach ($categories_3 as $category_3) {
                    $level_3_data[] = array(
                        'category_id' => $category_3['category_id'],
                        'name' => $category_3['name'],
                    );
                }
                $level_2_data[] = array(
                    'category_id' => $category_2['category_id'],
                    'name' => $category_2['name'],
                    'children' => $level_3_data
                );
            }
            $categories[] = array(
                'category_id' => $category_1['category_id'],
                'name' => $category_1['name'],
                'children' => $level_2_data
            );
        }
        $this->cache->set($cacheKey, $categories);
        return $categories;
    }

    public function getCategoriePathByProductId($product_id = 0)
    {
        if ((int)$product_id <= 0)  return '';

        $query = $this->db->query("SELECT DISTINCT path_id FROM `" . DB_PREFIX . "product_to_category` as pc LEFT JOIN `" . DB_PREFIX . "category_path` AS cp ON (pc.category_id = cp.category_id ) WHERE product_id = '" . $product_id . "' ORDER BY `level` ASC");

        $cid        = [];
        if (!empty($query->rows))
        {
            foreach ($query->rows as $item) {
                $cid[$item['path_id']] = $item['path_id'];
            }
        }

        return !empty($cid) ? implode('_', $cid) : '';
    }
}
