<?php

/**
 * flash.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author StifferChen <chenlin@opencart.cn>
 * @created 2018-05-24 15:20
 * @modified 2018-05-24 15:20
 */
class Flash
{
    private static $singleton;
    private $registry;

    private function __construct()
    {
        $this->registry = Registry::getSingleton();
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    public function canUpdateCart($cart_id, $quantity)
    {
        $update = true;
        $products = $this->cart->getCartProducts();
        $product = null;
        if ($products) {
            foreach ($products as $item_product) {
                if ($item_product['cart_id'] == $cart_id) {
                    $product = $item_product;
                    break;
                }
            }
        }
        if ($product) {
            $flash_data = $this->getFlashPriceAndCount($product['product_id']);
            if ($flash_data && $flash_data['count'] && $quantity > $flash_data['count']) {
                $update = false;
            }
        }
        return $update;
    }

    public function getFlashPriceAndCount($product_id = '')
    {
        // 未传入商品ID
        if (!$product_id) {
            return false;
        }

        // 模块未开启
        if (!$this->config->get('module_flash_sale_status')) {
            return false;
        }

        // 时间配置错误，或活动已结束
        if (!$this->getStatus()) {
            return false;
        }

        // 没有秒杀商品
        $flash_products = $this->config->get('module_flash_sale_products');
        if (!$flash_products) {
            return false;
        }

        $product = null;
        foreach ($flash_products as $flash_product) {
            if ($flash_product['product_id'] == $product_id) {
                $product = $flash_product;
                break;
            }
        }

        // 商品没有参与秒杀
        if (!$product) {
            return false;
        }

        $setting_false_price = (float)$product['price'];
        $setting_false_count = (int)$product['cart_count'];

        // 没有登录，直接去秒杀价和设置的秒杀数量
        if (!$this->customer->isLogged()) {
            return array(
                'price' => $setting_false_price,
                'checkout' => true,
                'count' => $setting_false_count
            );
        }

        // 没有设置限购数量
        if (!$setting_false_count) {
            return array(
                'price' => $setting_false_price,
                'checkout' => true,
                'count' => $setting_false_count
            );
        }

        // 活动期间的购买量
        $flash_order_count = $this->getFlashProductCount($product_id, $this->getStart(), $this->getEnd());
        // 活动期间没有购买过该商品
        if (!$flash_order_count) {
            return array(
                'price' => $setting_false_price,
                'checkout' => true,
                'count' => $setting_false_count
            );
        }

        // 已达到秒杀数量限制
        if ($flash_order_count >= $setting_false_count) {
            return array(
                'price' => $setting_false_price,
                'checkout' => false,
                'count' => $setting_false_count
            );
        }

        return array(
            'price' => $setting_false_price,
            'checkout' => true,
            'count' => $setting_false_count - $flash_order_count
        );
    }

    public function getStatus()
    {
        if ($this->getEnd() < $this->getStart() || $this->getEnd() < date('Y-m-d H:i:S', time())) {
            return false;
        }

        return true;
    }

    protected function getEnd()
    {
        return $this->config->get('module_flash_sale_end');
    }

    protected function getStart()
    {
        return $this->config->get('module_flash_sale_start');
    }

    private function getFlashProductCount($product_id, $start, $end)
    {
        $sql = "select sum(op.quantity) `count` from " . DB_PREFIX . "order_product op left join `" . DB_PREFIX . "order` o on o.order_id=op.order_id where o.customer_id = " . (int)$this->customer->getId() . " and date('" . $this->db->escape($start) . "') <= date(o.date_added) and date(o.date_added) <= date('" . $this->db->escape($end) . "') and op.product_id=" . (int)$product_id;
        $row = $this->db->query($sql)->row;
        return $row['count'];
    }

    public static function getSingleton()
    {
        if (!(self::$singleton instanceof self)) {
            self::$singleton = new self();
        }
        return self::$singleton;
    }
}