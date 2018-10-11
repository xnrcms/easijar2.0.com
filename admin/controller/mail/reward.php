<?php
class ControllerMailReward extends Controller {
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
			$points = $args[2];
		} else {
			$points = '';
		}

		if (isset($args[3])) {
			$order_id = $args[3];
		} else {
			$order_id = 0;
		}

		$this->load->model('customer/customer');

		$customer_info = $this->model_customer_customer->getCustomer($customer_id);

		if ($customer_info) {
			$this->load->language('mail/reward');

			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore($customer_info['store_id']);

			if ($store_info) {
				$store_url = $store_info['url'];
				$store_name = $store_info['name'];
			} else {
				$store_url = HTTPS_CATALOG;
				$store_name = $this->config->get('config_name');
			}

			$data['text_received'] = sprintf($this->language->get('text_received'), $points);
			$data['text_total'] = sprintf($this->language->get('text_total'), $this->model_customer_customer->getRewardTotal($customer_id));
			$data['store'] = $store_name;
			$data['store_url'] = $store_url;
			$data['logo'] = $store_url  . 'image/' . $this->config->get('config_logo');

			if (!$customer_info['email']) {
			    return;
            }
			$mail = new Mail();

			$mail->setTo($customer_info['email']);
			$mail->setSender(html_entity_decode($store_name, ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($store_name, ENT_QUOTES, 'UTF-8')));
			$mail->setHtml($this->load->view('mail/reward', $data));
			$mail->send();
		}
	}
}
