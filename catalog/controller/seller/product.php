<?php

/**
 * product.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-06-06 10:20
 * @modified 2018-06-06 10:20
 */
class ControllerSellerProduct extends Controller
{
    private $error = array();
    private $ms_product = null;
    private $ms_seller = null;
    private $ms_shipping = null;

    public function __construct($registry)
    {
        parent::__construct($registry);

        // Check customer login
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('seller/product');
            $this->response->redirect($this->url->link('account/login'));
        }

        $this->ms_seller = \Seller\MsSeller::getInstance($registry);

        // Check is seller
        if (!$this->ms_seller->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
        }

        $this->ms_shipping = \Seller\MsShipping::getInstance($registry);

        // Check seller shipping
        if ($this->ms_shipping->shippingRequired()) {
            $this->response->redirect($this->url->link('seller/shipping_cost'));
        }

        // Check seller upload image permission
        if (!session_id()) {
            session_start();
        }
        $_SESSION['seller_upload_permission'] = $this->ms_seller->sellerId();

        $this->load->model('localisation/language');
        $this->load->model('localisation/length_class');
        $this->load->model('localisation/weight_class');
        $this->load->model('tool/image');
        $this->load->model('catalog/product');
        $this->load->model('account/customer_group');
        $this->load->model('catalog/manufacturer');
        $this->load->model('multiseller/shipping_cost');

