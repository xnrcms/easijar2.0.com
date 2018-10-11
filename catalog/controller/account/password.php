<?php
class ControllerAccountPassword extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/password');

			$this->response->redirect($this->url->link('account/login'));
		}

		$this->load->language('account/password');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->load->model('account/customer');

			$this->model_account_customer->editPassword($this->customer->getId(), $this->request->post['password']);

			$this->session->data['success'] = $this->language->get('text_success');

			$backlink 			= isset($this->request->post['backlink']) ? $this->request->post['backlink'] : $this->url->link('account/account');
			
			$this->response->redirect($backlink);
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/password')
		);

		if (isset($this->error['old_password'])) {
			$data['error_old_password'] = $this->error['old_password'];
		} else {
			$data['error_old_password'] = '';
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		if (isset($this->error['confirm'])) {
			$data['error_confirm'] = $this->error['confirm'];
		} else {
			$data['error_confirm'] = '';
		}

		$data['action'] = $this->url->link('account/password');

		if (isset($this->request->post['old_password'])) {
			$data['old_password'] = $this->request->post['old_password'];
		} else {
			$data['old_password'] = '';
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->post['confirm'])) {
			$data['confirm'] = $this->request->post['confirm'];
		} else {
			$data['confirm'] = '';
		}

		$ptype 			= isset(($this->request->get['ptype'])) ? intval($this->request->get['ptype']) : 0;
		$backlink 		= $ptype == 0 ? $this->url->link('account/account') : $this->url->link('seller/account');

		$data['back'] 	= $backlink;

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/password', $data));
	}

	protected function validate() {
		//检验原始密码是否正确
		$this->load->model('account/customer');
		$customer 		= $this->model_account_customer->getCustomer($this->customer->getId(),$this->request->post['old_password']);
		$password 		= array_get($customer,'password');
		$passok 		= (!empty($password) && password_verify($this->request->post['old_password'], $password)) ? true : false;

		if (!$passok) {
			$this->error['old_password'] = $this->language->get('error_old_password');return !$this->error;
		}

		if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
			$this->error['password'] = $this->language->get('error_password');return !$this->error;
		}

		if ($this->request->post['confirm'] != $this->request->post['password']) {
			$this->error['confirm'] = $this->language->get('error_confirm');return !$this->error;
		}

		return true;
	}
}