<?php
class ControllerAppProductDescription extends Controller
{
    public function index()
    {
        $data['error'] = '';
        $data['lang'] = $this->load->language('app/app');

        if ($this->request->server['HTTPS']) {
            $server = $this->config->get('config_ssl');
        } else {
            $server = $this->config->get('config_url');
        }

        $data['base'] = $server;

        if (isset($this->request->get['product_id']) && (int)$this->request->get['product_id']) {
            $productId = (int)$this->request->get['product_id'];

            $this->load->model('catalog/product');
            $productInfo = $this->model_catalog_product->getProduct($productId);

            if (!$productInfo) {
                $data['error'] = 'Product not found.';
            } else {
                $data['description'] = html_entity_decode($productInfo['description'], ENT_QUOTES, 'UTF-8');
                $data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);
            }
        } else {
            $data['error'] = 'Product not found.';
        }

        $this->response->setOutput($this->load->view('app/template/product/description', $data));
    }
}
