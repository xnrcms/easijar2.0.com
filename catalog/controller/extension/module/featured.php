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

		$product_ids 	= isset($setting['product']) ? $setting['product'] : [];
		$product_total 	= count($product_ids);
		$pids 			= [];

		if ($product_total > 0) {
			$start 		= isset($setting['start']) ? $setting['start'] : 0;
			$limit 		= isset($setting['limit']) ? $setting['limit'] : 20;
			for ($i=$start; $i < ($start + $limit); $i++) { 
				if (isset($product_ids[$i])) {
					$pids[] = $product_ids[$i];
				}
			}
		}

		$setting['product'] 	= $pids;
		$products 				= $this->model_catalog_product_pro->getProductsByIds($setting);
		
		if (!$products) return [];

		//$products 			= array_slice($products, 0, (int)$setting['limit']);
		$data['products'] 	= [];
		
		foreach ($products as $product) {
			$data['products'][] = $this->model_catalog_product->handleSingleProduct($product, $setting['width'], $setting['height']);
		}

		if ($data['products']) {

			if (isset($setting['api']) && $setting['api']) return $data;

			$this->load->language('extension/module/featured');
			return $this->load->view('extension/module/featured', $data);
		}
	}
}
