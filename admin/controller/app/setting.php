<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-05-02 11:12:55
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-05-02 16:16:17
 */

class ControllerAppSetting extends Controller
{
    private $error;
    private $availableModuleTypes = ['slideshow', 'featured', 'image_combo', 'banner', 'icon', 'latest', 'special', 'blog_latest'];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('app/setting');
    }

    public function index()
    {
        $this->document->setTitle(t('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('setting/extension');
        $this->load->model('setting/module');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('app', $this->request->post);
            $this->session->data['success'] = t('text_success');
            $this->response->redirect($this->url->link('app/setting'));
        }

        $data['error'] = $this->error;
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('heading_title'), $this->url->link('app/setting'));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $data['action'] = $this->url->link('app/setting');

        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $data['setting'] = $this->model_setting_setting->getSetting('app');

            if ($modules = array_get($data, 'setting.app_home_modules')) {
                foreach ($modules as $code) {
                    $segments = explode('.', $code);

                    if (count($segments) == 1 && config("module_{$segments[0]}_status")) {
                        $this->load->language('extension/module/' . $code, 'extension');
                        $data['modules'][] = array(
                            'code' => $code,
                            'type' => strip_tags(t('extension')->get('heading_title')),
                            'name' => strip_tags(t('extension')->get('heading_title')),
                            'href' => $this->url->link("extension/module/{$segments[0]}"),
                        );
                    }

                    if ($moduleId = (int)array_get($segments, 1)) {
                        if ($moduleInfo = $this->model_setting_module->getModule($moduleId)) {
                            if ((bool)array_get($moduleInfo, 'status')) {
                                $this->load->language('extension/module/' . $segments[0], 'extension');
                                $data['modules'][] = array(
                                    'code' => $code,
                                    'type' => strip_tags(t('extension')->get('heading_title')),
                                    'name' => $moduleInfo['name'],
                                    'href' => $this->url->link("extension/module/{$segments[0]}", "module_id={$moduleId}"),
                                );
                            }
                        }
                    }
                }
            }
        }

        $data['extensions'] = [];
        $extensions = $this->model_setting_extension->getInstalled('module');
        if ($extensions) {
            $extensions = array_intersect($extensions, $this->availableModuleTypes);

            foreach ($extensions as $code) {
                $this->load->language('extension/module/' . $code, 'extension');
                $moduleData = [];
                $modules = $this->model_setting_module->getModulesByCode($code);
                foreach ($modules as $module) {
                    $setting = json_decode($module['setting'], true);
                    if (! (bool)array_get($setting, 'status')) {
                        continue;
                    }
                    $moduleData[] = array(
                        'name' => strip_tags($module['name']),
                        'code' => $code . '.' .  $module['module_id'],
                        'href' => $this->url->link("extension/module/{$code}", "module_id={$module['module_id']}"),
                    );
                }

                if ((bool)config("module_{$code}_status") || $moduleData) {
                    if (!$moduleData) {
                        $moduleData[] = array(
                            'name' => strip_tags(t('extension')->get('heading_title')),
                            'code' => $code,
                            'href' => $this->url->link("extension/module/{$code}"),
                        );
                    }

                    $data['extensions'][] = array(
                        'code'   => $code,
                        'name'   => strip_tags(t('extension')->get('heading_title')),
                        'href' => $this->url->link("extension/module/{$code}"),
                        'module' => $moduleData
                    );
                }
            }
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('app/setting', $data));
    }

    public function validate()
    {
        if (!$this->user->hasPermission('modify', 'app/setting')) {
            $this->error['warning'] = t('error_permission');
        }

        return !$this->error;
    }
}
