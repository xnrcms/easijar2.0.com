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
 * @created 2018-01-05 16:56
 * @modified 2018-01-05 16:56
 */
class ControllerExtensionModuleFlashSale extends Controller
{
    private $products = array();
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->products = $this->config->get('module_flash_sale_products');
    }

    public function index()
    {
        static $module = 0;

        if (defined('TIME_PRC') && TIME_PRC) {
            date_default_timezone_set('PRC');
        }

        // products
        $data['products'] = array();
        $products = $this->products;
        if ($products) {
            $this->load->model('tool/image');
            $this->load->model('catalog/product');
            $this->load->model('extension/module/flash_sale');

            $this->load->language('extension/module/flash_sale');
            $this->document->addScript('catalog/view/javascript/count-down/moment.js');
            $this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.min.js');
            $this->document->addScript('catalog/view/javascript/count-down/jquery.countdown.min.js');
            $this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');

            $config_start = $this->config->get('module_flash_sale_start');
            $data['date_start'] = $config_start;
            if ($config_start && time() > strtotime($data['date_start'])) {
                $data['show'] = true;
            } else {
                $data['show'] = false;
            }
            $data['date_end'] = $this->config->get('module_flash_sale_end');
            $seconds = null;
            if ($data['date_end']) {
                $seconds = strtotime($data['date_end']) - strtotime('now');
            }
            $data['seconds'] = $seconds;
            $products_id = array();
            $product_counts = array();
            foreach ($products as $product) {
                $product_count = (int)$product['count'];
                if (!$product_count) {
                    continue;
                }
                $products_id[] = $product['product_id'];
                $product_counts[$product['product_id']] = (int)$product['count'];
            }

            if ($products_id) {
                $sale_counts = model('extension/module/flash_sale')->getSaleCounts($products_id, $data['date_start'], $data['date_end']);
                $products_info = model('extension/module/flash_sale')->getProductsInfo($products_id);
                foreach ($products_id as $product_id) {
                    $sell_product_count = array_get($sale_counts, $product_id, 0);
                    if (array_get($product_counts, $product_id) && $sell_product_count >= $product_counts[$product_id]) {
                        $sell_out = false;
                    } else {
                        $sell_out = true;
                    }
                    $product_info = array_get($products_info, $product_id, array());
                    if ($product_info) {
                        if ($this->customer->isLogged() || (float)$product['price']) {
                            $special = $this->currency->format($product['price'], $this->session->data['currency']);
                        } else {
                            $special = false;
                        }
                        $single_product = model('catalog/product')->handleSingleProduct($product_info, 300, 300);
                        if ($single_product) {
                            $single_product['special'] = $special;
                            $single_product['sell_out'] = $sell_out;
                            $data['products'][] = $single_product;
                        }
                    }
                }
            }

            $data['module'] = $module++;

            return $this->load->view('extension/module/flash_sale', $data);
        }
    }
}