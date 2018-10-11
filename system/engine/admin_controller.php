<?php

/**
 * admin_controller.php
 *
 * @copyright  2017 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2/22/17 19:20
 * @modified   2/22/17 19:20
 */
class AdminController extends Controller
{
    protected $route;
    protected $listFields = array();
    protected $filterFields = array();

    protected $error = array();
    protected $data = array();
    protected $filterData = array();

    protected $canEdit = true;
    protected $canDelete = true;
    protected $listTemplate = '';
    protected $formTemplate = '';
    private $defaultListTemplate = 'common/common_list';
    private $defaultFormTemplate = 'common/common_form';

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('common/gd_common');
        if (!$this->route) {
            $this->route = $this->getRoute();
        }
        $this->data['can_edit'] = $this->canEdit;
        $this->data['can_delete'] = $this->canDelete;
        $this->setTemplate();
    }

    protected function getRoute()
    {
        $route = array_get($this->request->get, 'route');
        if (!$route) {
            throw new \Exception('Empty route!');
        }
        $routes = explode('/', $route);
        if (count($routes) < 2) {
            throw new \Exception('Invalid route');
        }
        return $routes[0] . '/' . $routes[1];
    }

    protected function setTemplate()
    {
        if (!$this->listTemplate) {
            $listTpl = DIR_TEMPLATE . $this->getRoute() . '_list.tpl';
            if (file_exists($listTpl)) {
                $this->listTemplate = $this->getRoute() . '_list';
            } else {
                $this->listTemplate = $this->defaultListTemplate;
            }
        }
        if (!$this->formTemplate) {
            $formTpl = DIR_TEMPLATE . $this->getRoute() . '_form.tpl';
            if (file_exists($formTpl)) {
                $this->formTemplate = $this->getRoute() . '_form';
            } else {
                $this->formTemplate = $this->defaultFormTemplate;
            }
        }
    }

    public function index()
    {
        $this->load->language($this->route);
        $this->getList();
    }

    protected function getList()
    {
        $this->setTitle();
        $this->setFields();
        $this->setBreadCrumbs();
        $this->setCollectionData();
        $this->setLanguages();
        $this->setExtraData();
        $this->setSortLinks();
        $this->setPagination();
        $this->setLayouts();
        $this->response->setOutput($this->load->view($this->listTemplate, $this->data));
    }

    protected function setTitle()
    {
        $this->document->setTitle($this->getHeadingTitle());
    }

    protected function getHeadingTitle()
    {
        if ($this->language->get('heading_title') != 'heading_title') {
            return $this->language->get('heading_title');
        }
        return studly_case($this->getModel()->getTable());
    }

    protected function getModel()
    {
        $this->load->model($this->route);
        return $this->{$this->getModelName()};
    }

    protected function getModelName()
    {
        $modelName = str_replace('/', '_', $this->route);
        return 'model_' . $modelName;
    }

    protected function setFields()
    {
        $this->data['list_fields'] = $this->getListFields();
        $this->data['filter_fields'] = $this->getFilterFields();
        $this->data['all_fields'] = $this->getAllFields();
        $this->data['primary_name'] = $this->getPrimaryName();
    }

    protected function getListFields()
    {
        if ($this->listFields) {
            return $this->listFields;
        }
        return $this->getAllFields();
    }

    protected function getAllFields()
    {
        if ($this->allFields) {
            return $this->allFields;
        }
        return array_keys($this->getModel()->getAllFields());
    }

    protected function getFilterFields()
    {
        if ($this->filterFields) {
            return $this->filterFields;
        }
        return $this->getAllFields();
    }

    protected function getPrimaryName()
    {
        return $this->getModel()->getPrimaryName();
    }

    protected function setBreadCrumbs($type = 'list')
    {
        if ($type == 'list') {
            $url = $this->getFilterUrlParams();
        } else {
            $url = $this->getEditFilterParams();
        }
        $this->data['breadcrumbs'] = array();
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->getHeadingTitle(),
            'href' => $this->url->link($this->route, 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );
        $this->data['add'] = $this->url->link($this->route . '/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $this->data['delete'] = $this->url->link($this->route . '/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
    }

    protected function getFilterUrlParams()
    {
        $url = $this->getFilterBaseParams();
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        return $url;
    }

    protected function getFilterBaseParams()
    {
        $url = '';
        foreach ($this->getFilterFields() as $field) {
            $filterKey = 'filter_' . $field;
            if (isset($this->request->get[$filterKey])) {
                $url .= '&' . $filterKey . '=' . urlencode(html_entity_decode($this->request->get[$filterKey], ENT_QUOTES, 'UTF-8'));
            }
        }
        return $url;
    }

    protected function getEditFilterParams()
    {
        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        return $url;
    }

    protected function setCollectionData()
    {
        $this->data['collections'] = array();
        $filter_data = $this->getFilterData();
        $allData = $this->getModel()->like($filter_data['like']);
        $results = $this->getModel()->all($filter_data);
        foreach ($results as $key => $result) {
            $this->data['collections'][$key] = $result;
            $this->data['collections'][$key]['edit'] = $this->getEditUrl($result[$this->getPrimaryName()]);
        }
        $this->data['total'] = count($allData);
    }

    protected function getFilterData()
    {
        if ($this->filterData) {
            return $this->filterData;
        }

        $filter_data = array();
        $filter_data['like'] = array();
        foreach ($this->getFilterFields() as $field) {
            $filter_data['filter_' . $field] = array_get($this->request->get, 'filter_' . $field);
            $filter_data['like'][$field] = array_get($this->request->get, 'filter_' . $field);
        }
        $filter_data['sort'] = array_get($this->request->get, 'sort', $this->getPrimaryName());
        $filter_data['order'] = array_get($this->request->get, 'order', 'DESC');
        $filter_data['page'] = array_get($this->request->get, 'page', 1);
        $filter_data['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data['limit'] = $this->config->get('config_limit_admin');
        $this->filterData = $filter_data;
        $this->data = array_merge($this->data, $this->filterData);
        return $filter_data;
    }

    protected function getEditUrl($id)
    {
        $url = $this->getFilterUrlParams();
        $params = 'user_token=' . $this->session->data['user_token'] . '&' . $this->getPrimaryName() . '=' . $id . $url;
        return $this->url->link($this->route . '/edit', $params, 'SSL');
    }

    protected function setLanguages()
    {
        $this->data['heading_title'] = $this->getHeadingTitle();
        $this->data['text_list'] = $this->language->get('text_list');
        $this->data['text_no_results'] = $this->language->get('text_no_results');
        $this->data['text_confirm'] = $this->language->get('text_confirm');
        $this->data['text_missing'] = $this->language->get('text_missing');
        $this->data['text_loading'] = $this->language->get('text_loading');

        foreach (array_merge($this->getListFields(), $this->getFilterFields()) as $field) {
            $this->data['entry_' . $field] = $this->getDefaultLanguage('entry_', $field);
            $this->data['column_' . $field] = $this->getDefaultLanguage('column_', $field);
        }
        $this->data['column_action'] = $this->getDefaultLanguage('column_', 'action');
        $this->data['button_add'] = $this->language->get('button_add');
        $this->data['button_edit'] = $this->language->get('button_edit');
        $this->data['button_delete'] = $this->language->get('button_delete');
        $this->data['button_filter'] = $this->language->get('button_filter');
        $this->data['button_view'] = $this->language->get('button_view');
        $this->data['button_save'] = $this->language->get('button_save');
    }

    protected function getDefaultLanguage($prefix, $key)
    {
        return $this->language->getDefault($prefix . $key);
    }

    protected function setExtraData()
    {
        $this->data['user_token'] = $this->session->data['user_token'];
        $this->data['error_warning'] = array_get($this->error, 'warning', '');
        $this->data['selected'] = array_get($this->request->post, 'selected', array());

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }
    }

    protected function setSortLinks()
    {
        $url = $this->getSortUrlParams();
        foreach ($this->getListFields() as $field) {
            $this->data['sort_' . $field] = $this->url->link($this->route, 'user_token=' . $this->session->data['user_token'] . '&sort=' . $field . $url, 'SSL');
        }
    }

    protected function getSortUrlParams()
    {
        $url = $this->getFilterBaseParams();
        if ($this->filterData['order'] == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        return $url;
    }

    protected function setPagination()
    {
        $url = $this->getPaginationUrlParams();
        $pagination = new Pagination();
        $total = $this->data['total'];
        $page = $this->filterData['page'];
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link($this->route, 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', 'SSL');

        $this->data['pagination'] = $pagination->render();

        $this->data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($total - $this->config->get('config_limit_admin'))) ? $total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $total, ceil($total / $this->config->get('config_limit_admin')));
    }

    protected function getPaginationUrlParams()
    {
        $url = $this->getFilterBaseParams();
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        return $url;
    }

    protected function setLayouts()
    {
        $this->data['store'] = HTTPS_CATALOG;
        $this->data['header'] = $this->load->controller('common/header');
        $this->data['column_left'] = $this->load->controller('common/column_left');
        $this->data['footer'] = $this->load->controller('common/footer');
    }

    public function add()
    {
        $this->load->language($this->route);
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->getModel()->create($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $url = $this->getEditFilterParams();
            $this->response->redirect($this->url->link($this->route, 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        $this->getForm();
    }

    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', $this->route)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    protected function getForm()
    {
        $this->setTitle();
        $this->setFields();
        $this->setFormLanguages();
        $this->setErrors();
        $this->setBreadCrumbs('edit');
        $this->setExtraData();
        $this->setEditLinks();
        $this->setFormData();
        $this->setLayouts();
        $this->response->setOutput($this->load->view($this->formTemplate, $this->data));
    }

    protected function setFormLanguages()
    {
        $this->data['heading_title'] = $this->getHeadingTitle();
        if (array_get($this->request->get, $this->getPrimaryName())) {
            $textForm = $this->language->get('text_edit');
        } else {
            $textForm = $this->language->get('text_add');
        }
        $this->data['text_select'] = $this->language->get('text_select');
        $this->data['text_none'] = $this->language->get('text_none');
        $this->data['text_form'] = $textForm;
        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_yes'] = $this->language->get('text_yes');
        $this->data['text_no'] = $this->language->get('text_no');

        foreach ($this->getAllFields() as $field) {
            $this->data['entry_' . $field] = $this->language->get('entry_' . $field);
        }
        foreach ($this->getAllFields() as $field) {
            $this->data['entry_' . $field] = $this->getDefaultLanguage('entry_', $field);
        }

        $this->data['help_address_format'] = $this->language->get('help_address_format');
        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');
    }

    protected function setErrors()
    {
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        foreach ($this->getAllFields() as $field) {
            if (isset($this->error[$field])) {
                $this->data['error_' . $field] = $this->error[$field];
            } else {
                $this->data['error_' . $field] = '';
            }
        }
    }

    protected function setEditLinks()
    {
        $url = $this->getEditFilterParams();
        if (!isset($this->request->get[$this->getPrimaryName()])) {
            $this->data['action'] = $this->url->link($this->route . '/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $this->data['action'] = $this->url->link($this->route . '/edit', 'user_token=' . $this->session->data['user_token'] . '&' . $this->getPrimaryName() . '=' . $this->request->get[$this->getPrimaryName()] . $url, true);
        }

        $this->data['cancel'] = $this->url->link($this->route, 'user_token=' . $this->session->data['user_token'] . $url, true);
    }

    protected function setFormData()
    {
        $allfieldTypes = $this->getModel()->getFields();
        $allFields = $this->getAllFields();
        if (isset($this->request->get[$this->getPrimaryName()]) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $singleData = $this->getModel()->find($this->request->get[$this->getPrimaryName()]);
        }
        foreach ($allFields as $field) {
            if (isset($this->request->post[$field])) {
                $this->data[$field] = $this->request->post[$field];
            } elseif (!empty($singleData)) {
                $this->data[$field] = $singleData[$field];
            } else {
                $this->data[$field] = '';
            }
        }
        $this->data['all_field_types'] = $allfieldTypes;
    }

    public function edit()
    {
        $this->load->language($this->route);
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->getModel()->update($this->request->get[$this->getPrimaryName()], $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $url = $this->getEditFilterParams();
            $this->response->redirect($this->url->link($this->route, 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        $this->getForm();
    }

    public function delete()
    {
        $this->load->language($this->route);
        if (!$this->canDelete) {
            return $this->getList();
        }
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $itemId) {
                $this->getModel()->delete($itemId);
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $url = $this->getEditFilterParams();
            $this->response->redirect($this->url->link($this->route, 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        $this->getList();
    }

    protected function validateDelete()
    {
        if (!$this->user->hasPermission('modify', $this->route)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
