<?php
class ControllerMailTransaction extends Controller {
	public function index(&$route, &$args, &$output) {
		$this->load->language('mail/transaction');

		$this->load->model('account/customer');

		$customer_info = $this->model_account_customer->getCustomer($args[0]);

		if ($customer_info) {
			$data['text_received'] = sprintf($this->language->get('text_received'), $this->config->get('config_name'));
			$data['text_amount'] = $this->language->get('text_amount');
			$data['text_total'] = $this->language->get('text_total');

			$data['amount'] = $this->currency->format($args[2], $this->config->get('config_currency'));
			$data['total'] = $this->currency->format($this->model_account_customer->getTransactionTotal($args[0]), $this->config->get('config_currency'));

			$data['store_url'] = HTTP_SERVER;
			$data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

			if ($this->config->get('config_logo')) {
				$data['logo'] = $this->url->imageLink(config('config_logo'));
			}

			if (!$customer_info['email']) {
			    return;
            }
			$mail = new Mail();

			$mail->setTo($customer_info['email']);
			$mail->setSubject(html_entity_decode(sprintf($this->language->get('text_subject'), $this->config->get('config_name')), ENT_QUOTES, 'UTF-8'));
			$mail->setHtml($this->load->view('mail/transaction', $data));
			$mail->send();
		}
	}
}
