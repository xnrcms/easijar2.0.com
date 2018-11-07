<?php
class ControllerExtensionModuleLatest extends Controller {
	public function index($setting) {
		$this->load->model('catalog/product');
		$this->load->model('catalog/product_pro');

		$filter_data = array(
			'sort'  => 'p.date_added',
			'order' => 'DESC',
			'start' => 0,
			'parent_id' => 0,
			'limit' => $setting['limit']
		);

		$results = $this->model_catalog_product_pro->getProducts($filter_data,false);
		if (!$results) {
			return;
		}

		$this->load->language('extension/module/latest');

		$data['products'] = array();
		foreach ($results as $result) {
			$data['products'][] = $this->model_catalog_product->handleSingleProduct($result, $setting['width'], $setting['height']);
		}
		
		if (isset($setting['api']) && $setting['api']) return $data;

		return $this->load->view('extension/module/latest', $data);
	}
}
