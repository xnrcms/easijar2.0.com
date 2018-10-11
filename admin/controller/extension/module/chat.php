<?php
class ControllerExtensionModuleChat extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/chat');
		$this->document->setTitle(t('heading_title'));

		$this->load->model('setting/setting');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_chat', $this->request->post);
			$this->session->data['success'] = t('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'type=module'));
		}

		$data['error'] = $this->error;

		$breadcrumbs = new Breadcrumb();
		$breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
		$breadcrumbs->add(t('text_extension'), $this->url->link('marketplace/extension', 'type=module'));
		$breadcrumbs->add(t('heading_title'), $this->url->link('extension/module/chat'));
		$data['breadcrumbs'] = $breadcrumbs->all();

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['action'] = $this->url->link('extension/module/chat');
		$data['cancel'] = $this->url->link('marketplace/extension' . 'type=module');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/chat', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/chat')) {
			$this->error['warning'] = t('error_permission');
		}

		foreach ($this->request->post['module_chat_titles'] as $language_id => $value) {
		    if ((utf8_strlen($value) < 2) || (utf8_strlen($value) > 20)) {
		        $this->error['titles'][$language_id] = t('error_title');
		    }
		}

		return !$this->error;
	}
}
