<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-02-02 10:13:10
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-04-09 12:05:25
 */

abstract class GDModuleBaseController extends Controller {
    protected $module_id = 0;
    protected $module_code = '';
    protected $for_layout = false;
    protected $error = array();

    public function index()
    {
        if (empty($this->module_code)) {
            throw new Exception('module_code is required.');
        }

        $this->module_id = (int)array_get($this->request->get, 'module_id');
        $this->load->language("extension/module/{$this->module_code}");
        $this->load->model('setting/module');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if ($this->for_layout) {
                if (!$this->module_id) {
                    $this->model_setting_module->addModule($this->module_code, $this->request->post);
                } else {
                    $this->model_setting_module->editModule($this->module_id, $this->request->post);
                }
            } else {
                $this->model_setting_setting->editSetting('module_' . $this->module_code, $this->request->post);
            }

            $this->session->data['success'] = t('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', '&type=module'));
        }

        $this->document->setTitle(t('heading_title'));
        $data['error'] = $this->error;
        $data['breadcrumbs'] = $this->getBreadcrumbs();

        if (!$this->module_id) {
            $data['action'] = $this->url->link("extension/module/{$this->module_code}");
        } else {
            $data['action'] = $this->url->link("extension/module/{$this->module_code}", "module_id={$this->module_id}");
        }

        $data['cancel'] = $this->url->link('marketplace/extension', '&type=module');

        if ($this->module_id && $this->request->server['REQUEST_METHOD'] != 'POST') {
            $data['module_info'] = $this->model_setting_module->getModule($this->module_id);
        }

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data = $this->overwriteDataForView($data);

        $this->response->setOutput($this->load->view("extension/module/{$this->module_code}", $data));
    }

    protected function validate()
    {
        if (!$this->permission('modify')) {
            $this->error['warning'] = t('error_permission');
        }

        $this->validate_form();

        return !$this->error;
    }

    protected function permission($type)
    {
        return $this->user->hasPermission($type, "extension/module/{$this->module_code}");
    }

    protected function validate_form()
    {
        return true;
    }

    protected function getBreadcrumbs()
    {
        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_extension'), $this->url->link('marketplace/extension', 'type=module'));
        if ($this->module_id) {
            $breadcrumbs->add(t('heading_title'), $this->url->link("extension/module/{$this->module_code}", "module_id={$this->module_id}"));
        } else {
            $breadcrumbs->add(t('heading_title'), $this->url->link("extension/module/{$this->module_code}"));
        }
        return $breadcrumbs->all();
    }

    protected function overwriteDataForView($data)
    {
        return $data;
    }
}
