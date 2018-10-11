<?php

/**
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-01-12 09:31:29
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-02-01 13:53:19
 */

class ControllerMobileCategories extends Controller {
    public function index() {
        if (!config('is_mobile')) {
            $this->redirectToHome();
        }

        $this->document->setTitle(config('config_meta_title'));
        $this->document->setDescription(config('config_meta_description'));
        $this->document->setKeywords(config('config_meta_keyword'));

        if (isset($this->request->get['route'])) {
            $this->document->addLink(HTTP_SERVER, 'canonical');
        }

        // Categories
        $this->load->model('catalog/category');
        $this->load->model('mobile/mobile');
        $this->load->model('catalog/product');
        $data['categories'] = array();

        $categories = $this->model_catalog_category->getCategories(0);
        if (!$categories) {
            $this->redirectToHome();
        }

        foreach ($categories as $category) {
            // Level 2
            $children = $this->model_catalog_category->getCategories($category['category_id']);
            $children_data = [];
            if ($children) {
                foreach ($children as $child) {
                    $grand_children = $this->model_catalog_category->getCategories($child['category_id']);
                    $grand_children_data = [];
                    foreach ($grand_children as $grand_child) {
                        if ($mobile_image = $this->model_mobile_mobile->getCategoryMobileImage($grand_child['category_id'])) {
                            $image = image_resize($mobile_image, 150, 150);
                        } else {
                            $image = image_resize($child['image'], 150, 150);
                        }

                        $grand_children_data[] = array(
                            'thumb' => $image,
                            'name'  => $grand_child['name'],
                            'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $grand_child['category_id'])
                        );
                    }

                    if ($mobile_image = $this->model_mobile_mobile->getCategoryMobileImage($child['category_id'])) {
                        $image = image_resize($mobile_image, 150, 150);
                    } else {
                        $image = image_resize($child['image'], 150, 150);
                    }

                    $children_data[] = array(
                        'thumb'          => $image,
                        'name'           => $child['name'],
                        'href'           => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id']),
                        'grand_children' => $grand_children_data
                    );
                }
            }

            if ($mobile_image = $this->model_mobile_mobile->getCategoryMobileImage($category['category_id'])) {
                $image = image_resize($mobile_image, 150, 150);
            } else {
                $image = image_resize($category['image'], 150, 150);
            }

            // Level 1
            $data['categories'][] = array(
                'name'     => $category['name'],
                'thumb'    => $image,
                'children' => $children_data,
                'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
            );
        }

        $data['placeholder'] = image_resize();

        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('mobile/categories', $data));
    }
}
