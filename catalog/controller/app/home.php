<?php

use Models\Blog\Post;

class ControllerAppHome extends Controller
{
    private static $moduleIndex = 0;
    private $debug = false;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('app/app');
        $this->load->model('catalog/category');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/product');
        $this->load->model('catalog/product_pro');
        $this->load->model('setting/module');
        $this->load->model('tool/image');
    }

    public function index()
    {
        $modules = config('app_home_modules');
        if (!$modules) {
            echo '没有配置首页！';
            return;
        }

        $this->debug = (bool)array_get($this->request->get, 'debug');

        $html = '';
        foreach ($modules as $code) {
            $segments = explode('.', $code);

            if (count($segments) == 1) {
                if (!config("module_{$segments[0]}_status")) {
                    continue;
                }

                $html .= $this->renderModuleByType($segments[0]);
            }

            if (count($segments) > 1) {
                $moduleId = (int)array_get($segments, 1);
                if ($moduleId &&
                    ($module = $this->model_setting_module->getModule($moduleId)) &&
                    (bool)array_get($module, 'status')) {
                    $html .= $this->renderModuleByType($segments[0], $module);
                }
            }
        }

        $data['base'] = config('config_url');
        $data['html'] = $html;
        $data['styles'] = $this->document->getStyles();
        $data['scripts'] = $this->document->getScripts('header');

        $this->response->setOutput($this->load->view('app/template/home/index', $data));
    }

    protected function renderModuleByType($type, $module = null)
    {
        $method = 'render' . str_replace('_', '', $type);
        if (method_exists($this, $method)) {
            return $this->{$method}($module);
        }
    }

    public function renderIcon($setting)
    {
        $icons = array_get($setting, 'item');
        if (!$icons) {
            return;
        }

        $data = [];
        foreach ($icons as $icon) {
            $title = array_get($icon, 'title.' . current_language_id());
            $href = array_get($icon, 'href');
            $image = array_get($icon, 'image');
            if (!$title || !$image) {
                continue;
            }
            $data['icons'][] = array(
                'title' => $title,
                'href' => $href,
                'image' => image_resize($image, 200, 200),
            );
        }

        if ($data) {
            $data['module_id'] = ++self::$moduleIndex;
            $data['placeholder'] = image_resize('placeholder.png', 200, 200);
            return $this->load->view('app/template/home/parts/icon', $data);
        }
    }

    protected function renderFeatured($setting)
    {
        if (empty($setting['product'])) {
            return;
        }

        if (!$setting['limit']) {
            $setting['limit'] = 4;
        }

        $data['products'] = [];
        foreach ($setting['product'] as $product_id) {
            $result = $this->model_catalog_product->getProduct($product_id);
            if (!$result) {
                continue;
            }

            $href = "product_id:{$result['product_id']}";
            if ($product = $this->model_catalog_product->handleSingleProduct($result, $setting['width'], $setting['height'], $href)) {
                unset($product['description']);
                if ($this->debug) {
                    $product['name'] = "[{$result['product_id']}] {$product['name']}";
                }
                $product['image'] = $product['thumb'];
                unset($product['thumb']);
                // Override special price with flash sale price
                if ($product['flash']) {
                    $product['special'] = $product['flash'];
                }
                $data['products'][] = $product;
            }

            if (count($data['products']) >= (int)$setting['limit']) {
                break;
            }
        }

        if ($data) {
            $this->load->language('extension/module/featured', 'featured');
            $data['heading_title'] = t('featured')->get('heading_title');
            $data['module_id'] = ++self::$moduleIndex;
            $data['placeholder'] = image_resize('placeholder.png', $setting['width'], $setting['height']);
            return $this->load->view('app/template/home/parts/featured', $data);
        }
    }

    protected function renderLatest($setting)
    {
        $filter = array(
            'sort'  => 'p.date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' => $setting['limit']
        );

        $results = $this->model_catalog_product_pro->getProducts($filter);
        if (!$results) {
            return;
        }

        $data['products'] = [];
        foreach ($results as $result) {
            $href = "product_id:{$result['product_id']}";
            if ($product = $this->model_catalog_product->handleSingleProduct($result, $setting['width'], $setting['height'], $href)) {
                unset($product['description']);
                if ($this->debug) {
                    $product['name'] = "[{$result['product_id']}] {$product['name']}";
                }
                $product['image'] = $product['thumb'];
                unset($product['thumb']);
                // Override special price with flash sale price
                if ($product['flash']) {
                    $product['special'] = $product['flash'];
                }
                $data['products'][] = $product;
            }
        }

        if ($data) {
            $this->load->language('extension/module/latest', 'latest');
            $data['heading_title'] = t('latest')->get('heading_title');
            $data['module_id'] = ++self::$moduleIndex;
            $data['placeholder'] = image_resize('placeholder.png', $setting['width'], $setting['height']);
            return $this->load->view('app/template/home/parts/latest', $data);
        }
    }

    protected function renderSpecial($setting)
    {
        $filter = array(
            'sort'  => 'pd.name',
            'order' => 'ASC',
            'start' => 0,
            'limit' => $setting['limit']
        );

        $results = $this->model_catalog_product->getProductSpecials($filter);
        if (!$results) {
            return;
        }

        $data['products'] = [];
        foreach ($results as $result) {
            $href = "product_id:{$result['product_id']}";
            if ($product = $this->model_catalog_product->handleSingleProduct($result, $setting['width'], $setting['height'], $href)) {
                unset($product['description']);
                if ($this->debug) {
                    $product['name'] = "[{$result['product_id']}] {$product['name']}";
                }
                $product['image'] = $product['thumb'];
                unset($product['thumb']);
                // Override special price with flash sale price
                if ($product['flash']) {
                    $product['special'] = $product['flash'];
                }
                $data['products'][] = $product;
            }
        }

        if ($data) {
            $this->load->language('extension/module/special', 'special');
            $data['heading_title'] = t('special')->get('heading_title');
            $data['module_id'] = ++self::$moduleIndex;
            $data['placeholder'] = image_resize('placeholder.png', $setting['width'], $setting['height']);
            return $this->load->view('app/template/home/parts/special', $data);
        }
    }

    protected function renderBanner($setting)
    {
        $this->load->model('design/banner');
        if (!$results = $this->model_design_banner->getBanner($setting['banner_id'])) {
            return;
        }

        $data = [];
        foreach ($results as $result) {
            if (image_exists($result['image'])) {
                $data['slides'][] = array(
                    'href'  => trim($result['link']),
                    'image' => image_resize($result['image'], $setting['width'], $setting['height'])
                );
            }
        }

        if ($data) {
            $this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
            $this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.min.js');

            $data['module_id'] = ++self::$moduleIndex;
            return $this->load->view('app/template/home/parts/banner', $data);
        }
    }

    protected function renderSlideshow($setting)
    {
        $this->load->model('design/banner');
        if (!$results = $this->model_design_banner->getBanner($setting['banner_id'])) {
            return;
        }

        $data = [];
        foreach ($results as $result) {
            if (image_exists($result['image'])) {
                $data['slides'][] = array(
                    'href'  => trim($result['link']),
                    'image' => image_resize($result['image'], $setting['width'], $setting['height'])
                );
            }
        }

        if ($data) {
            $this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
            $this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.min.js');

            $data['module_id'] = ++self::$moduleIndex;
            return $this->load->view('app/template/home/parts/slideshow', $data);
        }
    }

    protected function renderBlogLatest()
    {
        if (!config('blog_status')) {
            return;
        }

        $data['posts'] = Post::orderBy('sort_order', 'asc')->orderBy('post_id', 'desc')->take(config('module_blog_latest_limit'))->get();
        if (!$data['posts']) {
            return;
        }

        $data['heading_title'] = config('module_blog_latest_title.' . current_language_id());

        $this->load->language('blog/blog', 'blog');
            $data['text_view_count'] = t('blog')->get('text_view_count');

        return $this->load->view('app/template/home/parts/blog_latest', $data);
    }
}
