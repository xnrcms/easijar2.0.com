<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-04-12 18:03:29
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-04-26 15:05:05
 */

class ControllerExtensionThemeDefaultEvent extends Controller
{
    const THEME_CODE = 'default';
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/theme/' . self::THEME_CODE, '_theme_' . self::THEME_CODE);
    }

    private function isActiveTheme()
    {
        return config('config_theme') == self::THEME_CODE
            && config('theme_' . self::THEME_CODE . '_directory') == self::THEME_CODE;
    }

    private function t($key)
    {
        return t('_theme_' . self::THEME_CODE)->get($key);
    }

    public function controllerCommonMenuBefore(&$route, &$args)
    {
        if (!$this->isActiveTheme()) {
            return;
        }

        if (config('is_mobile')) {
            return;
        }

        $data['categories'] = [];

        $categories = [];
        $this->load->model('catalog/category');
        $results = $this->model_catalog_category->getCategories();
        foreach ($results as $result) {
            if (!$result['top']) {
                continue;
            }

            $categories[] = array(
                'name' => $result['name'],
                'href' => $this->url->link('product/category', 'path=' . $result['category_id'])
            );
        }

        if ($categories) {
            $data['categories'][] = array(
                'name' => $this->t('text_all_category'),
                'children' => $categories,
                'href' => 'javascript:void(0)'
            );
        }

        $data['categories'][] = array(
            'name'     => $this->t('text_latest'),
            'href'     => $this->url->link('product/latest')
        );

        $data['categories'][] = array(
            'name'     => $this->t('text_special'),
            'href'     => $this->url->link('product/special')
        );

       /* $data['categories'][] = array(
            'name'     => $this->t('text_brand'),
            'href'     => $this->url->link('product/manufacturer')
        );*/

        $data['categories'][] = array(
            'name'     => $this->t('text_contact'),
            'href'     => $this->url->link('information/contact')
        );

        if (config('blog_status')) {
            $categories = \Models\Blog\Category::where('status', 1)->get();
            $childrenData = [];
            if ($categories) {
                foreach ($categories as $category) {
                    $childrenData[] = array(
                        'name'  => $category->localizedDescription()->name,
                        'href'  => $category->href('show')
                    );
                }
            }

            $data['categories'][] = array(
                'name'     => config('blog_menu_name.' . current_language_id(), $this->t('text_blog')),
                'children' => $childrenData,
                'href'     => $this->url->link('blog/home')
            );
        }

        return $this->load->view('common/menu', $data);
    }
}
