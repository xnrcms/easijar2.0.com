<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-04-04 17:07:07
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-05-30 15:26:59
 */

class ControllerMiniAppHome extends Controller
{
    public function index()
    {
        $modules = config('miniapp_home_modules');
        if (!$modules) {
            return;
        }

        $this->load->model('setting/module');
        $sections = [];
        foreach ($modules as $code) {
            $segments = explode('.', $code);

            if (count($segments) == 1 && !config("module_{$segments[0]}_status")) {
                continue;
            }

            $moduleId = (int)array_get($segments, 1);
            if ($moduleId &&
                ($module = $this->model_setting_module->getModule($moduleId)) &&
                (bool)array_get($module, 'status')) {
                if ($data = $this->renderModuleByType($segments[0], $module)) {
                    $sections[] = array(
                        'type' => $segments[0],
                        'data' => $data
                    );
                }
            }
        }

        $this->jsonOutput($sections);
    }

    protected function renderModuleByType($type, $module)
    {
        $method = 'render' . str_replace('_', '', $type);
        if (method_exists($this, $method)) {
            return $this->{$method}($module);
        }
    }

    public function renderIcon($setting)
    {
        $items = array_get($setting, 'item');
        if (!$items) {
            return;
        }

        $data = [];
        foreach ($items as $item) {
            $title = array_get($item, 'title.' . current_language_id());
            $href = array_get($item, 'href');
            $image = array_get($item, 'image');
            if (!$title || !$image) {
                continue;
            }
            $data['items'][] = array(
                'title' => $title,
                'href' => $href,
                'image' => image_resize($image, 200, 200),
            );
        }

        if ($data) {
            return $data;
        }
    }

    protected function renderFeatured($setting)
    {
        if (empty($setting['product'])) {
            return;
        }

        $this->load->model('catalog/product');
        if (!$setting['limit']) {
            $setting['limit'] = 4;
        }

        $data['products'] = [];
        foreach ($setting['product'] as $product_id) {
            $result = $this->model_catalog_product->getProduct($product_id);
            if (!$result) {
                continue;
            }

            if ($product = $this->model_catalog_product->handleSingleProduct($result, $setting['width'], $setting['height'])) {
                unset($product['description']);
                $product['image'] = $product['thumb'];
                unset($product['thumb']);
                $data['products'][] = $product;
            }

            if (count($data['products']) >= (int)$setting['limit']) {
                break;
            }
        }

        if ($data) {
            $this->load->language('extension/module/featured', 'featured');
            $data['title'] = t('featured')->get('heading_title');
            return $data;
        }
    }

    protected function renderImageCombo($setting)
    {
        $items = array_get($setting, 'item');
        $style = array_get($setting, 'style');
        if (!$items || !$style) {
            return;
        }

        $data = [];
        foreach ($items as $item) {
            $href = array_get($item, 'href.' . current_language_id());
            $image = array_get($item, 'image');
            if (!$href || !$image) {
                continue;
            }
            $data['items'][] = array(
                'image' => config('config_url') .'image/' . $image,
                'href' => $href
            );
        }

        if ($data) {
            $data['style'] = $style;
            return $data;
        }
    }

    protected function renderBanner($setting)
    {
        return $this->renderSlideshow($setting);
    }

    protected function renderSlideshow($setting)
    {
        $this->load->model('design/banner');
        if (!$results = $this->model_design_banner->getBanner($setting['banner_id'])) {
            return;
        }

        $data = [];
        foreach ($results as $result) {
            if (is_file(DIR_IMAGE . $result['image'])) {
                $data[] = array(
                    'link'  => trim($result['link']),
                    'image' => image_resize($result['image'], $setting['width'], $setting['height'])
                );
            }
        }

        return $data;
    }
}
