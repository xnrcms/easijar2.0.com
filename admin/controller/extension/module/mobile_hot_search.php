<?php
class ControllerExtensionModuleMobileHotSearch extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/mobile_hot_search');
		$this->document->setTitle(t('heading_title'));

		$this->load->model('setting/setting');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_mobile_hot_search', $this->request->post);
			$this->session->data['success'] = t('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'type=module'));
		}

		$data['error'] = $this->error;

		$breadcrumbs = new Breadcrumb();
		$breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
		$breadcrumbs->add(t('text_module'), $this->url->link('marketplace/extension', 'type=module'));
		$breadcrumbs->add(t('heading_title'), $this->url->link('extension/module/mobile_hot_search'));
		$data['breadcrumbs'] = $breadcrumbs->all();

		$data['action'] = $this->url->link('extension/module/mobile_hot_search');
		$data['cancel'] = $this->url->link('marketplace/extension', 'type=module');

		if (isset($this->request->post['module_mobile_hot_search'])) {
			$data['modules'] = $this->request->post['module_mobile_hot_search'];
		} elseif ($this->config->get('module_mobile_hot_search')) {
			$data['modules'] = $this->config->get('module_mobile_hot_search');
		}else{
			$data['modules'] = array();
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/mobile_hot_search', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/mobile_hot_search')) {
			$this->error['warning'] = t('error_permission');
		}

		if ((utf8_strlen($this->request->post['module_mobile_hot_search_title']) < 2) || (utf8_strlen($this->request->post['module_mobile_hot_search_title']) > 64)) {
			$this->error['name'] = t('error_name');
		}
		return !$this->error;
	}
}
?>
