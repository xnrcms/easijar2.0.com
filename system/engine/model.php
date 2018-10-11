<?php
/**
 * @package     OpenCart
 * @author      Daniel Kerr
 * @copyright   Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license     https://opensource.org/licenses/GPL-3.0
 * @link        https://www.opencart.com
*/

/**
* Model class
*/
abstract class Model {
    protected $registry;

    public function __construct($registry) {
        $this->registry = $registry;
    }

    public function __get($key) {
        return $this->registry->get($key);
    }

    public function __set($key, $value) {
        $this->registry->set($key, $value);
    }

    protected function getCacheKey($filters = [])
    {
        $customerGroupId = $this->config->get('config_customer_group_id');
        $languageId = $this->config->get('config_language_id');
        $storeId = $this->config->get('config_store_id');
        $filterString = $filters ? json_encode($filters) : '';
        return md5($filterString . $customerGroupId . $languageId . $storeId);
    }

    protected function switchKey($items, $keyName)
    {
        if (empty($items) || !is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            $index = array_get($item, $keyName);
            if ($index) {
                $result[$index] = $item;
            }
        }
        return $result;
    }
}
