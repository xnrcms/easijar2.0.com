<?php
/**
 * msshipping.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-06-06 14:46
 * @modified 2018-06-06 14:46
 */

namespace Seller;

class MsShipping
{
    private static $instance = null;
    private $registry = null;

    const SHIPPING_YES = 1;
    const SHIPPING_NO = 0;
    const Shipping_SELECT = 2;

    private $count = 0;

    private function __construct($registry = null)
    {
        $this->registry = $registry;
        $this->load->model('multiseller/shipping_cost');
        $this->count = $this->model_multiseller_shipping_cost->getTotalShippingCosts();
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function getInstance($registry = null)
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

    public function getCount()
    {
        return $this->count;
    }

    public function shippingRequired()
    {
        $shipping_config = $this->config->get('module_multiseller_seller_shipping');
        if ($shipping_config && $shipping_config == self::SHIPPING_YES && !$this->getCount()) {
            return true;
        }
        return false;
    }
}