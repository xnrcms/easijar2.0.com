<?php

/**
 * flex.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author StifferChen <chenlin@opencart.cn>
 * @created 2018-06-04 14:25
 * @modified 2018-06-04 14:25
 */
class ControllerExtensionShippingFlex extends Controller
{
    private $error = array();

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/shipping/flex');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('localisation/express');
        $this->load->model('localisation/geo_zone');
        $this->load->model('localisation/tax_class');
    }

    public function index()
    {

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('shipping_flex', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping'));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['cost'])) {
            $data['error_cost'] = $this->error['cost'];
        } else {
            $data['error_cost'] = '';
        }

        if (isset($this->error['display'])) {
            $data['error_display'] = $this->error['display'];
        } else {
            $data['error_display'] = array();
        }

        if (isset($this->error['rate'])) {
            $data['error_rate'] = $this->error['rate'];
        } else {
            $data['error_rate'] = array();
        }

        if (isset($this->error['express'])) {
            $data['error_express'] = $this->error['express'];
        } else {
            $data['error_express'] = array();
        }

        $data['text_initial_weight_placeholder'] = $this->weight->getTitle($this->config->get('config_weight_class_id'));
        $data['text_initial_fee_format'] = $this->currency->getTitle($this->config->get('config_currency'));

        $breads = new Breadcrumb();
        $breads->add(t('text_home'), $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
        $breads->add(t('text_extension'), $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping'));
        $breads->add(t('heading_title'), $this->url->link('extension/shipping/flat', 'user_token=' . $this->session->data['user_token']));
        $data['breadcrumbs'] = $breads->all();

        if (isset($this->request->post['shipping_flex_cost'])) {
            $data['shipping_flex_cost'] = $this->request->post['shipping_flex_cost'];
        } else {
            $data['shipping_flex_cost'] = $this->config->get('shipping_flex_cost');
        }

        if (isset($this->request->post['shipping_flex_free'])) {
            $data['shipping_flex_free'] = $this->request->post['shipping_flex_free'];
        } else {
            $data['shipping_flex_free'] = $this->config->get('shipping_flex_free');
        }

        if (isset($this->request->post['shipping_flex_tax_class_id'])) {
            $data['shipping_flex_tax_class_id'] = $this->request->post['shipping_flex_tax_class_id'];
        } else {
            $data['shipping_flex_tax_class_id'] = $this->config->get('shipping_flex_tax_class_id');
        }

        if (isset($this->request->post['shipping_flex_sort_order'])) {
            $data['shipping_flex_sort_order'] = $this->request->post['shipping_flex_sort_order'];
        } else {
            $data['shipping_flex_sort_order'] = $this->config->get('shipping_flex_sort_order');
        }

        if (isset($this->request->post['shipping_flex_status'])) {
            $data['shipping_flex_status'] = $this->request->post['shipping_flex_status'];
        } else {
            $data['shipping_flex_status'] = $this->config->get('shipping_flex_status');
        }

        if (isset($this->request->post['shipping_flex_info'])) {
            $data['shipping_flex_info']    = $this->request->post['shipping_flex_info'];
        } else {
            $data['shipping_flex_info']    = $this->config->get('shipping_flex_info');
        }

        $data['shipping_count'] = count($data['shipping_flex_info']);
        $data['geo_zone_list_link'] = $this->url->link('localisation/geo_zone', 'user_token=' . $this->session->data['user_token'], true);
        $data['add_geo_zone_link'] = $this->url->link('localisation/geo_zone/add', 'user_token=' . $this->session->data['user_token'], true);

        $data['express_list_link'] = $this->url->link('localisation/express', 'user_token=' . $this->session->data['user_token'], true);
        $data['add_express_link'] = $this->url->link('localisation/express/add', 'user_token=' . $this->session->data['user_token'], true);

        $expresses = $this->model_localisation_express->getExpresses();
        $data['expresses'] = array();
        if ($expresses) {
            foreach ($expresses as $express) {
                if ($express['status']) {
                    $data['expresses'][] = $express;
                }
            }
        }

        $data['express'] = $this->url->link('localisation/express', 'user_token=' . $this->session->data['user_token'], true);
        $data['action'] = $this->url->link('extension/shipping/flex', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/flex', $data));
    }

    public function install()
    {
        $this->model_localisation_express->install();
    }

    public function uninstall()
    {
        $this->model_localisation_express->uninstall();
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/shipping/flex')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $total_preg = '/^([1-9]+\.?\d*|0\.\d*[1-9]+|[1-9]+\.\d*)$/i';
        if(!preg_match($total_preg, $this->request->post['shipping_flex_cost'])){
            $this->error['cost'] = $this->language->get('error_cost');
        }

        if(!isset($this->request->post['shipping_flex_info'])){
            $this->error['warning'] = $this->language->get('error_empty_shipping');
        }else{
            $preg_str = '/^(0|0\.\d*|[1-9]+\d*\.?\d*):(0|0\.\d*|[1-9]+\d*\.?\d*)\r$/im';
            foreach($this->request->post['shipping_flex_info'] as $key => $value){
                if ((utf8_strlen($value['display']) < 3) || (utf8_strlen($value['display']) > 255)) {
                    $this->error['display'][$key] = $this->language->get('error_display');
                    $this->error['warning'] = $this->language->get('error_display');
                }

                if(!isset($value['express_id']) || !$value['express_id']){
                    $this->error['express'][$key] = $this->language->get('error_express');
                    $this->error['warning'] = $this->language->get('error_express');
                }
            }
        }

        return !$this->error;
    }
}