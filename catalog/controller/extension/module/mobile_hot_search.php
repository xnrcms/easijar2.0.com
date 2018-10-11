<?php
class ControllerExtensionModuleMobileHotSearch extends Controller {
	public function index($setting) {
		if (!config('is_mobile')) {
			return;
		}
		if(!config('module_mobile_hot_search_status') || !config('module_mobile_hot_search')) {
			return;
		}

		$hot_search_keywords = config('module_mobile_hot_search');
		$sort_order = array();
		foreach ($hot_search_keywords as $key => $value) {
			$sort_order[$key] = array_get($value, 'sort_order', 0);
		}
		array_multisort($sort_order, SORT_ASC, $hot_search_keywords);

		$data['hot_search_keywords'] = [];
		foreach($hot_search_keywords as $keyword) {
			if($keyword['status'] && trim($keyword['keyword'])) {
				$data['hot_search_keywords'][] = $keyword;
			}
		}

		if ($data['hot_search_keywords']) {
			return $this->load->view('extension/module/mobile_hot_search', $data);
		}
	}
}
