<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-11 13:00:00
 * @modified         2016-11-11 13:00:00
 */

class ControllerExtensionModuleSms extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/sms');

        $this->document->settitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('module_sms', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module'));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['module_sms_sign'])) {
            $data['error_sign'] = $this->error['module_sms_sign'];
        } else {
            $data['error_sign'] = array();
        }

        if (isset($this->error['module_sms_yunpian_apikey'])) {
            $data['error_yunpian_apikey'] = $this->error['module_sms_yunpian_apikey'];
        } else {
            $data['error_yunpian_apikey'] = '';
        }

        if (isset($this->error['module_sms_ihuyi_account'])) {
            $data['error_ihuyi_account'] = $this->error['module_sms_ihuyi_account'];
        } else {
            $data['error_ihuyi_account'] = '';
        }

        if (isset($this->error['module_sms_ihuyi_password'])) {
            $data['error_ihuyi_password'] = $this->error['module_sms_ihuyi_password'];
        } else {
            $data['error_ihuyi_password'] = '';
        }

        if (isset($this->error['module_sms_c123_cgid'])) {
            $data['error_c123_cgid'] = $this->error['module_sms_c123_cgid'];
        } else {
            $data['error_c123_cgid'] = '';
        }
        if (isset($this->error['module_sms_c123_authkey'])) {
            $data['error_c123_authkey'] = $this->error['module_sms_c123_authkey'];
        } else {
            $data['error_c123_authkey'] = '';
        }
        if (isset($this->error['module_sms_c123_ac'])) {
            $data['error_c123_ac'] = $this->error['module_sms_c123_ac'];
        } else {
            $data['error_c123_ac'] = '';
        }

        if (isset($this->error['module_sms_plant'])) {
            $data['error_plant'] = $this->error['module_sms_plant'];
        } else {
            $data['error_plant'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token']),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module'),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/sms', 'user_token='.$this->session->data['user_token']),
        );

        $data['action'] = $this->url->link('extension/module/sms', 'user_token='.$this->session->data['user_token']);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module');

        $data['modules'] = array();

        if (isset($this->request->post['module_sms_yunpian_apikey'])) {
            $data['module_sms_yunpian_apikey'] = $this->request->post['module_sms_yunpian_apikey'];
        } else {
            $data['module_sms_yunpian_apikey'] = $this->config->get('module_sms_yunpian_apikey');
        }

        if (isset($this->request->post['module_sms_ihuyi_account'])) {
            $data['module_sms_ihuyi_account'] = $this->request->post['module_sms_ihuyi_account'];
        } else {
            $data['module_sms_ihuyi_account'] = $this->config->get('module_sms_ihuyi_account');
        }
        if (isset($this->request->post['module_sms_ihuyi_password'])) {
            $data['module_sms_ihuyi_password'] = $this->request->post['module_sms_ihuyi_password'];
        } else {
            $data['module_sms_ihuyi_password'] = $this->config->get('module_sms_ihuyi_password');
        }

        if (isset($this->request->post['module_sms_c123_cgid'])) {
            $data['module_sms_c123_cgid'] = $this->request->post['module_sms_c123_cgid'];
        } else {
            $data['module_sms_c123_cgid'] = $this->config->get('module_sms_c123_cgid');
        }
        if (isset($this->request->post['module_sms_c123_csid'])) {
            $data['module_sms_c123_csid'] = $this->request->post['module_sms_c123_csid'];
        } else {
            $data['module_sms_c123_csid'] = $this->config->get('module_sms_c123_csid');
        }
        if (isset($this->request->post['module_sms_c123_authkey'])) {
            $data['module_sms_c123_authkey'] = $this->request->post['module_sms_c123_authkey'];
        } else {
            $data['module_sms_c123_authkey'] = $this->config->get('module_sms_c123_authkey');
        }

        if (isset($this->request->post['module_sms_c123_ac'])) {
            $data['module_sms_c123_ac'] = $this->request->post['module_sms_c123_ac'];
        } else {
            $data['module_sms_c123_ac'] = $this->config->get('module_sms_c123_ac');
        }

        if (isset($this->request->post['module_sms_sign'])) {
            $data['module_sms_sign'] = $this->request->post['module_sms_sign'];
        } else {
            $data['module_sms_sign'] = $this->config->get('module_sms_sign');
        }

        if (isset($this->request->post['module_sms_plant'])) {
            $data['module_sms_plant'] = $this->request->post['module_sms_plant'];
        } else {
            $data['module_sms_plant'] = $this->config->get('module_sms_plant');
        }

        if (isset($this->request->post['module_sms_status'])) {
            $data['module_sms_status'] = $this->request->post['module_sms_status'];
        } else {
            $data['module_sms_status'] = $this->config->get('module_sms_status');
        }

        if (isset($this->request->post['module_sms_customer_approve_message'])) {
            $data['module_sms_customer_approve_message'] = $this->request->post['module_sms_customer_approve_message'];
        } else {
            $data['module_sms_customer_approve_message'] = $this->config->get('module_sms_customer_approve_message');
        }

        if (isset($this->request->post['module_sms_customer_register_verify_message'])) {
            $data['module_sms_customer_register_verify_message'] = $this->request->post['module_sms_customer_register_verify_message'];
        } else {
            $data['module_sms_customer_register_verify_message'] = $this->config->get('module_sms_customer_register_verify_message');
        }

        if (isset($this->request->post['module_sms_customer_add_transaction_message'])) {
            $data['module_sms_customer_add_transaction_message'] = $this->request->post['module_sms_customer_add_transaction_message'];
        } else {
            $data['module_sms_customer_add_transaction_message'] = $this->config->get('module_sms_customer_add_transaction_message');
        }

        if (isset($this->request->post['module_sms_customer_add_reward_message'])) {
            $data['module_sms_customer_add_reward_message'] = $this->request->post['module_sms_customer_add_reward_message'];
        } else {
            $data['module_sms_customer_add_reward_message'] = $this->config->get('module_sms_customer_add_reward_message');
        }

        if (isset($this->request->post['module_sms_return_update_message'])) {
            $data['module_sms_return_update_message'] = $this->request->post['module_sms_return_update_message'];
        } else {
            $data['module_sms_return_update_message'] = $this->config->get('module_sms_return_update_message');
        }

        if (isset($this->request->post['module_sms_customer_register_login_message'])) {
            $data['module_sms_customer_register_login_message'] = $this->request->post['module_sms_customer_register_login_message'];
        } else {
            $data['module_sms_customer_register_login_message'] = $this->config->get('module_sms_customer_register_login_message');
        }

        if (isset($this->request->post['module_sms_customer_register_approval_message'])) {
            $data['module_sms_customer_register_approval_message'] = $this->request->post['module_sms_customer_register_approval_message'];
        } else {
            $data['module_sms_customer_register_approval_message'] = $this->config->get('module_sms_customer_register_approval_message');
        }

        if (isset($this->request->post['module_sms_order_effect_message'])) {
            $data['module_sms_order_effect_message'] = $this->request->post['module_sms_order_effect_message'];
        } else {
            $data['module_sms_order_effect_message'] = $this->config->get('module_sms_order_effect_message');
        }

        if (isset($this->request->post['module_sms_order_update_message'])) {
            $data['module_sms_order_update_message'] = $this->request->post['module_sms_order_update_message'];
        } else {
            $data['module_sms_order_update_message'] = $this->config->get('module_sms_order_update_message');
        }

        if (isset($this->request->post['module_sms_find_back_password'])) {
            $data['module_sms_find_back_password'] = $this->request->post['module_sms_find_back_password'];
        } else {
            $data['module_sms_find_back_password'] = $this->config->get('module_sms_find_back_password');
        }

        if (isset($this->request->post['module_sms_order_paid_notify_admin_message'])) {
            $data['module_sms_order_paid_notify_admin_message'] = $this->request->post['module_sms_order_paid_notify_admin_message'];
        } else {
            $data['module_sms_order_paid_notify_admin_message'] = $this->config->get('module_sms_order_paid_notify_admin_message');
        }

        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/sms', $data));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/sms')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['module_sms_plant']) {
            $this->error['module_sms_plant'] = $this->language->get('error_plant');
        }

        if (($this->request->post['module_sms_plant'] == 'c123' || $this->request->post['module_sms_plant'] == 'yunpian') && !$this->request->post['module_sms_sign']) {
            foreach ($this->request->post['module_sms_sign'] as $language_id => $value) {
                if ((utf8_strlen($value) < 1) || (utf8_strlen($value) > 128)) {
                    $this->error['module_sms_sign'][$language_id] = $this->language->get('error_sign');
                }
            }
        }

        if ($this->request->post['module_sms_plant'] == 'c123' && !$this->request->post['module_sms_c123_ac']) {
            $this->error['module_sms_c123_ac'] = $this->language->get('error_c123_ac');
        }

        if ($this->request->post['module_sms_plant'] == 'c123' && !$this->request->post['module_sms_c123_authkey']) {
            $this->error['module_sms_c123_authkey'] = $this->language->get('error_c123_authkey');
        }

        if ($this->request->post['module_sms_plant'] == 'c123' && !$this->request->post['module_sms_c123_cgid']) {
            $this->error['module_sms_c123_cgid'] = $this->language->get('error_c123_cgid');
        }

        if ($this->request->post['module_sms_plant'] == 'yunpian' && !$this->request->post['module_sms_yunpian_apikey']) {
            $this->error['module_sms_yunpian_apikey'] = $this->language->get('error_yunpian_apikey');
        }

        if ($this->request->post['module_sms_plant'] == 'ihuyi' && !$this->request->post['module_sms_ihuyi_account']) {
            $this->error['module_sms_ihuyi_account'] = $this->language->get('error_ihuyi_account');
        }

        if ($this->request->post['module_sms_plant'] == 'ihuyi' && !$this->request->post['module_sms_ihuyi_password']) {
            $this->error['module_sms_ihuyi_password'] = $this->language->get('error_ihuyi_password');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    public function install()
    {
        $this->load->language('extension/module/sms');
        $this->session->data['success'] = $this->language->get('text_success_install');
    }
}
