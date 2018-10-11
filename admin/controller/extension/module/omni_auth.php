<?php

/**
 * OmniAuth
 *
 * @copyright  2017 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2017-07-18 10:19
 * @modified   2017-07-18 10:19
 */

class ControllerExtensionModuleOmniAuth extends Controller
{
    private $error = array();
    private $data = array();
    private $providers = array(
        'wechat' => '微信扫码', 'qq' => 'QQ', 'weibo' => '微博',
        'facebook' => 'Facebook', 'google' => 'Google Plus', 'twitter' => 'Twitter'
    );

    public function index()
    {
        $this->load->language('extension/module/omni_auth');
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (isset($this->request->post['module_omni_auth_items'])) {
                $sort_order = array();
                foreach ($this->request->post['module_omni_auth_items'] as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }
                array_multisort($sort_order, SORT_ASC, $this->request->post['module_omni_auth_items']);
            }

            $this->model_setting_setting->editSetting('module_omni_auth', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'));
        }


        $this->data['heading_title'] = strip_tags($this->language->get('heading_title'));

        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_copyright'] = sprintf($this->language->get('text_copyright'), date('Y'));

        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_debug'] = $this->language->get('entry_debug');

        $this->data['entry_provider'] = $this->language->get('entry_provider');
        $this->data['entry_key'] = $this->language->get('entry_key');
        $this->data['entry_secret'] = $this->language->get('entry_secret');
        $this->data['entry_scope'] = $this->language->get('entry_scope');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');
        $this->data['button_add_row'] = $this->language->get('button_add_row');
        $this->data['button_remove'] = $this->language->get('button_remove');

        // Process Errors
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        // Generate Breadcrumbs
        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'),
            'separator' => ' :: '
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/omni_auth', 'user_token=' . $this->session->data['user_token']),
            'separator' => ' :: '
        );

        // Set Page Title
        $this->document->setTitle($this->language->get('heading_title'));
        $this->data['providers'] = $this->providers;

        // Basic Variables
        $this->data['action'] = $this->url->link('extension/module/omni_auth', 'user_token=' . $this->session->data['user_token']);
        $this->data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        // Process Variables
        if (isset($this->request->post['module_omni_auth_items'])) {
            $this->data['module_omni_auth_items'] = $this->request->post['module_omni_auth_items'];
        } elseif ($this->config->get('module_omni_auth_items')) {
            $this->data['module_omni_auth_items'] = $this->config->get('module_omni_auth_items');
        } else {
            $this->data['module_omni_auth_items'] = array();
        }
        foreach ($this->data['module_omni_auth_items'] as $key => $item) {
            $this->data['module_omni_auth_items'][$key]['callback'] = base_url() . 'callback/' . $item['provider'];
        }

        if (isset($this->request->post['module_omni_auth_debug'])) {
            $this->data['module_omni_auth_debug'] = $this->request->post['module_omni_auth_debug'];
        } elseif ($this->config->get('module_omni_auth_debug')) {
            $this->data['module_omni_auth_debug'] = $this->config->get('module_omni_auth_debug');
        } else {
            $this->data['module_omni_auth_debug'] = 0;
        }

        if (isset($this->request->post['module_omni_auth_status'])) {
            $this->data['module_omni_auth_status'] = $this->request->post['module_omni_auth_status'];
        } elseif ($this->config->get('module_omni_auth_status')) {
            $this->data['module_omni_auth_status'] = $this->config->get('module_omni_auth_status');
        } else {
            $this->data['module_omni_auth_status'] = 0;
        }

        // Load Template
        $this->data['header'] = $this->load->controller('common/header');
        $this->data['column_left'] = $this->load->controller('common/column_left');
        $this->data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/omni_auth', $this->data));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/account')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        }
        return false;
    }

    public function install()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS`" . DB_PREFIX . "customer_authentication` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `uid` varchar(50) NOT NULL DEFAULT '',
                `unionid` varchar(50) NOT NULL DEFAULT '',
                `provider` varchar(20) NOT NULL DEFAULT '',
                `access_token` varchar(255) DEFAULT '',
                `token_secret` varchar(255) DEFAULT '',
                `avatar` varchar(255) DEFAULT '',
                `date_added` datetime DEFAULT NULL,
                `date_modified` datetime DEFAULT NULL,
                PRIMARY KEY (`id`,`customer_id`),
                UNIQUE KEY `id_UNIQUE` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function uninstall()
    {
        //$this->db->query('DROP TABLE ' . DB_PREFIX . 'customer_authentication;');
        //$this->load->model('setting/setting');
        //$this->model_setting_setting->deleteSetting('module_omni_auth');
    }
}
