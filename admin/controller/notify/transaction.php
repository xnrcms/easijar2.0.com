<?php
class ControllerNotifyTransaction extends Controller {
	public function index(&$route, &$args, &$output) {
		$this->load->model('customer/customer');

        $balance = $this->currency->format($args[2], $this->config->get('config_currency'));
        $total = $this->currency->format($this->model_customer_customer->getTransactionTotal($args[0]), $this->config->get('config_currency'));
        $total = $this->currency->format($total, $this->config->get('config_currency'));
        $description = $args[1];

        $this->load->model('notify/notify');
        $this->model_notify_notify->customerAddTransaction($args[0], $balance, $total, $description);
	}
}
