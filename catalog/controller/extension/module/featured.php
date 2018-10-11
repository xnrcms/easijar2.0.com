<?php
class ControllerExtensionModuleFeatured extends Controller {
	public function index($setting) {
		if (empty($setting['product'])) {
			return;
		}

		$this->load->model('catalog/product');
		$this->load->model('catalog/product_pro');

		if (!$setting['limit']) {
			$setting['limit'] = 4;
		}

		$products = $this->model_catalog_product_pro->getProductsByIds($setting['product']);
		if (!$products) {
			return;
		}

		$products = array_slice($products, 0, (int)$setting['limit']);

		$data['products'] = array();
		foreach ($products as $product) {
			$data['products'][] = $this->model_catalog_product->handleSingleProduct($product, $setting['width'], $setting['height']);
		}

		if ($data['products']) {
			$this->load->language('extension/module/featured');
			return $this->load->view('extension/module/featured', $data);
		}
	}
}
