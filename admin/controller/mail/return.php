<?php
class ControllerMailReturn extends Controller {
	public function index($route, $args, $output) {
		if (isset($args[3])) {
			$notify = $args[3];
		} else {
			$notify = '';
		}

		if (!$notify) {
			return;
		}

		if (isset($args[0])) {
			$return_id = $args[0];
		} else {
			$return_id = '';
		}

		if (isset($args[1])) {
			$return_status_id = $args[1];
		} else {
			$return_status_id = '';
		}

		if (isset($args[2])) {
			$comment = $args[2];
		} else {
			$comment = '';
		}

		$this->load->model('sale/return');

		$return_info = $this->model_sale_return->getReturn($return_id);

		if (!$return_info) {
			return;
		}

		if (!$return_info['email']) {
		    return;
		}

		$this->load->model('customer/customer');
		$customer_info = $this->model_customer_customer->getCustomer($return_info['customer_id']);
		if ($customer_info) {
			$this->load->model('setting/store');
			$store_info = $this->model_setting_store->getStore($customer_info['store_id']);
			if ($store_info) {
				$store_url = $store_info['url'];
				$store_name = $store_info['name'];
			}
		}

		$store_url = isset($store_url) ? $store_url : HTTPS_CATALOG;
		$store_name = isset($store_name) ? $store_name : config('config_name');

		$this->load->language('mail/return');

		$data['return_id'] = $return_id;
		$data['date_added'] = date($this->language->get('datetime_format'), strtotime($return_info['date_modified']));
		$data['return_status'] = $return_info['return_status'];
		$data['comment'] = strip_tags(html_entity_decode($comment, ENT_QUOTES, 'UTF-8'));
		$data['store'] = $store_name;
		$data['store_url'] = $store_url;
		$data['logo'] = $store_url  . 'image/' . $this->config->get('config_logo');


		$mail = new Mail();

		$mail->setTo($return_info['email']);
		$mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'), $return_id));
		$mail->setHtml($this->load->view('mail/return', $data));
		$mail->send();
	}
}