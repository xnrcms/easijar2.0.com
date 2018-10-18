<?php
class ControllerExtensionModuleCategory extends Controller {
	public function index() {
		$this->load->language('extension/module/category');

		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = array();
		}

		if (isset($parts[0])) {
			$data['category_id'] = $parts[0];
		} else {
			$data['category_id'] = 0;
		}

		if (isset($parts[1])) {
			$data['child_id'] = $parts[1];
		} else {
			$data['child_id'] = 0;
		}

		$data['grand_child_id'] = isset($parts[2]) ? $parts[2] : 0;

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$data['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			$children_data = array();

			$children = $this->model_catalog_category->getCategories($category['category_id']);

			foreach($children as $child) {
				$grand_children = $this->model_catalog_category->getCategories($child['category_id']);
				$grand_children_data = [];
				foreach ($grand_children as $grand_child) {
					$filter_data = array('filter_category_id' => $grand_child['category_id'], 'filter_sub_category' => true,'parent_id'=>0);
					$grand_children_data[] = array(
						'category_id' => $grand_child['category_id'],
						'name' => $grand_child['name'] . (config('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
						'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $grand_child['category_id'])
					);
				}

				$filter_data = array('filter_category_id' => $child['category_id'], 'filter_sub_category' => true,'parent_id'=>0);

				$children_data[] = array(
					'category_id' => $child['category_id'],
					'name' => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
					'grand_children' => $grand_children_data,
					'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
				);
			}

			$filter_data = array(
				'filter_category_id'  => $category['category_id'],
				'filter_sub_category' => true,
				'parent_id'=>0
			);

			$data['categories'][] = array(
				'category_id' => $category['category_id'],
				'name'        => $category['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
				'children'    => $children_data,
				'href'        => $this->url->link('product/category', 'path=' . $category['category_id'])
			);
		}

		if (!$data['categories']) {
			return;
		}

		return $this->load->view('extension/module/category', $data);
	}
}
