<?php
class ControllerExtensionModuleBlogLatest extends Controller
{
	private $error = array();

	public function index()
	{
		$this->load->language('extension/module/blog_latest');
		$this->document->setTitle(t('heading_title'));

		$this->load->model('setting/setting');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_blog_latest', $this->request->post);
			$this->session->data['success'] = t('text_success');
			$this->response->redirect($this->url->link('marketplace/extension','type=module'));
		}

		$data['error'] = $this->error;

		$breadcrumbs = new Breadcrumb();
		$breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
		$breadcrumbs->add(t('text_extension'), $this->url->link('marketplace/extension', 'type=module'));
		$breadcrumbs->add(t('heading_title'), $this->url->link('extension/module/blog_latest'));
		$data['breadcrumbs'] = $breadcrumbs->all();

		$data['action'] = $this->url->link('extension/module/blog_latest');
		$data['cancel'] = $this->url->link('marketplace/extension','type=module');

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/blog_latest', $data));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/blog_latest')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['module_blog_latest_title'] as $language_id => $value) {
			if ((utf8_strlen($value) < 1) || (utf8_strlen($value) > 128)) {
				$this->error['title'][$language_id] = $this->language->get('error_title');
			}
		}

		if ((int)$this->request->post['module_blog_latest_limit'] <= 0) {
			$this->error['limit'] = $this->language->get('error_limit');
		}

		return !$this->error;
	}
}
