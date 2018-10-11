<?php
class ControllerProductLatest extends Controller {
	public function index() {
		$this->load->language('product/latest');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$page = 1;
		$limit = config('theme_' . config('config_theme') . '_product_limit');

		$this->document->setTitle(t('heading_title'));
		$this->document->setUrl(($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI']);
		$this->document->setImage($this->model_tool_image->resize(config('config_image'), 600, 315));

		$breadcrumb = new Breadcrumb();
		$breadcrumb->add(t('text_home'), $this->url->link('common/home'));
		$breadcrumb->add(t('heading_title'), $this->url->link('product/latest'));
		$data['breadcrumbs'] = $breadcrumb->all();

		$filter_data = array(
			'sort' => 'p.date_added',
			'order' => 'DESC',
			'parent_id' => 0,
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		);

		$results = $this->model_catalog_product->getProducts($filter_data);

		$data['products'] = array();
		foreach ($results as $result) {
			$data['products'][] = $this->model_catalog_product->handleSingleProduct($result, config('theme_' . config('config_theme') . '_image_product_width'), config('theme_' . config('config_theme') . '_image_product_height'));
		}

		if (config('is_mobile')) {
			$data['style'] = 'grid';
		} else {
			if (array_get($this->request->get, 'style')) {
				$this->session->data['display_style'] = (array_get($this->request->get, 'style') == 'list' ? 'list' : 'grid');
			}
			$data['style'] = $this->session->get('display_style', 'grid');

			$data['style_grid'] = $this->url->link('product/latest', '&style=grid');
			$data['style_list'] = $this->url->link('product/latest', '&style=list');
		}

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/latest', $data));
	}
}
