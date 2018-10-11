<?php
class ControllerAccountEdit extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/edit');

			$this->response->redirect($this->url->link('account/login'));
		}

		$this->load->language('account/edit');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

		$this->load->model('account/customer');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_account_customer->editCustomer($this->customer->getId(), $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('account/account'));
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
			'text' => $this->language->get('text_edit'),
			'href' => $this->url->link('account/edit')
		);

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

		if (isset($this->error['custom_field'])) {
			$data['error_custom_field'] = $this->error['custom_field'];
		} else {
			$data['error_custom_field'] = array();
		}

		$data['action'] = $this->url->link('account/edit');

		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
		}

		if (isset($this->request->post['fullname'])) {
			$data['fullname'] = $this->request->post['fullname'];
		} elseif (!empty($customer_info)) {
			$data['fullname'] = $customer_info['fullname'];
		} else {
			$data['fullname'] = '';
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} elseif (!empty($customer_info)) {
			$data['email'] = $customer_info['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} elseif (!empty($customer_info)) {
			$data['telephone'] = $customer_info['telephone'];
		} else {
			$data['telephone'] = '';
		}

		// Custom Fields
		$data['custom_fields'] = array();
		
		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'account') {
				$data['custom_fields'][] = $custom_field;
			}
		}

		if (isset($this->request->post['custom_field']['account'])) {
			$data['account_custom_field'] = $this->request->post['custom_field']['account'];
		} elseif (isset($customer_info)) {
			$data['account_custom_field'] = json_decode($customer_info['custom_field'], true);
		} else {
			$data['account_custom_field'] = array();
		}

        $data['omni_auth_status'] = $this->config->get('module_omni_auth_status');
        $socials = $this->config->get('module_omni_auth_items');
        $data['omni_auth_socials'] = array();
        if ($socials) {
            foreach ($socials as $key => $social) {
                if ($social['enabled']) {
                    $provider = strtolower($social['provider']);
                    $data['omni_auth_socials'][$key] = $social;
                    $data['omni_auth_socials'][$key]['provider'] = $provider;
                    $data['omni_auth_socials'][$key]['label'] = $this->language->get("text_{$provider}");
                    $data['omni_auth_socials'][$key]['associated'] = $this->customer->associated($provider);
                }
            }
        }
        $data['customer_email'] = $this->customer->getEmail();
        $data['customer_telephone'] = $this->customer->getTelephone();

		$data['back'] = $this->url->link('account/account');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/edit', $data));
	}

	protected function validate() {
		if ((utf8_strlen(trim($this->request->post['fullname'])) < 1) || (utf8_strlen(trim($this->request->post['fullname'])) > 32)) {
			$this->error['fullname'] = $this->language->get('error_fullname');
		}

		if (array_get($this->request->post, 'email') && ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL))) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if (array_get($this->request->post, 'email') && (($this->customer->getEmail() != $this->request->post['email']) && $this->model_account_customer->getTotalCustomersByEmail($this->request->post['email']))) {
			$this->error['warning'] = $this->language->get('error_exists_email');
		}

		if (is_ft()) {
            if (array_get($this->request->post, 'telephone')) {
                $telephones = explode('-', $this->request->post['telephone']);
                if (count($telephones) < 2 || !strlen($telephones[0]) || !strlen($telephones[1] || strlen($telephones[0]) > 4)) {
                    $this->error['telephone'] = $this->language->get('error_telephone');
                }
            }
        } else {
            if (array_get($this->request->post, 'telephone') && ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32))) {
                $this->error['telephone'] = $this->language->get('error_telephone');
            }
        }

		if (array_get($this->request->post, 'telephone') && (($this->customer->getTelephone() != $this->request->post['telephone']) && $this->model_account_customer->getTotalCustomersByTelephone($this->request->post['telephone']))) {
			$this->error['warning'] = $this->language->get('error_exists_telephone');
		}

		if (!array_get($this->request->post, 'email') && !array_get($this->request->post, 'telephone')) {
			$this->error['email'] = $this->language->get('error_email_telephone_all_null');
			$this->error['telephone'] = $this->language->get('error_email_telephone_all_null');
        }

		// Custom field validation
		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'account') {
				if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
					$this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
				} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
					$this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
				}
			}
		}

		return !$this->error;
	}
}
