<?php
class ControllerNotifyTransaction extends Controller {
	public function index(&$route, &$args, &$output) {
		$this->load->language('mail/transaction');

		$this->load->model('account/customer');

		$customer_info = $this->model_account_customer->getCustomer($args[0]);

		if ($customer_info) {
			$balance = $this->currency->format($args[2], $this->config->get('config_currency'));
			$total = $this->currency->format($this->model_account_customer->getTransactionTotal($args[0]), $this->config->get('config_currency'));
            $description = $args[1];

            $this->load->model('notify/notify');
            $this->model_notify_notify->customerAddTransaction($customer_info['fullname'], $balance, $description);
		}
	}
}
