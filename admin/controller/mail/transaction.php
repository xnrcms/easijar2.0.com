<?php
class ControllerMailTransaction extends Controller {
	public function index($route, $args, $output) {
		if (isset($args[0])) {
			$customer_id = $args[0];
		} else {
			$customer_id = '';
		}

		if (isset($args[1])) {
			$description = $args[1];
		} else {
			$description = '';
		}

		if (isset($args[2])) {
			$amount = $args[2];
		} else {
			$amount = '';
		}

		if (isset($args[3])) {
			$order_id = $args[3];
		} else {
			$order_id = '';
		}

		$this->load->model('customer/customer');

		$customer_info = $this->model_customer_customer->getCustomer($customer_id);

		if ($customer_info) {
			$this->load->language('mail/transaction');

			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore($customer_info['store_id']);

			if ($store_info) {
				$store_name = $store_info['name'];
				$store_url = $store_info['url'];
			} else {
				$store_url = HTTPS_CATALOG;
				$store_name = $this->config->get('config_name');
			}

			$data['text_received'] = sprintf($this->language->get('text_received'), $this->currency->format($amount, $this->config->get('config_currency')));
			$data['text_total'] = sprintf($this->language->get('text_total'), $this->currency->format($this->model_customer_customer->getTransactionTotal($customer_id), $this->config->get('config_currency')));
			$data['store'] = $store_name;
			$data['store_url'] = $store_url;
			$data['logo'] = $store_url  . 'image/' . $this->config->get('config_logo');

			if (!$customer_info['email']) {
			    return;
            }
			$mail = new Mail();

			$mail->setTo($customer_info['email']);
			$mail->setSender(html_entity_decode($store_name, ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
			$mail->setHtml($this->load->view('mail/transaction', $data));
			$mail->send();
		}
	}
}
