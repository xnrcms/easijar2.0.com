<?php

/**
 * express.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author StifferChen <chenlin@opencart.cn>
 * @created 2018-06-04 15:12
 * @modified 2018-06-04 15:12
 */
class ControllerLocalisationExpress extends Controller
{
    private $error = array();
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('localisation/express');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('localisation/express');
        $this->load->model('localisation/language');
    }

    public function index()
    {
        $this->getList();
    }

    public function add()
    {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_localisation_express->addExpress($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('localisation/express', 'user_token=' . $this->session->data['user_token'] . $url));
        }
        $this->getForm();
    }

    public function edit()
    {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_localisation_express->editExpress($this->request->get['express_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('localisation/express', 'user_token=' . $this->session->data['user_token'] . $url));
        }
        $this->getForm();
    }

    public function delete() {

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $express_id) {
                $this->model_localisation_express->deleteExpress($express_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('localisation/express', 'user_token=' . $this->session->data['user_token'] . $url));
        }

        $this->getList();
    }

    protected function getForm()
    {
        $data['text_form'] = !isset($this->request->get['express_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['title'])) {
            $data['error_title'] = $this->error['title'];
        } else {
            $data['error_title'] = array();
        }

        $url = '';
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        if (!isset($this->request->get['express_id'])) {
            $data['action'] = $this->url->link('localisation/express/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('localisation/express/edit', 'user_token=' . $this->session->data['user_token'] . '&express_id=' . $this->request->get['express_id'] . $url, true);
        }
        $data['cancel'] = $this->url->link('localisation/express', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $breads = new Breadcrumb();
        $breads->add(t('text_home'), $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
        $breads->add(t('text_extension'), $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping'));
        $breads->add(t('text_extension_flat'), $this->url->link('extension/shipping/flex', 'user_token=' . $this->session->data['user_token']));
        $breads->add(t('heading_title'), $this->url->link('localisation/express', 'user_token=' . $this->session->data['user_token'] . $url, true));
        $data['breadcrumbs'] = $breads->all();

        $data['languages'] = $this->model_localisation_language->getLanguages();

        if (isset($this->request->post['express'])) {
            $data['express'] = $this->request->post['express'];
        } elseif (isset($this->request->get['express_id'])) {
            $data['express'] = $this->model_localisation_express->getExpressExpress($this->request->get['express_id']);
        } else {
            $data['express'] = array();
        }

        if (isset($this->request->get['express_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $express_info = $this->model_localisation_express->getExpress($this->request->get['express_id']);
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($express_info)) {
            $data['status'] = $express_info['status'];
        } else {
            $data['status'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('localisation/express_form', $data));
    }

    protected function getList()
    {
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $filter_data = array(
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $express_total = $this->model_localisation_express->getTotalExpresses();
        $results = $this->model_localisation_express->getExpresses($filter_data);
        $data['expresses'] = array();
        if ($results) {
            foreach ($results as $result) {
                $data['expresses'][] = array(
                    'express_id' => $result['express_id'],
                    'title' => $result['title'],
                    'status' => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                    'edit' => $this->url->link('localisation/express/edit', 'user_token=' . $this->session->data['user_token'] . '&express_id=' . $result['express_id'] . $url)
                );
            }
        }

        $pagination = new Pagination();
        $pagination->total = $express_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('localisation/express', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($express_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($express_total - $this->config->get('config_limit_admin'))) ? $express_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $express_total, ceil($express_total / $this->config->get('config_limit_admin')));

        $breads = new Breadcrumb();
        $breads->add(t('text_home'), $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
        $breads->add(t('text_extension'), $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping'));
        $breads->add(t('text_extension_flat'), $this->url->link('extension/shipping/flex', 'user_token=' . $this->session->data['user_token']));
        $breads->add(t('heading_title'), $this->url->link('localisation/express', 'user_token=' . $this->session->data['user_token']));
        $data['breadcrumbs'] = $breads->all();

        $data['add'] = $this->url->link('localisation/express/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('localisation/express/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('localisation/express_list', $data));
    }

    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'localisation/express')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->request->post['express'] as $language_id => $value) {
            if ((utf8_strlen($value['title']) < 1) || (utf8_strlen($value['title']) > 128)) {
                $this->error['title'][$language_id] = $this->language->get('error_title');
            }
        }
        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'localisation/express')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}