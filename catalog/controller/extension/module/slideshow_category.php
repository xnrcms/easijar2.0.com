<?php
class ControllerExtensionModuleSlideshowCategory extends Controller
{
    private static $moduleId = 0;

    public function index($setting)
    {
        if (config('is_mobile')) {
            return;
        }

        $banners = $this->getBanners($setting);
        if ($banners) {
            $data['module_id'] = ++$this->moduleId;
            $data['banners'] = $banners;
            $data['height'] = $setting['height'];
            $data['categories'] = $this->getCategories($setting);
            $this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
            $this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.min.js');

            return $this->load->view('extension/module/slideshow_category', $data);
        }
    }

    protected function getBanners($setting)
    {
        $banners = [];
        $this->load->model('design/banner');
        $results = $this->model_design_banner->getBanner($setting['banner_id']);
        if (!$results) {
            return $banners;
        }

        foreach ($results as $result) {
            if (!$image = array_get($result, 'image')) {
                continue;
            }
            if (!image_exists($image)) {
                continue;
            }
            $banners[] = array(
                'title' => $result['title'],
                'href'  => $result['link'],
                'image' => image_resize($result['image'], $setting['width'], $setting['height'])
            );
        }

        return $banners;
    }

    protected function getCategories($setting)
    {
        // No necessary to load categories on mobile
        if (config('is_mobile')) {
            return;
        }

        $categoryIds = array_get($setting, 'category');
        if (!$categoryIds) {
            return;
        }

        $this->load->model('catalog/category');
        $this->load->model('catalog/product');

        $categoriesData = [];
        $categorys      = $this->model_catalog_category->getCategoryByIds($categoryIds);
        if(!$categorys){
            return;
        }

        $parent_ids    = [];
        foreach ($categorys as $category) {
            $parent_ids[] = $category['category_id'];
        }

        if (!$parent_ids) {
            return;
        }

        $children_categorys         = $this->model_catalog_category->getCategoriesByParentIds($parent_ids);
        $child_cate                 = [];
        foreach ($children_categorys as $key => $value) {
            $child_cate[$value['parent_id']][] = $value;
        }

        foreach ($categorys as $category) {

            //$children = $this->model_catalog_category->getCategories($category['category_id']);
            $children = (isset($child_cate[$category['category_id']]) && !empty($child_cate[$category['category_id']])) ? $child_cate[$category['category_id']] : [];
            if (!$children) {
                continue;
            }

            $childrenData = [];
            foreach($children as $child) {
                $childrenData[] = array(
                    'name' => $child['name'],
                    'href' => $this->url->link('product/category', ['path' => "{$category['category_id']}_{$child['category_id']}"])
                );
            }

            $productsData = [];
            if ($childrenData) {
                $filterData = array(
                    'filter_category_id'  => $category['category_id'],
                    'filter_sub_category' => true,
                    'sort'                => 'p.date_modified',
                    'order'               => 'DESC',
                    'parent_id'           => 0,
                    'start'               => 0,
                    'limit'               => 4
                );

                $products = $this->model_catalog_product->getProducts($filterData);
                foreach ($products as $product) {
                    $productsData[] = $this->model_catalog_product->handleSingleProduct($product, 200, 200);
                }
            }

            $categoriesData[] = array(
                'name'        => $category['name'],
                'href'        => $this->url->link('product/category', ['path' => $category['category_id']]),
                'description' => utf8_substr(trim(strip_tags(html_entity_decode($category['description'], ENT_QUOTES, 'UTF-8'))), 0, 30),
                'children'    => $childrenData,
                'products'    => $productsData
            );
        }

        return $categoriesData;
    }
}