        $this->ms_product = \Seller\MsProduct::getInstance($registry);
        $this->load->language('seller/product');
        $this->load->language('seller/layout');
        $this->document->setTitle($this->language->get('heading_title'));
    }

    public function index()
    {
        $this->getList();
    }

    public function delete()
    {
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $product_id) {
                $this->ms_product->deleteProduct($product_id);
            }
            $this->session->data['success'] = $this->language->get('text_delete_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_price'])) {
                $url .= '&filter_price=' . $this->request->get['filter_price'];
            }

            if (isset($this->request->get['filter_quantity'])) {
                $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_show_variant'])) {
                $url .= '&filter_show_variant=' . $this->request->get['filter_show_variant'];
            }

            if (isset($this->request->get['filter_sku'])) {
                $url .= '&filter_sku=' . $this->request->get['filter_sku'];
            }

            if (isset($this->request->get['filter_stock_quantity'])) {
                $url .= '&filter_stock_quantity=' . $this->request->get['filter_stock_quantity'];
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('seller/product', $url));
        }
        $this->getList();
    }

    public function edit()
    {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->ms_product->editProduct($this->request->get['product_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_edit_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_price'])) {
                $url .= '&filter_price=' . $this->request->get['filter_price'];
            }

            if (isset($this->request->get['filter_quantity'])) {
                $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_show_variant'])) {
                $url .= '&filter_show_variant=' . $this->request->get['filter_show_variant'];
            }

            if (isset($this->request->get['filter_sku'])) {
                $url .= '&filter_sku=' . $this->request->get['filter_sku'];
            }

            if (isset($this->request->get['filter_stock_quantity'])) {
                $url .= '&filter_stock_quantity=' . $this->request->get['filter_stock_quantity'];
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('seller/product', $url));
        }
        $this->getForm();
    }

    /**
     * total update product price
     */
    public function updatePrice()
    {
        if (!$this->ms_seller->isSeller()) {
            $json['status'] = 0;
            $json['message'] = t('error_permission');
            $this->jsonOutput($json);
            return;
        }

        $json = array();
        $this->load->language('catalog/product');
        $productIds = $this->request->post['product_id'];
        $type = $this->request->post['type'];
        $value = $this->request->post['value'];

        if ($type == 'reset') {
            $data = array();
            foreach ($productIds as $id) {
                $data[] = array(
                    'product_id' => $id,
                    'price' => $value
                );
            }
            $this->ms_product->batchUpdateProductPrice($data);
        }

        if ($type == 'raise') {
            $key = $this->request->post['key'];
            $products = $this->ms_product->getBatchProductTotal($productIds);
            if ($key == 'price') {
                $data = array();
                foreach ($products as $product) {
                    $data[] = array(
                        'product_id' => $product['product_id'],
                        'price' => (float)$product['price'] + $value
                    );
                }
                $this->ms_product->batchUpdateProductPrice($data);
            } elseif ($key == 'pct') {
                $data = array();
                foreach ($products as $product) {
                    $price = (float)$product['price'] + ((float)$product['price'] * ($value / 100));

                    $data[] = array(
                        'product_id' => $product['product_id'],
                        'price' => $price,
                    );
                }
                $this->ms_product->batchUpdateProductPrice($data);
            }
        }

        if ($type == 'reduce') {
            $key = $this->request->post['key'];
            $products = model('catalog/product')->getBatchProductTotal($productIds);

            if ($key == 'price') {
                $data = array();
                foreach ($products as $product) {
                    $data[] = array(
                        'product_id' => $product['product_id'],
                        'price' => (float)$product['price'] - $value
                    );
                }
                $this->ms_product->batchUpdateProductPrice($data);
            } elseif ($key == 'pct') {
                $data = array();
                foreach ($products as $product) {
                    $price = (float)$product['price'] - ((float)$product['price'] * ($value / 100));

                    $data[] = array(
                        'product_id' => $product['product_id'],
                        'price' => $price,
                    );
                }
                $this->ms_product->batchUpdateProductPrice($data);
            }
        }

        $json['status'] = 1;
        $json['message'] = t('update_success');
        $url = $this->url->getQueries();
        $url['selected'] = implode('.', $productIds);
        $json['link'] = html_entity_decode($this->url->link('seller/product', $url));
        $this->jsonOutput($json);
    }

    public function updateQuantity()
    {
        if (!$this->ms_seller->isSeller()) {
            $json['status'] = 0;
            $json['message'] = t('error_permission');
            $this->jsonOutput($json);
            return;
        }

        $json = array();
        $this->load->language('catalog/product');
        $productIds = $this->request->post['product_id'];
        $type = $this->request->post['type'];
        $value = $this->request->post['value'];

        if ($type == 'reset') {
            $data = array();
            foreach ($productIds as $id) {
                $data[] = array(
                    'product_id' => $id,
                    'quantity' => $value
                );
            }
            $this->ms_product->batchUpdateProductQuantity($data);
        }

        if ($type == 'raise') {
            $products = $this->ms_product->getBatchProductTotal($productIds);
            $data = array();
            foreach ($products as $product) {

                $data[] = array(
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'] + $value,
                );
            }
            $this->ms_product->batchUpdateProductQuantity($data);
        }

        if ($type == 'reduce') {
            $products = $this->ms_product->getBatchProductTotal($productIds);
            $data = array();
            foreach ($products as $product) {

                $data[] = array(
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'] - $value,
                );
            }
            $this->ms_product->batchUpdateProductQuantity($data);
        }

        $json['status'] = 1;
        $json['message'] = t('update_success');
        $url = $this->url->getQueries();
        $url['selected'] = implode('.', $productIds);
        $json['link'] = html_entity_decode($this->url->link('seller/product', $url));
        $this->jsonOutput($json);
    }

    public function updateStatus()
    {
        if (!$this->ms_seller->isSeller()) {
            $json['status'] = 0;
            $json['message'] = t('error_permission');
            $this->jsonOutput($json);
            return;
        }

        $json = array();
        $this->load->language('catalog/product');
        $productIds = $this->request->post['product_id'];
        $this->ms_product->batchUpdateProductStatus($productIds);

        $json['status'] = 1;
        $json['message'] = t('update_success');
        $url = $this->url->getQueries();
        $url['selected'] = implode('.', $productIds);
        $json['link'] = html_entity_decode($this->url->link('seller/product', $url));
        $this->jsonOutput($json);
    }

    public function add()
    {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->ms_product->addProduct($this->request->post);
            $this->session->data['success'] = $this->language->get('text_add_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_price'])) {
                $url .= '&filter_price=' . $this->request->get['filter_price'];
            }

            if (isset($this->request->get['filter_quantity'])) {
                $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_show_variant'])) {
                $url .= '&filter_show_variant=' . $this->request->get['filter_show_variant'];
            }

            if (isset($this->request->get['filter_sku'])) {
                $url .= '&filter_sku=' . $this->request->get['filter_sku'];
            }

            if (isset($this->request->get['filter_stock_quantity'])) {
                $url .= '&filter_stock_quantity=' . $this->request->get['filter_stock_quantity'];
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('seller/product', $url));
        }
        $this->getForm();
    }

    public function ckfinder()
    {
        $files = isset($this->request->post['files']) ? $this->request->post['files'] : '';
        $results = array();
        if ($files) {
            $this->load->model('tool/image');
            $files_array = explode(',', $files);
            $images = array();
            foreach ($files_array as $file) {
                $images[] = str_replace(HTTP_SERVER . 'image/', '', $file);
            }
            foreach ($images as $image) {
                if ($image && is_file(DIR_IMAGE . $image)) {
                    $tmp_thumb = $this->model_tool_image->resize($image, 100, 100);
                    $tmp_image = $image;
                } else {
                    $tmp_thumb = $this->model_tool_image->resize('no_image.png', 100, 100);
                    $tmp_image = '';
                }
                $item = array(
                    'thumb' => $tmp_thumb,
                    'image' => $tmp_image
                );
                $results[] = $item;
            }
        }
        if (!$results) {
            $json = array('code' => 0, 'result' => '');
        } else {
            $json = array(
                'code' => 1,
                'result' => $results
            );
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function categoryComplete()
    {
        $json = array();

        if (isset($this->request->get['filter_name'])) {

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'sort' => 'name',
                'order' => 'ASC',
                'start' => 0,
                'limit' => 5
            );

            $results = $this->ms_product->getCategories($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'category_id' => $result['category_id'],
                    'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function manufacturerComplete()
    {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/manufacturer');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start' => 0,
                'limit' => 5
            );

            $results = $this->model_catalog_manufacturer->getManufacturers($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'manufacturer_id' => $result['manufacturer_id'],
                    'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function productComplete()
    {
        $json = array();

        if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model'])
            || isset($this->request->get['filter_sku'])
        ) {

            if (isset($this->request->get['filter_name'])) {
                $filter_name = $this->request->get['filter_name'];
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_model'])) {
                $filter_model = $this->request->get['filter_model'];
            } else {
                $filter_model = '';
            }

            if (isset($this->request->get['filter_sku'])) {
                $filter_sku = $this->request->get['filter_sku'];
            } else {
                $filter_sku = '';
            }

            if (isset($this->request->get['filter_stock_quantity'])) {
                $filter_stock_quantity = $this->request->get['filter_stock_quantity'];
            } else {
                $filter_stock_quantity = '';
            }

            if (isset($this->request->get['limit'])) {
                $limit = $this->request->get['limit'];
            } else {
                $limit = 5;
            }

            $filter_data = array(
                'filter_name' => $filter_name,
                'filter_model' => $filter_model,
                'filter_sku' => $filter_sku,
                'filter_stock_quantity' => $filter_stock_quantity,
                'start' => 0,
                'limit' => $limit
            );

            $results = $this->model_catalog_product->getProducts($filter_data);
            foreach ($results as $result) {
                $product_id = $result['product_id'];
                $seller_product = $this->ms_product->getSellerProduct($product_id);
                if (!$seller_product) {
                    continue;
                }
                $json[] = array(
                    'product_id' => $result['product_id'],
                    'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'model' => $result['model'],
                    'sku' => $result['sku'],
                    'price' => $result['price'],
                    'minimum' => $result['minimum'],
                    'quantity' => $result['quantity']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function fileUpload()
    {
        $files = $_FILES;
        if (!$files) {
            $json = array(
                'code' => 0,
                'msg' => $this->language->get('error_upload_empty')
            );
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return false;
        }

        $opened_extensions = get_loaded_extensions();

        if (!in_array('fileinfo', $opened_extensions)) {
            $json = array(
                'code' => 0,
                'msg' => $this->language->get('error_upload_extension')
            );
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return false;
        }

        // Can upload file type: zip, rar, pdf
        $upload_filter = array('application/zip', 'application/octet-stream', 'application/pdf', 'application/x-rar');
        $array_keys = array_keys($_FILES);
        $files = $_FILES[$array_keys[0]];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $file_type = $finfo->file($files['tmp_name']);
        if (!in_array(strtolower($file_type), $upload_filter)) {
            $json = array(
                'code' => 0,
                'msg' => $this->language->get('error_upload_type')
            );
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return false;
        }

        $post_max = ini_get('post_max_size');
        $mul = substr($post_max, -1);
        $mul = ($mul == 'M' ? 1048576 : ($mul == 'K' ? 1024 : ($mul == 'G' ? 1073741824 : 1)));
        if ($files['size'] >= $mul) {
            $json[] = array(
                'code' => 0,
                'msg' => sprintf($this->language->get('error_upload_size'), $post_max)
            );
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return false;
        }

        $file_name = $files['name'];
        $file_name_arr = explode('.', $file_name);
        $file_suffix = $file_name_arr[count($file_name_arr) - 1];
        $file_prefix = implode('.', array_slice($file_name_arr, 0, count($file_name_arr) - 1));
        $download_name = str_replace(' ', '', $file_prefix) . '.' . $this->ms_seller->sellerId() . '.' . $file_suffix;

        $file = $file_name . '.' . token(32);
        $data = array(
            'filename' => $file,
            'mask' => $file_name,
            'download_name' => $download_name
        );

        @move_uploaded_file($files['tmp_name'], DIR_DOWNLOAD . $file);
        $download_id = $this->ms_product->saveUpload($data);
        $json = array(
            'code' => 1,
            'msg' => $this->language->get('success_upload'),
            'id' => $download_id,
            'name' => $download_name
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function attributeComplete()
    {
        $json = array();

        if (isset($this->request->get['filter_name'])) {

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start' => 0,
                'limit' => 5
            );

            $results = $this->ms_product->getAttributes($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'attribute_id' => $result['attribute_id'],
                    'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'attribute_group' => $result['attribute_group']
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // Product variant
    public function variant()
    {
        $this->load->language('catalog/product');

        $data = $this->loadVariantData();

        $data['json'] = array(
            'variants' => array_get($data, 'variants', []),
            'custom_variants' => array_get($data, 'custom_variants', []),
            'variant_groups' => array_get($data, 'variant_groups', []),
            'selected_variants' => array_get($data, 'selected_variants', []),
            'products' => array_get($data, 'products', [])
        );

        $data['base'] = HTTP_SERVER;

        $this->response->setOutput($this->load->view('seller/product_variant', $data));
    }

    public function variant_save()
    {
        if (!$this->ms_seller->isSeller()) {
            $json['status'] = 0;
            $json['message'] = t('error_permission');
            $this->jsonOutput($json);
            return false;
        }

        $json['status'] = 1;
        $json['message'] = '';
        $productModel = $this->loadProductModel();
        if (empty($productModel)) {
            $json['status'] = 0;
            $json['message'] = 'Product not found';
            $this->jsonOutput($json);
        } else {
            $productModel->saveVariants($this->request->post);

            $json['status'] = 1;
            $json['message'] = 'Success';
            $json['data'] = $this->loadVariantData();
            $this->jsonOutput($json);
        }
    }

    public function saveVariants($data)
    {
        $requestProductIds = [];
        $childProductIds = $this->children()->pluck('product_id')->toArray();
        $childProductIds[] = $this->product_id;
        $requestProducts = array_get($data, 'products');

        if (empty($requestProducts)) {
            $removeProductIds = $childProductIds;
        } else {
            foreach ($data['products'] as $item) {
                $productId = $item['product_id'];
                if ($productId && in_array($productId, $childProductIds)) {
                    $requestProductIds[] = $productId;
                    $product = self::find($productId);
                    $product->updateVariant($item);
                } elseif ($productId == 0) {
                    self::createNewVariant($this->product_id, $item);
                }
            }
            $removeProductIds = array_diff($childProductIds, $requestProductIds);
        }
        if ($removeProductIds) {
            self::removeVariant($this->product_id, $removeProductIds);
        }
    }

    private function loadVariantData()
    {
        $productModel = $this->loadProductModel();
        if (empty($productModel)) {
            return false;
        }
        return $productModel->getVariantForAdmin();
    }

    private function loadProductModel()
    {
        $productId = array_get($this->request->get, 'product_id');
        if (empty($productId)) {
            return false;
        }
        $productModel = Models\Product::find($productId);
        if (empty($productModel)) {
            return false;
        }
        return $productModel;
    }

    protected function baseData()
    {
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        return $data;
    }

    protected function editorData()
    {
        return \Seller\Editor::getInstance($this->registry)->getEditorData();
    }

    protected function validateDelete()
    {
        $error = '';
        foreach ($this->request->post['selected'] as $product_id) {
            $seller_product = $this->ms_product->getSellerProduct($product_id);
            if (!$seller_product) {
                $error = $this->language->get('error_product_seller');
            }

            $product = \Models\Product::find($product_id);
            if ($product->isMaster() && count($product->getChildrenIds()) > 1) {
                $error = $this->language->get('error_master_cannot_delete');
            }
        }
        if ($error) {
            $this->error['warning'] = $error;
        }
        return !$this->error;
    }

    protected function validateForm()
    {
        foreach ($this->request->post['product_description'] as $language_id => $value) {
            if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
                $this->error['name'][$language_id] = $this->language->get('error_name');
            }

            if ((utf8_strlen($value['meta_title']) < 1) || (utf8_strlen($value['meta_title']) > 255)) {
                $this->error['meta_title'][$language_id] = $this->language->get('error_meta_title');
            }

        }

        //价格合法
        if (!isset($this->request->post['price']) || $this->request->post['price'] <= 0 || $this->request->post['price'] >= 1000000 ) {
            //$this->error['price'] = $this->language->get('error_price');
        }

        if ((utf8_strlen($this->request->post['sku']) < 3) || (utf8_strlen($this->request->post['sku']) > 64)) {
            $this->error['sku'] = $this->language->get('error_sku');
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    protected function getForm()
    {
        $data = $this->baseData();
        $editor_data = $this->editorData();
        $data['placeholder'] = $editor_data['placeholder'];
        $data['shipping_require'] = $this->config->get('module_multiseller_seller_shipping');
        $data['editor_language'] = $editor_data['editor_language'];
        $data['text_form'] = !isset($this->request->get['product_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        $data['currency_code'] = $this->currency->getSymbolLeft($this->config->get('config_currency'));

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = array();
        }

        if (isset($this->error['meta_title'])) {
            $data['error_meta_title'] = $this->error['meta_title'];
        } else {
            $data['error_meta_title'] = array();
        }

        if (isset($this->error['sku'])) {
            $data['error_sku'] = $this->error['sku'];
        } else {
            $data['error_sku'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_seller'),
            'href' => $this->url->link('seller/account')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('seller/product', $url)
        );

        if (!isset($this->request->get['product_id'])) {
            $approved = 0;
            $data['type'] = 'new';
            $data['action'] = $this->url->link('seller/product/add', $url);
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_add'),
                'href' => $this->url->link('seller/product/add', $url)
            );
        } else {
            $data['type'] = 'update';
            $data['action'] = $this->url->link('seller/product/edit', 'product_id=' . $this->request->get['product_id'] . $url);
            $approved = $this->ms_product->getSellerProductApproved($this->request->get['product_id']);
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_edit'),
                'href' => $this->url->link('seller/product/edit', 'product_id=' . $this->request->get['product_id'] . $url)
            );
        }

        $data['approved'] = $approved;
        $data['validate'] = $this->config->get('module_multiseller_product_validation');

        if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $seller_product = $this->ms_product->getSellerProduct($this->request->get['product_id']);
            if (!$seller_product) {
                $this->response->redirect($this->url->link('seller/product'));
            }
            $product_info = $this->ms_product->getProduct($this->request->get['product_id']);
        }


        if (isset($this->request->post['price'])) {
            $data['price'] = $this->request->post['price'];
        } elseif (!empty($product_info)) {
            $data['price'] = $product_info['price'];
        } else {
            $data['price'] = '';
        }

        if (isset($this->request->post['date_available'])) {
            $data['date_available'] = $this->request->post['date_available'];
        } elseif (!empty($product_info)) {
            $data['date_available'] = (int)strtotime($product_info['date_available']) > 0 ? $product_info['date_available'] : '';
        } else {
            $data['date_available'] = '';
        }

        if (isset($this->request->post['date_until'])) {
            $data['date_until'] = $this->request->post['date_until'];
        } elseif (!empty($product_info)) {
            $data['date_until'] = (int)strtotime($product_info['date_until']) > 0 ? $product_info['date_until'] : '';
        } else {
            $data['date_until'] = '';
        }

        if (isset($this->request->post['quantity'])) {
            $data['quantity'] = $this->request->post['quantity'];
        } elseif (!empty($product_info)) {
            $data['quantity'] = $product_info['quantity'];
        } else {
            $data['quantity'] = '';
        }

        if (isset($this->request->post['minimum'])) {
            $data['minimum'] = $this->request->post['minimum'];
        } elseif (!empty($product_info)) {
            $data['minimum'] = $product_info['minimum'];
        } else {
            $data['minimum'] = 1;
        }

        if (isset($this->request->post['model'])) {
            $data['model'] = $this->request->post['model'];
        } elseif (!empty($product_info)) {
            $data['model'] = $product_info['model'];
        } else {
            $data['model'] = '';
        }

        if (isset($this->request->post['sku'])) {
            $data['sku'] = $this->request->post['sku'];
        } elseif (!empty($product_info)) {
            $data['sku'] = $product_info['sku'];
        } else {
            $data['sku'] = '';
        }

        if (isset($this->request->post['length'])) {
            $data['length'] = $this->request->post['length'];
        } elseif (!empty($product_info)) {
            $data['length'] = $product_info['length'];
        } else {
            $data['length'] = '';
        }

        if (isset($this->request->post['width'])) {
            $data['width'] = $this->request->post['width'];
        } elseif (!empty($product_info)) {
            $data['width'] = $product_info['width'];
        } else {
            $data['width'] = '';
        }

        if (isset($this->request->post['height'])) {
            $data['height'] = $this->request->post['height'];
        } elseif (!empty($product_info)) {
            $data['height'] = $product_info['height'];
        } else {
            $data['height'] = '';
        }

        if (isset($this->request->post['length_class_id'])) {
            $data['length_class_id'] = $this->request->post['length_class_id'];
        } elseif (!empty($product_info)) {
            $data['length_class_id'] = $product_info['length_class_id'];
        } else {
            $data['length_class_id'] = $this->config->get('config_length_class_id');
        }

        if (isset($this->request->post['weight'])) {
            $data['weight'] = $this->request->post['weight'];
        } elseif (!empty($product_info)) {
            $data['weight'] = $product_info['weight'];
        } else {
            $data['weight'] = '';
        }

        if (isset($this->request->post['weight_class_id'])) {
            $data['weight_class_id'] = $this->request->post['weight_class_id'];
        } elseif (!empty($product_info)) {
            $data['weight_class_id'] = $product_info['weight_class_id'];
        } else {
            $data['weight_class_id'] = $this->config->get('config_weight_class_id');
        }

        if (isset($this->request->post['shipping'])) {
            $data['shipping'] = $this->request->post['shipping'];
        } elseif (!empty($product_info)) {
            $data['shipping'] = $product_info['shipping'];
        } else {
            $data['shipping'] = 1;
        }

        if (isset($this->request->post['manufacturer_id'])) {
            $data['manufacturer_id'] = $this->request->post['manufacturer_id'];
        } elseif (!empty($product_info)) {
            $data['manufacturer_id'] = $product_info['manufacturer_id'];
        } else {
            $data['manufacturer_id'] = 0;
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($product_info)) {
            $data['status'] = $product_info['status'];
        } else {
            $data['status'] = true;
        }

        if (isset($this->request->post['subtract'])) {
            $data['subtract'] = $this->request->post['subtract'];
        } elseif (!empty($product_info)) {
            $data['subtract'] = $product_info['subtract'];
        } else {
            $data['subtract'] = 1;
        }

        if (isset($this->request->post['manufacturer'])) {
            $data['manufacturer'] = $this->request->post['manufacturer'];
        } elseif (!empty($product_info)) {
            $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

            if ($manufacturer_info) {
                $data['manufacturer'] = $manufacturer_info['name'];
            } else {
                $data['manufacturer'] = '';
            }
        } else {
            $data['manufacturer'] = '';
        }

        // Description
        if (isset($this->request->post['product_description'])) {
            $data['product_description'] = $this->request->post['product_description'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_description'] = $this->ms_product->getProductDescriptions($this->request->get['product_id']);
        } else {
            $data['product_description'] = array();
        }

        if (isset($this->request->post['stock_status_id'])) {
            $data['stock_status_id'] = $this->request->post['stock_status_id'];
        } elseif (!empty($product_info)) {
            $data['stock_status_id'] = $product_info['stock_status_id'];
        } else {
            $data['stock_status_id'] = 0;
        }

        // Categories
        if (isset($this->request->post['product_category'])) {
            $categories = $this->request->post['product_category'];
        } elseif (isset($this->request->get['product_id'])) {
            $categories = $this->ms_product->getProductCategories($this->request->get['product_id']);
        } else {
            $categories = array();
        }
        $data['product_categories'] = array();
        foreach ($categories as $category_id) {
            $category_info = $this->ms_product->getCategory($category_id);

            if ($category_info) {
                $data['product_categories'][] = array(
                    'category_id' => $category_info['category_id'],
                    'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                );
            }
        }

        // Related
        if (isset($this->request->post['product_related'])) {
            $products = $this->request->post['product_related'];
        } elseif (isset($this->request->get['product_id'])) {
            $products = $this->ms_product->getProductRelated($this->request->get['product_id']);
        } else {
            $products = array();
        }
        $data['product_relateds'] = array();
        foreach ($products as $product_id) {
            $related_info = $this->ms_product->getProduct($product_id);

            if ($related_info) {
                $data['product_relateds'][] = array(
                    'product_id' => $related_info['product_id'],
                    'name' => $related_info['name']
                );
            }
        }

        // Attributes
        if (isset($this->request->post['product_attribute'])) {
            $product_attributes = $this->request->post['product_attribute'];
        } elseif (isset($this->request->get['product_id'])) {
            $product_attributes = $this->ms_product->getProductAttributes($this->request->get['product_id']);
        } else {
            $product_attributes = array();
        }

        $data['product_attributes'] = array();
        //dd($product_attributes);
        foreach ($product_attributes as $product_attribute) {
            $attribute_info = $this->ms_product->getAttribute($product_attribute['attribute_id']);
            if ($attribute_info) {
                $data['product_attributes'][] = array(
                    'attribute_id' => $product_attribute['attribute_id'],
                    'name' => $attribute_info['name'],
                    'product_attribute_description' => $product_attribute['product_attribute_description']
                );
            }
        }

        // Discount
        if (isset($this->request->post['product_discount'])) {
            $product_discounts = $this->request->post['product_discount'];
        } elseif (isset($this->request->get['product_id'])) {
            $product_discounts = $this->ms_product->getProductDiscounts($this->request->get['product_id']);
        } else {
            $product_discounts = array();
        }

        $data['product_discounts'] = array();

        foreach ($product_discounts as $product_discount) {
            $data['product_discounts'][] = array(
                'customer_group_id' => $product_discount['customer_group_id'],
                'quantity' => $product_discount['quantity'],
                'priority' => $product_discount['priority'],
                'price' => $product_discount['price'],
                'date_start' => ($product_discount['date_start'] != '0000-00-00') ? $product_discount['date_start'] : '',
                'date_end' => ($product_discount['date_end'] != '0000-00-00') ? $product_discount['date_end'] : ''
            );
        }

        // Special
        if (isset($this->request->post['product_special'])) {
            $product_specials = $this->request->post['product_special'];
        } elseif (isset($this->request->get['product_id'])) {
            $product_specials = $this->ms_product->getProductSpecials($this->request->get['product_id']);
        } else {
            $product_specials = array();
        }

        $data['product_specials'] = array();

        foreach ($product_specials as $product_special) {
            $data['product_specials'][] = array(
                'customer_group_id' => $product_special['customer_group_id'],
                'priority' => $product_special['priority'],
                'price' => $product_special['price'],
                'date_start' => ($product_special['date_start'] != '0000-00-00') ? $product_special['date_start'] : '',
                'date_end' => ($product_special['date_end'] != '0000-00-00') ? $product_special['date_end'] : ''
            );
        }

        // Downloads
        if (isset($this->request->post['product_download'])) {
            $product_downloads = $this->request->post['product_download'];
        } elseif (isset($this->request->get['product_id'])) {
            $product_downloads = $this->ms_product->getProductDownloads($this->request->get['product_id']);
        } else {
            $product_downloads = array();
        }

        $data['product_downloads'] = array();

        foreach ($product_downloads as $download_id) {
            $download_info = $this->ms_product->getDownload($download_id);
            if ($download_info) {
                $data['product_downloads'][] = array(
                    'download_id' => $download_info['download_id'],
                    'name' => $download_info['name']
                );
            }
        }

        // Image
        if (isset($this->request->post['image'])) {
            $data['image'] = $this->request->post['image'];
        } elseif (!empty($product_info)) {
            $data['image'] = $product_info['image'];
        } else {
            $data['image'] = '';
        }

        if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
        } elseif (!empty($product_info) && is_file(DIR_IMAGE . $product_info['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($product_info['image'], 100, 100);
        } else {
            $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        }

        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        // Images
        if (isset($this->request->post['product_image'])) {
            $product_images = $this->request->post['product_image'];
        } elseif (isset($this->request->get['product_id'])) {
            $product_images = $this->model_catalog_product->getProductImages($this->request->get['product_id']);
        } else {
            $product_images = array();
        }

        $data['product_images'] = array();

        foreach ($product_images as $product_image) {
            if (is_file(DIR_IMAGE . $product_image['image'])) {
                $image = $product_image['image'];
                $thumb = $product_image['image'];
            } else {
                $image = '';
                $thumb = 'no_image.png';
            }

            $data['product_images'][] = array(
                'image' => $image,
                'thumb' => $this->model_tool_image->resize($thumb, 100, 100),
                'sort_order' => $product_image['sort_order']
            );
        }

        $data['cancel'] = $this->url->link('seller/product', $url);

        $data['customer_groups'] = $this->model_account_customer_group->getCustomerGroups();

        $data['tax_classes'] = $this->ms_product->getTaxClasses();

        $data['stock_statuses'] = $this->ms_product->getStockStatuses();
        $data['languages'] = $this->model_localisation_language->getLanguages();
        $data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();
        $data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

        $this->response->setOutput($this->load->view('seller/product_form', $data));
    }

    protected function getList()
    {
        $data = $this->baseData();

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['filter_model'])) {
            $filter_model = $this->request->get['filter_model'];
        } else {
            $filter_model = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_show_variant'])) {
            $filter_show_variant = $this->request->get['filter_show_variant'];
        } else {
            $filter_show_variant = 0;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'p.date_modified';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_show_variant'])) {
            $url .= '&filter_show_variant=' . $this->request->get['filter_show_variant'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_seller'),
            'href' => $this->url->link('seller/account')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('seller/product', $url)
        );

        $data['products'] = array();

        $filter_data = array(
            'filter_name' => $filter_name,
            'filter_model' => $filter_model,
            'filter_status' => $filter_status,
            'filter_show_variant' => $filter_show_variant,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $data['add'] = $this->url->link('seller/product/add', $url);
        $data['delete'] = $this->url->link('seller/product/delete', $url);

        $product_total = $this->ms_product->getTotalProducts($filter_data);
        $results = $this->ms_product->getProducts($filter_data);
        foreach ($results as $result) {
            if (is_file(DIR_IMAGE . $result['image'])) {
                $image = $this->model_tool_image->resize($result['image'], 40, 40);
            } else {
                $image = $this->model_tool_image->resize('no_image.png', 40, 40);
            }

            $special = false;

            $product_specials = $this->ms_product->getProductSpecials($result['product_id']);

            foreach ($product_specials as $product_special) {
                if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
                    $special = $this->currency->format($product_special['price'], $this->config->get('config_currency'));

                    break;
                }
            }

            $data['products'][] = array(
                'product_id' => $result['product_id'],
                'image' => $image,
                'name' => $result['name'],
                'model' => $result['model'],
                'original_price' => $result['price'],
                'price' => $this->currency->format($result['price'], $this->config->get('config_currency')),
                'special' => $special,
                'quantity' => $result['quantity'],
                'status' => $result['status'],
                'view' => $this->url->link('product/product', "product_id={$result['product_id']}"),
                'edit' => $this->url->link('seller/product/edit', 'product_id=' . $result['product_id'] . $url)
            );
        }

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } elseif (isset($this->request->get['selected'])) {
            $data['selected'] = $this->request->get['selected'];
        } else {
            $data['selected'] = array();
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_show_variant'])) {
            $url .= '&filter_show_variant=' . $this->request->get['filter_show_variant'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_id'] = $this->url->link('seller/product', 'sort=p.product_id' . $url);
        $data['sort_name'] = $this->url->link('seller/product', 'sort=pd.name' . $url);
        $data['sort_model'] = $this->url->link('seller/product', 'sort=p.model' . $url);
        $data['sort_status'] = $this->url->link('seller/product', '&sort=p.status' . $url);
        $data['sort_order'] = $this->url->link('seller/product', 'sort=p.sort_order' . $url);
        $data['sort_price'] = $this->url->link('seller/product', 'sort=p.price' . $url);

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_show_variant'])) {
            $url .= '&filter_show_variant=' . $this->request->get['filter_show_variant'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $product_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('seller/product', $url . '&page={page}');

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
        $data['filter_model'] = $filter_model;
        $data['filter_status'] = $filter_status;
        $data['filter_show_variant'] = $filter_show_variant;
        $data['page'] = $page;
        $data['sort'] = $sort;
        $data['order'] = $order;

        $this->response->setOutput($this->load->view('seller/product_list', $data));
    }

    public function thumb() {
        $image = array_get($this->request->get, 'image');
        $image = rawurldecode($this->request->get['image']);
        $image = image_resize($image, 100, 100);
        $this->response->redirect($image);
    }
}