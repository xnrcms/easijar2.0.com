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
 * @created 2018-01-04 19:01
 * @modified 2018-01-04 19:01
 */
class ControllerExtensionModuleFlashSale extends Controller
{
    private $error = array();

    public function index()
    {
        $this->document->addScript('view/javascript/laydate/laydate.js');
        $this->language->load('extension/module/flash_sale');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_flash_sale', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'], 'SSL'));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => false
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => ' :: '
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/flash_sale', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => ' :: '
        );

        if (isset($this->request->post['module_flash_sale_start'])) {
            $data['module_flash_sale_start'] = $this->request->post['module_flash_sale_start'];
        } else {
            $data['module_flash_sale_start'] = $this->config->get('module_flash_sale_start');
        }
        if (isset($this->request->post['module_flash_sale_end'])) {
            $data['module_flash_sale_end'] = $this->request->post['module_flash_sale_end'];
        } else {
            $data['module_flash_sale_end'] = $this->config->get('module_flash_sale_end');
        }
        if (isset($this->request->post['module_flash_sale_status'])) {
            $data['module_flash_sale_status'] = $this->request->post['module_flash_sale_status'];
        } else {
            $data['module_flash_sale_status'] = $this->config->get('module_flash_sale_status');
        }

        $data['module_flash_sale_products'] = array();
        if (isset($this->request->post['flash_sale_products'])) {
            $flash_sale_products = $this->request->post['module_flash_sale_products'];
        } else {
            $flash_sale_products = $this->config->get('module_flash_sale_products');
        }
        if ($flash_sale_products && is_array($flash_sale_products)) {
            $this->load->model('catalog/product');
            foreach ($flash_sale_products as $flash_sale_product) {
                $product_info = $this->model_catalog_product->getProduct($flash_sale_product['product_id']);
                if (!$product_info) {
                    continue;
                }
                $product_name = $product_info && $product_info['name'] ? $product_info['name'] : '';
                $data['module_flash_sale_products'][] = array(
                    'product_id' => $flash_sale_product['product_id'],
                    'name' => $product_name,
                    'price' => $flash_sale_product['price'],
                    'count' => $flash_sale_product['count'],
                    'quantity' => $product_info['quantity'],
                    'minimum' => $product_info['minimum'],
                    'cart_count' => $flash_sale_product['cart_count']
                );
            }
        }

        $data['action'] = $this->url->link('extension/module/flash_sale', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/module/flash_sale', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/flash_sale')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    public function install()
    {
        $this->load->model('extension/module/flash_sale');
        $this->model_extension_module_flash_sale->install();
    }

    public function uninstall()
    {
        $this->load->model('extension/module/flash_sale');
        $this->model_extension_module_flash_sale->uninstall();
    }
}