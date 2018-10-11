<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-23 11:12:00
 * @modified         2016-11-23 15:11:10
 */

class ControllerExtensionPaymentUnionpay extends Controller
{
    private $error = array();

    public function index()
    {
        // 加载语言数据
        $this->load->language('extension/payment/unionpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        // 处理提交的表单
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            //$this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('payment_unionpay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment'));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['secrity_code'])) {
            $data['error_secrity_code'] = $this->error['secrity_code'];
        } else {
            $data['error_secrity_code'] = '';
        }

        if (isset($this->error['currency_code'])) {
            $data['error_currency_code'] = $this->error['currency_code'];
        } else {
            $data['error_currency_code'] = '';
        }

        if (isset($this->error['partner'])) {
            $data['error_partner'] = $this->error['partner'];
        } else {
            $data['error_partner'] = '';
        }

        if (isset($this->error['cert_pwd'])) {
            $data['error_cert_pwd'] = $this->error['cert_pwd'];
        } else {
            $data['error_cert_pwd'] = '';
        }

        if (isset($this->error['pfx_name'])) {
            $data['error_pfx_name'] = $this->error['pfx_name'];
        } else {
            $data['error_pfx_name'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token='.$this->session->data['user_token'])
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/unionpay', 'user_token='.$this->session->data['user_token'])
        );

        $data['action'] = $this->url->link('extension/payment/unionpay', 'user_token='.$this->session->data['user_token']);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment');

        // 设置表单的值
        if (isset($this->request->post['payment_unionpay_total'])) {
            $data['payment_unionpay_total'] = $this->request->post['payment_unionpay_total'];
        } else {
            $data['payment_unionpay_total'] = $this->config->get('payment_unionpay_total');
        }

        if (isset($this->request->post['payment_unionpay_partner'])) {
            $data['payment_unionpay_partner'] = $this->request->post['payment_unionpay_partner'];
        } else {
            $data['payment_unionpay_partner'] = $this->config->get('payment_unionpay_partner');
        }

        if (isset($this->request->post['payment_unionpay_cert_pwd'])) {
            $data['payment_unionpay_cert_pwd'] = $this->request->post['payment_unionpay_cert_pwd'];
        } else {
            $data['payment_unionpay_cert_pwd'] = $this->config->get('payment_unionpay_cert_pwd');
        }

        if (isset($this->request->post['payment_unionpay_pfx_name'])) {
            $data['payment_unionpay_pfx_name'] = $this->request->post['payment_unionpay_pfx_name'];
        } else {
            $data['payment_unionpay_pfx_name'] = $this->config->get('payment_unionpay_pfx_name');
        }

        if (isset($this->request->post['payment_unionpay_currency_code'])) {
            $data['payment_unionpay_currency_code'] = $this->request->post['payment_unionpay_currency_code'];
        } else {
            $data['payment_unionpay_currency_code'] = $this->config->get('payment_unionpay_currency_code');
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/currency');
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        // 保留
        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_unionpay_status'])) {
            $data['payment_unionpay_status'] = $this->request->post['payment_unionpay_status'];
        } else {
            $data['payment_unionpay_status'] = $this->config->get('payment_unionpay_status');
        }

        if (isset($this->request->post['payment_unionpay_trade_finished'])) {
            $data['payment_unionpay_trade_finished'] = $this->request->post['payment_unionpay_trade_finished'];
        } else {
            $data['payment_unionpay_trade_finished'] = $this->config->get('payment_unionpay_trade_finished');
        }

        if (isset($this->request->post['payment_unionpay_sort_order'])) {
            $data['payment_unionpay_sort_order'] = $this->request->post['payment_unionpay_sort_order'];
        } else {
            $data['payment_unionpay_sort_order'] = $this->config->get('payment_unionpay_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/unionpay', $data));
    }

    // 验证
    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/unionpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_unionpay_partner']) {
            $this->error['partner'] = $this->language->get('error_partner');
        }

        if (!$this->request->post['payment_unionpay_cert_pwd']) {
            $this->error['cert_pwd'] = $this->language->get('error_cert_pwd');
        }

        if (!$this->request->post['payment_unionpay_pfx_name']) {
            $this->error['pfx_name'] = $this->language->get('error_pfx_name');
        }

        if (!$this->request->post['payment_unionpay_currency_code']) {
            $this->error['currency_code'] = $this->language->get('error_currency_code');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}
