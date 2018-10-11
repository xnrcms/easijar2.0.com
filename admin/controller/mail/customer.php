<?php
class ControllerMailCustomer extends Controller {
	public function approve(&$route, &$args, &$output) {
		$this->load->model('customer/customer');

		$customer_info = $this->model_customer_customer->getCustomer($args[0]);

		if ($customer_info) {
			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore($customer_info['store_id']);

			if ($store_info) {
				$store_name = html_entity_decode($store_info['name'], ENT_QUOTES, 'UTF-8');
				$store_url = $store_info['url'];
			} else {
				$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
				$store_url = HTTP_CATALOG;
			}

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($customer_info['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
			} else {
				$language_code = $this->config->get('config_language');
			}

			$language = new Language($language_code);
			$language->load($language_code);
			$language->load('mail/customer_approve');

			$subject = sprintf($language->get('text_subject'), $store_name);

			$data['text_welcome'] = sprintf($language->get('text_welcome'), $store_name);
			$data['text_login'] = $language->get('text_login');
			$data['text_service'] = $language->get('text_service');
			$data['text_thanks'] = $language->get('text_thanks');

			$data['button_login'] = $language->get('button_login');

			$data['login'] = $store_url . 'index.php?route=account/login';
			$data['store'] = $store_name;

			$data['store_url'] = $store_url;
			$data['logo'] = $store_url  . 'image/' . $this->config->get('config_logo');

			$this->load->model('tool/image');


            if (!$customer_info['email']) {
                return;
            }
			$mail = new Mail();

			$mail->setTo($customer_info['email']);
			$mail->setSender($store_name);
			$mail->setSubject($subject);
			$mail->setHtml($this->load->view('mail/customer_approve', $data));
			$mail->send();
		}
	}

	public function deny(&$route, &$args, &$output) {
		$this->load->model('customer/customer');

		$customer_info = $this->model_customer_customer->getCustomer($args[0]);

		if ($customer_info) {
			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore($customer_info['store_id']);

			if ($store_info) {
				$store_name = html_entity_decode($store_info['name'], ENT_QUOTES, 'UTF-8');
				$store_url = $store_info['url'];
			} else {
				$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
				$store_url = HTTP_CATALOG;
			}

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($customer_info['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
			} else {
				$language_code = $this->config->get('config_language');
			}

			$language = new Language($language_code);
			$language->load($language_code);
			$language->load('mail/customer_deny');

			$subject = sprintf($language->get('text_subject'), $store_name);

			$data['text_welcome'] = sprintf($language->get('text_welcome'), $store_name);
			$data['text_denied'] = $language->get('text_denied');
			$data['text_thanks'] = $language->get('text_thanks');

			$data['button_contact'] = $language->get('button_contact');

			$data['contact'] = $store_url . 'index.php?route=information/contact';
			$data['store'] = $store_name;
			$data['store_url'] = $store_url;
			$data['logo'] = HTTP_CATALOG  . 'image/' . $this->config->get('config_logo');

			$this->load->model('tool/image');


            if (!$customer_info['email']) {
                return;
            }
			$mail = new Mail($this->config->get('config_mail_engine'));

			$mail->setTo($customer_info['email']);
			$mail->setSender($store_name);
			$mail->setSubject($subject);
			$mail->setHtml($this->load->view('mail/customer_deny', $data));
			$mail->send();
		}
	}
}