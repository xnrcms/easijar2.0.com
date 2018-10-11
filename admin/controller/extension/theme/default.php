<?php
class ControllerExtensionThemeDefault extends Controller {
    const THEME_CODE = 'default';
    private $error = array();

    public function install()
    {
        $this->addEvents(1);
    }

    public function uninstall()
    {
        $this->removeEvents();
    }

    public function index() {
        $this->load->language('extension/theme/' . self::THEME_CODE);
        $this->document->setTitle(t('heading_title'));

        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('theme_' . self::THEME_CODE, $this->request->post, $this->request->get['store_id']);

            $this->clearCssCache();

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

        $data['directories'] = array();
        $directories = glob(DIR_CATALOG . 'view/theme/*', GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $data['directories'][] = basename($directory);
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/theme/' . self::THEME_CODE, $data));
    }

    protected function validate() {
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

    // Add events
    private function addEvents($active = 0) {
        $this->load->model('setting/event');
        foreach ($this->getEvents() as $key => $event) {
            list($trigger, $action) = explode(':', $event);
            if ($trigger && $action) {
                $this->model_setting_event->addEvent($key, $trigger, $action, $active);
            }
        }
    }

    // Remove events
    private function removeEvents() {
        $this->load->model('setting/event');
        foreach ($this->getEvents() as $key => $event) {
            $this->model_setting_event->deleteEventByCode($key);
        }
    }

    private function getEvents()
    {
        return [
            self::THEME_CODE . '.c.c.common.menu.before' => 'catalog/controller/common/menu/before:extension/theme/default/event/controllerCommonMenuBefore',
        ];
    }

    // Clear custom css cache file
    private function clearCssCache()
    {
        $prefix = self::THEME_CODE . '.custom';
        $pattern = DIR_IMAGE . "cache/{$prefix}.*.css";
        $files = glob($pattern);
        array_map('unlink', glob($pattern));
    }
}
