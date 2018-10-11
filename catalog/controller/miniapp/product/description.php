<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-04-01 16:14:04
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-05-30 14:32:33
 */

class ControllerMiniAppProductDescription extends Controller
{
    public function index()
    {
        $data['error'] = null;
        $this->load->language('app/app');

        if (!$productId = (int)array_get($this->request->get, 'product_id')) {
            $data['error'] = 'No product_id param.';
        } else {
            $this->load->model('catalog/product');
            if ($product = $this->model_catalog_product->getProduct($productId)) {
                $data['description'] = html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8');
                $data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($productId);
            } else {
                $data['error'] = "Product not found (product_id: {$productId})";
            }
        }

        $this->response->setOutput($this->load->view('miniapp/product/description', $data));
    }
}
