<?php
class ControllerSellerRegister extends Controller {
	private $error = array();

	public function index() {
		if ($this->customer->isLogged()) {
		    if ($this->customer->isSeller()) {
			    $this->response->redirect($this->url->link('seller/account'));
            } else {
			    $this->response->redirect($this->url->link('seller/add'));
            }
		}

		$this->load->language('seller/register');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

		$this->load->model('account/customer');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			unset($this->session->data['guest']);

			$customer_id = $this->model_account_customer->addCustomer($this->request->post);

			$this->load->model('multiseller/seller');

			$this->model_multiseller_seller->addSeller($customer_id, $this->request->post);

			if (!$this->config->get('module_multiseller_seller_approval')) {
                // Clear any previous login attempts in not registered.
                $this->model_account_customer->deleteLoginAttempts($customer_id);

                $this->customer->login($this->request->post['email'], $this->request->post['password']);

                // Log the IP info
                $this->model_account_customer->addLogin($this->customer->getId(), $this->request->server['REMOTE_ADDR']);
            }

			$this->response->redirect($this->url->link('seller/success'));
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
			'text' => $this->language->get('text_register'),
			'href' => $this->url->link('seller/register')
		);

		$this->load->language('common/footer', 'footer');
		if ($powered = config('theme_' . config('config_theme') . '_copyright.' . current_language_id())) {
		    $data['powered'] = html_entity_decode($powered, ENT_QUOTES, 'UTF-8');
		} else {
		    $data['powered'] = sprintf($this->language->get('footer')->get('text_powered'), $this->config->get('config_name'), date('Y', time()));
		}

		$data['text_account_already'] = sprintf($this->language->get('text_account_already'), $this->url->link('seller/login'));

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['fullname'])) {
			$data['error_fullname'] = $this->error['fullname'];
		} else {
			$data['error_fullname'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = '';
		}

		if (isset($this->error['company'])) {
			$data['error_company'] = $this->error['company'];
		} else {
			$data['error_company'] = '';
		}

		if (isset($this->error['store_name'])) {
			$data['error_store_name'] = $this->error['store_name'];
		} else {
			$data['error_store_name'] = '';
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

		if (isset($this->error['alipay'])) {
			$data['error_alipay'] = $this->error['alipay'];
		} else {
			$data['error_alipay'] = '';
		}

		if (isset($this->error['country'])) {
			$data['error_country'] = $this->error['country'];
		} else {
			$data['error_country'] = '';
		}

		if (isset($this->error['zone'])) {
			$data['error_zone'] = $this->error['zone'];
		} else {
			$data['error_zone'] = '';
		}

		if (isset($this->error['city'])) {
			$data['error_city'] = $this->error['city'];
		} else {
			$data['error_city'] = '';
		}

		if (isset($this->error['county'])) {
			$data['error_county'] = $this->error['county'];
		} else {
			$data['error_county'] = '';
		}

		$data['action'] = $this->url->link('seller/register');

		if (isset($this->request->post['fullname'])) {
			$data['fullname'] = $this->request->post['fullname'];
		} else {
			$data['fullname'] = '';
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} else {
			$data['telephone'] = '';
		}

		if (isset($this->request->post['store_name'])) {
			$data['store_name'] = $this->request->post['store_name'];
		} else {
			$data['store_name'] = '';
		}

		if (isset($this->request->post['company'])) {
			$data['company'] = $this->request->post['company'];
		} else {
			$data['company'] = '';
		}

		if (isset($this->request->post['alipay'])) {
			$data['alipay'] = $this->request->post['alipay'];
		} else {
			$data['alipay'] = '';
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

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		if (isset($this->request->post['country_id'])) {
			$data['country_id'] = $this->request->post['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}

		if (isset($this->request->post['zone_id'])) {
			$data['zone_id'] = $this->request->post['zone_id'];
		} else {
			$data['zone_id'] = $this->config->get('config_zone_id');
		}

		if (isset($this->request->post['city_id'])) {
			$data['city_id'] = $this->request->post['city_id'];
		} else {
			$data['city_id'] = '';
		}

		if (isset($this->request->post['county_id'])) {
			$data['county_id'] = $this->request->post['county_id'];
		} else {
			$data['county_id'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		if ($this->config->get('module_multiseller_seller_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('module_multiseller_seller_id'));
			$information_info2 = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

			if ($information_info) {
				$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('module_multiseller_seller_id')), $information_info['title'], $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id')), $information_info2['title']);
			} else {
				$data['text_agree'] = '';
			}
		} else {
			$data['text_agree'] = '';
		}

		if (isset($this->request->post['agree'])) {
			$data['agree'] = $this->request->post['agree'];
		} else {
			$data['agree'] = false;
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('seller/register', $data));
	}

	protected function validate() {
		if ((utf8_strlen(trim($this->request->post['fullname'])) < 1) || (utf8_strlen(trim($this->request->post['fullname'])) > 32)) {
			$this->error['fullname'] = $this->language->get('error_fullname');
		}

		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ($this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
			$this->error['warning'] = $this->language->get('error_exists');
		}

		if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		if ((utf8_strlen($this->request->post['store_name']) < 3) || (utf8_strlen($this->request->post['store_name']) > 32)) {
			$this->error['store_name'] = $this->language->get('error_store_name');
		}

		if ((utf8_strlen($this->request->post['company']) < 3) || (utf8_strlen($this->request->post['company']) > 32)) {
			$this->error['company'] = $this->language->get('error_company');
		}

		if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
			$this->error['password'] = $this->language->get('error_password');
		}

		if ($this->request->post['confirm'] != $this->request->post['password']) {
			$this->error['confirm'] = $this->language->get('error_confirm');
		}

		if ((utf8_strlen($this->request->post['alipay']) < 10) || (utf8_strlen($this->request->post['alipay']) > 64)) {
			$this->error['alipay'] = $this->language->get('error_alipay');
		}

		if ($this->request->post['country_id'] == '' || !is_numeric($this->request->post['country_id'])) {
			$this->error['country'] = $this->language->get('error_country');
		}

		if (!isset($this->request->post['zone_id']) || $this->request->post['zone_id'] == '' || !is_numeric($this->request->post['zone_id'])) {
			$this->error['zone'] = $this->language->get('error_zone');
		}

		if (!isset($this->request->post['city_id']) || $this->request->post['city_id'] == '' || !is_numeric($this->request->post['city_id'])) {
			$this->error['city'] = $this->language->get('error_city');
		}

		if (!isset($this->request->post['county_id']) || $this->request->post['county_id'] == '' || !is_numeric($this->request->post['county_id'])) {
			$this->error['county'] = $this->language->get('error_county');
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$this->error['captcha'] = $captcha;
			}
		}

		if ($this->config->get('module_multiseller_seller_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('module_multiseller_seller_id'));

			if ($information_info && !isset($this->request->post['agree'])) {
				$this->error['warning'] = sprintf($this->language->get('error_agree'), $information_info['title']);
			}
		}

		return !$this->error;
	}
}