<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-01-16 21:04:28
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-02-05 11:41:23
 */

class ControllerExtensionThemeMobile extends Controller
{
    const THEME_CODE = 'mobile';
    private $error = array();
    private $events = [
        'catalog/view/product/*/before' => 'mobile/event/viewProductCategoryBefore',
        'catalog/view/common/footer/before' => 'mobile/event/viewCommonFooterBefore',
    ];

    public function index()
    {
        $this->load->language('extension/theme/' . self::THEME_CODE);
        $this->document->setTitle(t('heading_title'));

        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('theme_' . self::THEME_CODE, $this->request->post, $this->request->get['store_id']);

            // Toggle on/off theme events
            if (array_get($this->request->post, 'theme_' . self::THEME_CODE . '_status')) {
                $this->addEvents();
            } else {
                $this->deleteEvents();
            }

            // Clear custom css cache file
            $prefix = self::THEME_CODE . '.custom';
            $pattern = DIR_IMAGE . "cache/{$prefix}.*.css";
            $files = glob($pattern);
            array_map('unlink', glob($pattern));

            $this->session->data['success'] = t('text_success');
            $this->response->redirect($this->url->link('extension/theme/' . self::THEME_CODE, 'store_id=0'));
        }

        $data['error'] = $this->error;

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_extension'), $this->url->link('marketplace/extension', '&type=theme'));
        $breadcrumbs->add(t('heading_title'), $this->url->link('extension/theme/' . self::THEME_CODE, '&store_id=' . $this->request->get['store_id']));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $data['action'] = $this->url->link('extension/theme/' . self::THEME_CODE, 'store_id=' . $this->request->get['store_id']);
        $data['cancel'] = $this->url->link('marketplace/extension', 'type=theme');

        if (isset($this->request->get['store_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $data['setting'] = $this->model_setting_setting->getSetting('theme_' . self::THEME_CODE, $this->request->get['store_id']);
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/theme/' . self::THEME_CODE, $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/theme/' . self::THEME_CODE)) {
            $this->error['warning'] = t('error_permission');
        }

        if (!$this->request->post['theme_' . self::THEME_CODE . '_product_limit']) {
            $this->error['product_limit'] = t('error_limit');
        }

        if (!$this->request->post['theme_' . self::THEME_CODE . '_product_description_length']) {
            $this->error['product_description_length'] = t('error_limit');
        }

        $dimensions = [
            'image_category',
            'image_thumb',
            'image_preview',
            'image_popup',
            'image_product',
            'image_additional',
            'image_related',
            'image_compare',
            'image_wishlist',
            'image_cart',
            'image_location'
        ];

        foreach ($dimensions as $dimension) {
            if (!$this->request->post['theme_' . self::THEME_CODE . "_{$dimension}_width"] || !$this->request->post['theme_' . self::THEME_CODE . "_{$dimension}_height"]) {
                $this->error[$dimension] = t('error_' . $dimension);
            }
        }

        return !$this->error;
    }

    private function addEvents()
    {
        $event_code = 'theme_' . self::THEME_CODE;
        $this->deleteEvents();

        if ($this->events) {
            foreach ($this->events as $trigger => $action) {
                $this->model_setting_event->addEvent($event_code, $trigger, $action);
            }
        }
    }

    private function deleteEvents()
    {
        $event_code = 'theme_' . self::THEME_CODE;
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode($event_code);
    }
}
