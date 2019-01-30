<?php
class ControllerExtensionModuleSpecial extends Controller {
	public function index($setting) {
		$this->load->model('catalog/product');

		$filter_data = array(
			'sort'  => 'p.date_modified',
			'order' => 'DESC',
			'start' => 0,
			'limit' => $setting['limit'],
			'start' => isset($setting['start']) ? $setting['start'] : 0
		);

		$product_total 	= $this->model_catalog_product->getTotalProductSpecials();
		$results 		= $this->model_catalog_product->getProductSpecials($filter_data);
		if (!$results) {
			return;
		}

		$this->load->language('extension/module/special');

		$data 					= [];
		$data['products'] 		= [];
		$data['product_total'] 	= $product_total;

		foreach ($results as $result) {
			$data['products'][] = $this->model_catalog_product->handleSingleProduct($result, $setting['width'], $setting['height']);
		}

        if (isset($setting['api']) && $setting['api']) return $data;

		return $this->load->view('extension/module/special', $data);
	}
}
