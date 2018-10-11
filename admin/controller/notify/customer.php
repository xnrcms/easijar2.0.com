<?php
class ControllerNotifyCustomer extends Controller {
	public function approve(&$route, &$args, &$output) {
		$this->load->model('customer/customer');

		$customer_info = $this->model_customer_customer->getCustomer($args[0]);

		if ($customer_info) {
			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore($customer_info['store_id']);

            $this->load->model('notify/notify');
            $this->model_notify_notify->customerApprove($customer_info['customer_id'], $customer_info['fullname']);
		}
	}
}