<?php
class ControllerCommonMenu extends Controller {
	public function index() {
		$this->load->language('common/menu');

		// Menu
		$this->load->model('catalog/category');

		$this->load->model('catalog/product');
		$this->load->model('catalog/product_pro');

		$data['categories'] = array();
		$data['home'] = $this->url->link('common/home');

		$categories = $this->model_catalog_category->getCategories(0);

		$totals = $this->model_catalog_product_pro->getTotalProductsFromAllCategories();
		foreach ($categories as $category) {
			if ($category['top']) {
				// Level 2
				$children_data = array();

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
				    $total = array_get($totals, $child['category_id'], 0);
					$children_data[] = array(
						'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $total . ')' : ''),
						'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
					);
				}

				// Level 1
				$data['categories'][] = array(
					'name'     => $category['name'],
					'children' => $children_data,
					'column'   => $category['column'] ? $category['column'] : 1,
					'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
				);
			}
		}

		// BLOG
		if (config('blog_status')) {
			$categories = \Models\Blog\Category::where('status', 1)->get();
			$children_data = [];
			if ($categories) {
				foreach ($categories as $category) {
					$children_data[] = array(
						'name'  => $category->localizedDescription()->name,
						'href'  => $category->href('show')
					);
				}
			}

			$data['categories'][] = array(
				'name'     => config('blog_menu_name.' . current_language_id(), 'BLOG'),
				'children' => $children_data,
				'column'   => 1,
				'href'     => $this->url->link('blog/home')
			);
		}

		return $this->load->view('common/menu', $data);
	}
}
