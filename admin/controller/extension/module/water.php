<?php

/**
 * water.php
 *
 * @copyright 2017 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author StifferChen <chenlin@opencart.cn>
 * @created 2017-12-29 09:55
 * @modified 2017-12-29 09:55
 */
class ControllerExtensionModuleWater extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/water');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_water', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $this->document->addScript('view/javascript/spectrum/spectrum.js');
        $this->document->addStyle('view/javascript/spectrum/spectrum.css');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/water', 'user_token=' . $this->session->data['user_token'], true)

        );

        if (isset($this->request->post['module_water_type'])) {
            $data['module_water_type'] = $this->request->post['module_water_type'];
        } else {
            $data['module_water_type'] = $this->config->get('module_water_type');
        }
        if (isset($this->request->post['module_water_position'])) {
            $data['module_water_position'] = $this->request->post['module_water_position'];
        } else {
            $data['module_water_position'] = $this->config->get('module_water_position');
        }
        if (isset($this->request->post['module_water_alpha'])) {
            $data['module_water_alpha'] = $this->request->post['module_water_alpha'];
        } else {
            $data['module_water_alpha'] = (int)$this->config->get('module_water_alpha');
        }
        if (isset($this->request->post['module_water_font_color'])) {
            $data['module_water_font_color'] = $this->request->post['module_water_font_color'];
        } else {
            $data['module_water_font_color'] = $this->config->get('module_water_font_color');
        }
        if (isset($this->request->post['module_water_font'])) {
            $data['module_water_font'] = $this->request->post['module_water_font'];
        } else {
            $data['module_water_font'] = $this->config->get('module_water_font');
        }
        if (isset($this->request->post['module_water_status'])) {
            $data['module_water_status'] = $this->request->post['module_water_status'];
        } else {
            $data['module_water_status'] = $this->config->get('module_water_status');
        }

        $this->load->model('tool/image');
        if (isset($this->request->post['module_water_image']) && is_file(DIR_IMAGE . $this->request->post['module_water_image'])) {
            $data['module_water_image'] = $this->request->post['module_water_image'];
            $data['src_water_image'] = $this->model_tool_image->resize($this->request->post['module_water_image'], 100, 100);
        } else {
            $data['module_water_image'] = $this->config->get('module_water_image');
            if(is_file(DIR_IMAGE . $this->config->get('module_water_image'))){
                $data['src_water_image'] = $this->model_tool_image->resize($this->config->get('module_water_image'), 100, 100);
            }else{
                $data['src_water_image'] = $this->model_tool_image->resize('no_image.png', 100, 100);
            }
        }
        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';

        }

        $data['action'] = $this->url->link('extension/module/water', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/water', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/water')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}