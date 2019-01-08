<?php

class ControllerExtensionModuleMultiFilter extends Controller
{
    private static $firstModule = false;
    private static $brands, $stock, $attributes, $options, $variants;

    protected $routeType = ''; // search/category
    protected $categoryParts = []; // category page path parts
    protected $searchKeyword = ''; // search page keyword
    protected $data = [];
    protected $setting = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/module/multi_filter');
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('catalog/product_pro');
    }

    public function getFilterForApi()
    {
        $route = current_route();
        if (!in_array($route, ['api/product/index','api/product/search'])) return;

        if ($route == 'api/product/index') {
            if ($path = array_get($this->request->post, 'path')) {
                $this->categoryParts = explode('_', (string)$path);
            } else {
                $this->categoryParts = null;
            }

            if ($this->categoryParts) {
                $this->routeType = 'category';
                return $this->initFilterForApi();
            }
            return;
        }

        if ($route == 'api/product/search') {
            if ($keyword = array_get($this->request->post, 'search')) {
                $this->routeType = 'search';
                $this->searchKeyword = $keyword;
                return $this->initFilterForApi();
            }
            return;
        }
    }

    public function index($setting)
    {
        $route = current_route();
        if (!in_array($route, ['product/category', 'product/search'])) {
            return;
        }

        $this->setting = $setting;

        // Init filter data for category page
        if ($route == 'product/category') {
            if ($path = array_get($this->request->get, 'path')) {
                $this->categoryParts = explode('_', (string)$path);
            } else {
                $this->categoryParts = null;
            }

            if ($this->categoryParts) {
                $this->routeType = 'category';
                return $this->initFilter();
            }
            return;
        }

        // Init filter data for search page
        if ($route == 'product/search') {
            if ($keyword = array_get($this->request->get, 'search')) {
                $this->routeType = 'search';
                $this->searchKeyword = $keyword;
                return $this->initFilter();
            }
            return;
        }
    }

    protected function initFilter()
    {
        $filter = array_get($this->request->get, 'filter', '');

        if ($brand = array_get($this->request->get, 'brand')) {
            $selectedBrandIds = parse_filters($brand);
        } else {
            $selectedBrandIds = [];
        }

        if ($variant = array_get($this->request->get, 'variant')) {
            $selectedVariantValueIds = parse_filters($variant);
        } else {
            $selectedVariantValueIds = [];
        }

        if ($option = array_get($this->request->get, 'option')) {
            $selectedOptionValueIds = parse_filters($option);
        } else {
            $selectedOptionValueIds = [];
        }

        if ($attr = array_get($this->request->get, 'attr')) {
            $selectedAttributes = parse_attributes($attr);
        } else {
            $selectedAttributes = [];
        }

        if (isset($this->request->get['in_stock'])) {
            $selectedInStock = array_get($this->request->get, 'in_stock');
        }

        if ($status = array_get($this->request->get, 'status')) {
            $selectedStockStatusIds = parse_filters($status);
        } else {
            $selectedStockStatusIds = [];
        }

        $keyword = array_get($this->request->get, 'search', '');

        if ($price = array_get($this->request->get, 'price')) {
            $selectedPrices = parse_filters($price);
        } else {
            $selectedPrices = [];
        }

        $filterData = array(
            'filter_filter'           => $filter,
            'filter_name'             => $keyword,
            'filter_price'            => $selectedPrices,
            'filter_brand_ids'        => $selectedBrandIds,
            'filter_option_value_ids' => $selectedOptionValueIds,
            'filter_attributes'       => $selectedAttributes,
            'filter_sub_category'     => true,
        );
        if (isset($selectedInStock)) {
            $filterData['filter_in_stock'] = $selectedInStock;
            $this->data['selected_in_stock'] = $selectedInStock;
        }

        if ($this->routeType == 'category') {
            $categoryId = last($this->categoryParts);
            $filterData['filter_category_id'] = $categoryId;
        }

        $this->data['selected_brands'] = $selectedBrandIds;
        $this->data['selected_variants'] = $selectedVariantValueIds;
        $this->data['selected_options'] = $selectedOptionValueIds;
        $this->data['selected_statuses'] = $selectedStockStatusIds;
        $this->data['selected_attributes'] = $selectedAttributes;
        $this->data['selected_keyword'] = $keyword;

        /*if (!self::$brands) {
            self::$brands = $this->model_catalog_product_pro->getProductTotalGroupBrand($filterData);
        }
        $this->data['brands'] = self::$brands;*/
        $this->data['brands']       = [];
        $this->data['options']      = [];
        $this->data['attributes']   = [];

        if (!self::$stock) {
            self::$stock = $this->model_catalog_product_pro->getProductTotalGroupStockStatus($filterData);
        }
        $this->data['stock'] = self::$stock;

        /*if (!self::$attributes) {
            self::$attributes = $this->model_catalog_product_pro->getProductTotalGroupAttrValues($filterData);
        }
        $this->data['attributes'] = self::$attributes;

        if (!self::$options) {
            self::$options = $this->model_catalog_product_pro->getProductTotalGroupOptionValues($filterData);
        }
        $this->data['options'] = self::$options;*/

        if (!self::$variants) {
            self::$variants = $this->model_catalog_product_pro->getProductTotalGroupVariantValues($filterData);
        }
        $this->data['variants'] = self::$variants;

        // 无筛选数据
        if (!self::$brands && !self::$stock && !self::$attributes && !self::$options && !self::$variants) {
            return;
        }

        if ($priceRange = $this->model_catalog_product_pro->getProductPriceRange($filterData)) {
            if (count($priceRange) == 2) {
                $min = (int)$priceRange[0];
                $max = (int)$priceRange[1];

                if ($min > 0 && $max > 0 && $max > $min) {
                    $this->data['price_range'] = array(
                        'min' => $min,
                        'max' => $max,
                    );

                    if (count($selectedPrices) != 2) {
                        $selectedPriceRangeMin = $this->data['price_range']['min'];
                        $selectedPriceRangeMax = $this->data['price_range']['max'];
                    } else {
                        $selectedPriceRangeMin = $selectedPrices[0];
                        $selectedPriceRangeMax = $selectedPrices[1];
                    }

                    $selectedPriceRangeMin = max($selectedPriceRangeMin, $min);
                    $selectedPriceRangeMax = min($selectedPriceRangeMax, $max);

                    if ($selectedPriceRangeMin > $selectedPriceRangeMax) {
                        $selectedPriceRangeMin = $min;
                    }

                    if ($selectedPriceRangeMax < $selectedPriceRangeMin) {
                        $selectedPriceRangeMax = $max;
                    }

                    if ((int)$selectedPriceRangeMin > $min || $selectedPriceRangeMax < $max) {
                        $this->data['selected_price_range'] = [(int)$selectedPriceRangeMin, (int)$selectedPriceRangeMax];
                    }

                    $this->document->addStyle('catalog/view/javascript/jquery/jquery-ui/jquery-ui.min.css');
                    $this->document->addScript('catalog/view/javascript/jquery/jquery-ui/jquery-ui.min.js');
                }
            }
        }
        
        if (!self::$firstModule) {
            self::$firstModule = true;
            $url = $this->url->getQueriesOnly(['path', 'sort', 'limit', 'order', 'search']);
            $this->data['filter']['href'] = html_entity_decode($this->url->link(current_route(), $url));
            $this->data['filter']['selected_in_stock'] = (isset($selectedInStock) && $selectedInStock) ? 1 : 0;
            $this->data['filter']['selected_brands'] = array_map('intval', $selectedBrandIds);
            $this->data['filter']['selected_variants'] = array_map('intval', $selectedVariantValueIds);
            $this->data['filter']['selected_options'] = array_map('intval', $selectedOptionValueIds);
            $this->data['filter']['selected_statuses'] = array_map('intval', $selectedStockStatusIds);
            $this->data['filter']['selected_attributes'] = (object)$selectedAttributes;
            $this->data['filter']['selected_keyword'] = $keyword;
            $this->data['filter']['selected_price_range'] = array_get($this->data, 'selected_price_range', []);
            $this->data['filter']['mobile'] = config('is_mobile');

            $this->document->addScript('catalog/view/javascript/filter.js');
            if (config('is_mobile')) {
                $this->document->addScript('catalog/view/javascript/jquery/jquery-ui/jquery-ui.min.js');
                $this->document->addScript('catalog/view/javascript/jquery/jquery.ui.touch-punch.min.js');
            }
        }

        $template = 'extension/module/multi_filter/' . ($this->isPositionColumnLeftOrRight() ? 'column_left_right' : 'content_top_bottom');
        return $this->load->view($template, $this->data);
    }

    // 判断模块是否在布局在 column_left/column_right
    protected function isPositionColumnLeftOrRight()
    {
        if (!$position = array_get($this->setting, 'position')) {
            return false;
        }
        return in_array($position, ['column_left', 'column_right']);
    }

    protected function initFilterForApi()
    {
        $filter = array_get($this->request->post, 'filter', '');

        if ($brand = array_get($this->request->post, 'brand')) {
            $selectedBrandIds = parse_filters($brand);
        } else {
            $selectedBrandIds = [];
        }

        if ($variant = array_get($this->request->post, 'variant')) {
            $selectedVariantValueIds = parse_filters($variant);
        } else {
            $selectedVariantValueIds = [];
        }

        if ($option = array_get($this->request->post, 'option')) {
            $selectedOptionValueIds = parse_filters($option);
        } else {
            $selectedOptionValueIds = [];
        }

        if ($attr = array_get($this->request->post, 'attr')) {
            $selectedAttributes = parse_attributes($attr);
        } else {
            $selectedAttributes = [];
        }

        if (isset($this->request->post['in_stock'])) {
            $selectedInStock = array_get($this->request->post, 'in_stock');
        }

        if ($status = array_get($this->request->post, 'status')) {
            $selectedStockStatusIds = parse_filters($status);
        } else {
            $selectedStockStatusIds = [];
        }

        $keyword = array_get($this->request->post, 'search', '');

        if ($price = array_get($this->request->post, 'price')) {
            $selectedPrices = parse_filters($price);
        } else {
            $selectedPrices = [];
        }

        $filterData = array(
            'filter_filter'           => $filter,
            'filter_name'             => $keyword,
            'filter_price'            => $selectedPrices,
            'filter_brand_ids'        => $selectedBrandIds,
            'filter_option_value_ids' => $selectedOptionValueIds,
            'filter_attributes'       => $selectedAttributes,
            'filter_sub_category'     => true,
        );
        if (isset($selectedInStock)) {
            $filterData['filter_in_stock'] = $selectedInStock;
            $this->data['selected_in_stock'] = $selectedInStock;
        }

        if ($this->routeType == 'category') {
            $categoryId = last($this->categoryParts);
            $filterData['filter_category_id'] = $categoryId;
        }

        $this->data['selected_brands'] = $selectedBrandIds;
        $this->data['selected_variants'] = $selectedVariantValueIds;
        $this->data['selected_options'] = $selectedOptionValueIds;
        $this->data['selected_statuses'] = $selectedStockStatusIds;
        $this->data['selected_attributes'] = $selectedAttributes;
        $this->data['selected_keyword'] = $keyword;

        /*if (!self::$brands) {
            self::$brands = $this->model_catalog_product_pro->getProductTotalGroupBrand($filterData);
        }
        $this->data['brands'] = self::$brands;*/
        $this->data['brands']       = [];
        $this->data['options']      = [];
        $this->data['attributes']   = [];

        if (!self::$stock) {
            self::$stock = $this->model_catalog_product_pro->getProductTotalGroupStockStatus($filterData);
        }
        $this->data['stock'] = self::$stock;
        
        /*if (!self::$attributes) {
            self::$attributes = $this->model_catalog_product_pro->getProductTotalGroupAttrValues($filterData);
        }
        $this->data['attributes'] = self::$attributes;*/

        /*if (!self::$options) {
            self::$options = $this->model_catalog_product_pro->getProductTotalGroupOptionValues($filterData);
        }
        $this->data['options'] = self::$options;*/

        if (!self::$variants) {
            self::$variants = $this->model_catalog_product_pro->getProductTotalGroupVariantValues($filterData);
        }
        $this->data['variants'] = self::$variants;

        // 无筛选数据
        if (!self::$brands && !self::$stock && !self::$attributes && !self::$options && !self::$variants) {
            return;
        }

        if ($priceRange = $this->model_catalog_product_pro->getProductPriceRange($filterData)) {
            if (count($priceRange) == 2) {
                $min = (int)$priceRange[0];
                $max = (int)$priceRange[1];

                if ($min > 0 && $max > 0 && $max > $min) {
                    $this->data['price_range'] = array(
                        'min' => $min,
                        'max' => $max,
                    );

                    if (count($selectedPrices) != 2) {
                        $selectedPriceRangeMin = $this->data['price_range']['min'];
                        $selectedPriceRangeMax = $this->data['price_range']['max'];
                    } else {
                        $selectedPriceRangeMin = $selectedPrices[0];
                        $selectedPriceRangeMax = $selectedPrices[1];
                    }

                    $selectedPriceRangeMin = max($selectedPriceRangeMin, $min);
                    $selectedPriceRangeMax = min($selectedPriceRangeMax, $max);

                    if ($selectedPriceRangeMin > $selectedPriceRangeMax) {
                        $selectedPriceRangeMin = $min;
                    }

                    if ($selectedPriceRangeMax < $selectedPriceRangeMin) {
                        $selectedPriceRangeMax = $max;
                    }

                    if ((int)$selectedPriceRangeMin > $min || $selectedPriceRangeMax < $max) {
                        $this->data['selected_price_range'] = [(int)$selectedPriceRangeMin, (int)$selectedPriceRangeMax];
                    }
                }
            }
        }

        if (!self::$firstModule) {
            self::$firstModule                              = true;
            $url                                            = $this->url->getQueriesOnly(['path', 'sort', 'limit', 'order', 'search']);
            $this->data['filter']['href']                   = html_entity_decode($this->url->link(current_route(), $url));
            $this->data['filter']['selected_in_stock']      = (isset($selectedInStock) && $selectedInStock) ? 1 : 0;
            $this->data['filter']['selected_brands']        = array_map('intval', $selectedBrandIds);
            $this->data['filter']['selected_variants']      = array_map('intval', $selectedVariantValueIds);
            $this->data['filter']['selected_options']       = array_map('intval', $selectedOptionValueIds);
            $this->data['filter']['selected_statuses']      = array_map('intval', $selectedStockStatusIds);
            $this->data['filter']['selected_attributes']    = (object)$selectedAttributes;
            $this->data['filter']['selected_keyword']       = $keyword;
            $this->data['filter']['selected_price_range']   = array_get($this->data, 'selected_price_range', []);
            $this->data['filter']['mobile']                 = config('is_mobile');
        }

        return $this->data;
    }
}
