<?php
class ControllerMultisellerProduct extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('multiseller/product');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/product');

        $this->getList();
    }

    public function delete() {
        $this->load->language('multiseller/product');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/product');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $product_id) {
                $this->model_catalog_product->deleteProduct($product_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

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

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . $url));
        }

        $this->getList();
    }

    protected function getList() {
        $this->document->addScript('view/javascript/layer/layer.js');
        $this->document->addScript('view/javascript/jquery/switch/bootstrap-switch.min.js');

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

        if (isset($this->request->get['filter_price'])) {
            $filter_price = $this->request->get['filter_price'];
        } else {
            $filter_price = '';
        }

        if (isset($this->request->get['filter_quantity'])) {
            $filter_quantity = $this->request->get['filter_quantity'];
        } else {
            $filter_quantity = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_category'])) {
            $filter_category = $this->request->get['filter_category'];
        } else {
            $filter_category = '';
        }

        if (isset($this->request->get['filter_seller'])) {
            $filter_seller = $this->request->get['filter_seller'];
        } else {
            $filter_seller = '';
        }

        if (isset($this->request->get['filter_image'])) {
            $filter_image = $this->request->get['filter_image'];
        } else {
            $filter_image = '';
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

        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
        }

        if (isset($this->request->get['filter_seller'])) {
            $url .= '&filter_seller=' . $this->request->get['filter_seller'];
        }

        if (isset($this->request->get['filter_image'])) {
            $url .= '&filter_image=' . $this->request->get['filter_image'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['delete'] = $this->url->link('multiseller/product/delete', 'user_token=' . $this->session->data['user_token'] . $url);

        $this->load->model('catalog/category');
        $data['categories'] = array();

        $filter_data = array(
            'sort'  => 'name',
            'order' => 'ASC',
        );

        $results = $this->model_catalog_category->getCategories($filter_data);

        foreach ($results as $result) {
            $data['categories'][] = array(
                'category_id' => $result['category_id'],
                'name'        => $result['name'],
            );
        }

        $data['products'] = array();

        $filter_data = array(
            'filter_name'     => $filter_name,
            'filter_model'    => $filter_model,
            'filter_price'    => $filter_price,
            'filter_quantity' => $filter_quantity,
            'filter_category' => $filter_category,
            'filter_seller'   => $filter_seller,
            'filter_status'   => $filter_status,
            'filter_image'    => $filter_image,
            'sort'            => $sort,
            'order'           => $order,
            'start'           => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'           => $this->config->get('config_limit_admin')
        );

        $this->load->model('tool/image');
        $this->load->model('multiseller/product');

        $product_total = $this->model_catalog_product->getTotalProducts($filter_data);

        $results = $this->model_catalog_product->getProducts($filter_data);

        foreach ($results as $result) {
            if (is_file(DIR_IMAGE . $result['image'])) {
                $image = $this->model_tool_image->resize($result['image'], 40, 40);
            } else {
                $image = $this->model_tool_image->resize('no_image.png', 40, 40);
            }

            $special = false;

            $product_specials = $this->model_catalog_product->getProductSpecials($result['product_id']);

            foreach ($product_specials  as $product_special) {
                if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
                    $special = $this->currency->format($product_special['price'], $this->config->get('config_currency'));

                    break;
                }
            }

            $seller_info = $this->model_multiseller_product->getSeller($result['product_id']);

			if ($seller_info && !$seller_info['approved']) {
				$approve = $this->url->link('multiseller/product/approve', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url);
			} else {
				$approve = '';
			}

            $data['products'][] = array(
                'product_id' => $result['product_id'],
                'image'      => $image,
                'seller'     => $seller_info ? $seller_info['store_name'] : $this->language->get('text_platform'),
                'sold'       => $seller_info ? $seller_info['number_sold'] : $this->language->get('text_nothing'),
                'name'       => $result['name'],
                'model'      => $result['model'],
                'original_price'      => $result['price'], // 未格式化的价格
                'price'      => $this->currency->format($result['price'], $this->config->get('config_currency')),
                'special'    => $special,
                'quantity'   => $result['quantity'],
                'status'     => $result['status'],
                'approve'     => $approve,
                'view'       => $this->front_url->link('product/product', "product_id={$result['product_id']}"),
                'edit'       => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url)
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

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

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } elseif (isset($this->request->get['selected'])){
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

        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
        }

        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
        }

        if (isset($this->request->get['filter_seller'])) {
            $url .= '&filter_seller=' . $this->request->get['filter_seller'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_image'])) {
            $url .= '&filter_image=' . $this->request->get['filter_image'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_id'] = $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.product_id' . $url);
        $data['sort_name'] = $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url);
        $data['sort_model'] = $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url);
        $data['sort_price'] = $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url);
        $data['sort_quantity'] = $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url);
        $data['sort_status'] = $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url);
        $data['sort_order'] = $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url);

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

        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
        }

        if (isset($this->request->get['filter_seller'])) {
            $url .= '&filter_seller=' . $this->request->get['filter_seller'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_image'])) {
            $url .= '&filter_image=' . $this->request->get['filter_image'];
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
        $pagination->url = $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
        $data['filter_model'] = $filter_model;
        $data['filter_price'] = $filter_price;
        $data['filter_quantity'] = $filter_quantity;
        $data['filter_status'] = $filter_status;
        $data['filter_image'] = $filter_image;
        $data['filter_category'] = $filter_category;
        $data['filter_seller'] = $filter_seller;

        $data['page'] = $page;
        $data['sort'] = $sort;
        $data['order'] = $order;

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('multiseller/product_list', $data));
    }

	public function approve() {
		$this->load->language('multiseller/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/product');
		$this->load->model('catalog/product');

		if (isset($this->request->get['product_id']) && $this->validateApprove()) {
			$this->model_multiseller_product->approve($this->request->get['product_id']);

			$this->session->data['success'] = $this->language->get('text_success');

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

            if (isset($this->request->get['filter_category'])) {
                $url .= '&filter_category=' . $this->request->get['filter_category'];
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_image'])) {
                $url .= '&filter_image=' . $this->request->get['filter_image'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

			$this->response->redirect($this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getList();
	}

	protected function validateApprove() {
		if (!$this->user->hasPermission('modify', 'multiseller/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'multiseller/product')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            foreach ($this->request->post['selected'] as $product_id) {
                if (\Models\Product::find($product_id)->isMaster()) {
                    $this->error['warning'] = $this->language->get('error_master_cannot_delete');
                }
            }
        }

        return !$this->error;
    }

    // Quick edit
    public function edit_status() {
        $json = array();
        if (!$this->user->hasPermission('modify', 'multiseller/product')) {
            $json['status'] = 0;
            $json['message'] = t('error_permission');
            $json['data'] = null;

            $this->jsonOutput($json);
            return;
        }

        if ((int)array_get($this->request->get, 'product_id') < 1) {
            $json['status'] = 0;
            $json['message'] = t('error_product_id_required');
            $json['data'] = null;

            $this->jsonOutput($json);
            return;
        }

        if (array_get($this->request->get, 'status') === null) {
            $json['status'] = 0;
            $json['message'] = t('error_status_required');
            $json['data'] = null;

            $this->jsonOutput($json);
            return;
        }

        $product_id = (int)$this->request->get['product_id'];
        $status = (int)$this->request->get['status'] > 0 ? 1 : 0;

        $this->load->model('catalog/product');
        $this->model_catalog_product->editProductStatus($product_id, $status);

        $json['status'] = 1;
        $json['message'] = t('text_success');
        $json['data'] = null;

        $this->jsonOutput($json);
    }
}
