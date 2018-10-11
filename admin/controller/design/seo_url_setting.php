<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-07-11 15:33:02
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-07-11 16:41:33
 */

class ControllerDesignSeoUrlSetting extends Controller
{
    private $error = [];

    public function index()
    {
        $this->load->language('design/seo_url_setting');
        $this->document->setTitle(t('heading_title'));

        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('seo_url_setting', $this->request->post);
            $this->session->data['success'] = t('text_success');
            $this->response->redirect($this->url->link('design/seo_url_setting'));
        }

        $data['error'] = $this->error;
        if (isset($this->session->data['success'])) {
            $data['text_success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['text_success'] = '';
        }

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('heading_title'), $this->url->link('design/seo_url_setting'));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $data['action'] = $this->url->link('design/seo_url_setting');

        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $data['setting'] = $this->model_setting_setting->getSetting('seo_url_setting');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('design/seo_url_setting', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'design/seo_url_setting')) {
            $this->error['warning'] = t('error_permission');
        }

        return !$this->error;
    }
}
