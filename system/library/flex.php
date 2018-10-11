<?php

/**
 * flex.php
 *
 * @copyright 2018 OpenCart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-07-13 15:44
 * @modified 2018-07-13 15:44
 */
class Flex
{
    private static $instance;
    private $registry = null;

    private function __construct()
    {
        $this->registry = Registry::getSingleton();
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
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
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    public function getShippingDataByCountryId($country_id = '', $zone_id = '')
    {
        if (!$country_id || !$zone_id) {
            return array();
        }

        return $this->calculateShippingCostByCountryIdAndWeight($country_id, $zone_id);
    }

    protected function calculateShippingCostByCountryIdAndWeight($country_id, $zone_id)
    {
        $all_costs = $this->getAllCostsByCountryId($country_id, $zone_id);
        return $this->sortCost($all_costs);
    }

    protected function sortCost($all_sorts)
    {
        if(!$all_sorts){
            return array();
        }
        $cost = array();
        foreach($all_sorts as $sort){
            $cost[] = $sort['cost'];
        }

        array_multisort($cost, SORT_DESC, $all_sorts);
        return $all_sorts;
    }

    protected function getAllCostsByCountryId($country_id, $zone_id, $input_weight = array())
    {
        if (!$input_weight) {
            $weight = $this->getCalculateWeight();
        } else {
            $weight = $input_weight;
        }

        $shippings = $this->getShippings();
        $filter_shippings = array();
        if ($shippings) {
            foreach ($shippings as $shipping) {
                $geo_zone_id = $shipping['geo_zone_id'];
                if (!$geo_zone_id) {
                    $filter_shippings[] = $shipping;
                } else {
                    $country_geo_zone = $this->fitShippingCountryAndZone($country_id, $zone_id, $geo_zone_id);
                    if ($country_geo_zone) {
                        $filter_shippings[] = $shipping;
                    }
                }
            }
        }
        $shipping_cost = array();
        if ($filter_shippings) {
            foreach ($filter_shippings as $shipping) {
                $calculate_type = isset($shipping['calculate_type']) ? $shipping['calculate_type'] : '';
                if (!$calculate_type) {
                    $calculate_weight = $weight['weight'];
                } elseif ($calculate_type == 1) {
                    $calculate_weight = $weight['volume'];
                } else {
                    $calculate_weight = $weight['compare_weight'];
                }
                $weight_type = $shipping['weight_type'];
                if ($weight_type) {
                    $top_weight = $shipping['top_weight'];
                    $top_price = $shipping['top_price'];
                    $input_top_prices = isset($shipping['top_prices']) ? $shipping['top_prices'] : array();
                    $top_prices = array();
                    if ($input_top_prices) {
                        foreach ($input_top_prices as $item) {
                            if (!$item['unit_weight_min'] || !$item['unit_weight_max'] || !$item['unit_per_weight'] || !$item['unit_per_price']) {
                                continue;
                            }
                            $top_prices[] = $item;
                        }
                    }
                    if (!$top_prices) {
                        $max_weight = $top_weight;
                    } else {
                        $last_top_price = end($top_prices);
                        $max_weight = $last_top_price['unit_weight_max'];
                    }

                    if ($calculate_weight > $max_weight) {
                        continue;
                    }
                    if ($top_weight > $calculate_weight) {
                        $shipping_price = $top_price;
                    } else {
                        $tmp_price = $top_price;
                        foreach ($top_prices as $price) {
                            if ($price['unit_weight_max'] >= $calculate_weight) {
                                $differ_weight = $calculate_weight - $price['unit_weight_min'];
                            } else {
                                $differ_weight = $price['unit_weight_max'] - $price['unit_weight_min'];
                            }

                            $level_weight = $differ_weight / $price['unit_per_weight'];
                            $tmp_price += $level_weight * $price['unit_per_price'];
                            if ($price['unit_weight_max'] >= $calculate_weight) {
                                break;
                            }
                        }
                        $shipping_price = $tmp_price;
                    }
                    $shipping_cost[] = array(
                        'cost' => $shipping_price,
                        'shipping' => $shipping
                    );
                } else {
                    $shipping_rate = preg_replace('/\s+/i', ',', $shipping['rate']);
                    $rate_arr = explode(',', $shipping_rate);
                    $rate = $this->sortRate($rate_arr);
                    $rate_items = explode(',', $rate);
                    if($rate_items && count($rate_items) == 1){
                        $rate_cost_arr = explode(':', $rate_items[0]);
                        if ($rate_cost_arr && $calculate_weight <= (float)$rate_cost_arr[0]){
                            $shipping_cost[] = array(
                                'cost' => (float)$rate_cost_arr[1],
                                'shipping' => $shipping
                            );
                        }
                    }elseif($rate_items && count($rate_items) > 1){
                        foreach($rate_items as $rate_item){
                            $rate_cost_arr = explode(':', $rate_item);
                            if($rate_cost_arr && $calculate_weight <= (float)$rate_cost_arr[0]){
                                $shipping_cost[] = array(
                                    'cost' => (float)$rate_cost_arr[1],
                                    'shipping' => $shipping
                                );
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $shipping_cost;
    }

    protected function sortRate($rates)
    {
        if (count($rates) <= 1) {
            return $rates;
        }

        $rate_sort_arr = array();
        foreach ($rates as $item) {
            $preg_str = '/^(0\.0+\d*|0\.\d+|\d+\.?\d*):(0\.0+\d*|0\.\d+|\d+\.?\d*)$/i';
            if(!preg_match($preg_str, $item)){
                continue;
            }
            $item_arr = explode(':', $item);
            $rate_sort_arr[] = array(
                'weight' => $item_arr[0],
                'rate' => $item_arr[1],
            );
        }
        $sort = array(
            'direction' => 'SORT_ASC',
            'field' => 'weight',
        );

        foreach ($rate_sort_arr AS $uniqid => $row) {
            foreach ($row AS $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }

        if (isset($arrSort[$sort['field']]) && $sort['direction']) {
            array_multisort($arrSort[$sort['field']], constant($sort['direction']), $rate_sort_arr);
        }

        $tmp_arr = array();
        foreach ($rate_sort_arr as $item) {
            $tmp_arr[] = $item['weight'] . ':' . $item['rate'];
        }

        return implode(',', $tmp_arr);
    }

    protected function fitShippingCountryAndZone($country_id, $zone_id, $geo_zone_id)
    {
        $row = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "' AND country_id = '" . (int)$country_id . "' AND (zone_id = '" . (int)$zone_id . "' OR zone_id = '0')")->row;
        return $row ? true : false;
    }

    protected function getCalculateWeight()
    {
        $products_volume = $this->getCartProductsVolume();
        $products_weight = $this->getCartProductWeight();
        $compare_weight = $products_volume > $products_weight ? $products_volume : $products_weight;
        return array(
            'weight' => $products_weight,
            'volume' => $products_volume,
            'compare_weight' => $compare_weight
        );
    }

    protected function getCartProductsVolume()
    {
        $products = $this->getCartProducts();
        $volume = 0;
        if ($products) {
            $this->load->model("catalog/product");
            foreach ($products as $product) {
                $product_id = $product['product_id'];
                $product_info = $this->model_catalog_product->getProduct($product_id);
                if ($product_info) {
                    $length = $this->length->convert($product['length'], $product['length_class_id'], $this->config->get('config_length_class_id'));
                    $width = $this->length->convert($product['width'], $product['length_class_id'], $this->config->get('config_length_class_id'));
                    $height = $this->length->convert($product['height'], $product['length_class_id'], $this->config->get('config_length_class_id'));
                    $volume += $length * $height * $width * $product['quantity'];
                }
            }
        }
        return $volume;
    }

    protected function getCartProductWeight()
    {
        $products = $this->getCartProducts();
        $weight = 0;
        if ($products) {
            $this->load->model("catalog/product");
            foreach ($products as $product) {
                $product_id = $product['product_id'];
                $product_info = $this->model_catalog_product->getProduct($product_id);
                if ($product_info) {
                    $weight += $this->weight->convert($product['weight'], $product['weight_class_id'], $this->config->get('config_weight_class_id'));
                }
            }
        }

        return $weight;
    }

    protected function getCartProducts()
    {
        return $this->cart->getproducts();
    }

    protected function getShippings()
    {
        $shipping = $this->config->get('shipping_flex_info');
        $results = array();
        if ($shipping) {
            foreach ($shipping as $item) {
                $express_id = array_get($item, 'express_id', 0);
                if ($express_id) {
                    $express_title = $this->getExpressTitle($express_id);
                    if ($express_title) {
                        $item['express_title'] = $express_title;
                        $results[] = $item;
                    }
                }
            }
        }
        return $results;
    }

    protected function getExpressTitle($express_id)
    {
        $row = $this->db->query("select title from " . DB_PREFIX . "express_title where express_id=" . (int)$express_id . " and language_id=" . (int)$this->config->get('config_language_id'))->row;
        return array_get($row, 'title', '');
    }

    public function fixFreeShipping($country_id, $zone_id)
    {
        $row = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_flex_free') . "' AND country_id = '" . (int)$country_id . "' AND (zone_id = '" . (int)$zone_id . "' OR zone_id = '0')")->row;
        return $row ? true : false;
    }
}