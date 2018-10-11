<?php
class ControllerNotifyRegister extends Controller {
	public function index(&$route, &$args, &$output) {
		$this->load->model('account/customer_group');

		if (isset($args[0]['customer_group_id'])) {
			$customer_group_id = $args[0]['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

		if ($customer_group_info) {
		    $this->load->model('notify/notify');
			if ($customer_group_info['approval']) {
                $this->model_notify_notify->customerRegisterApproval($args[0]);
            } else {
                $this->model_notify_notify->customerRegisterLogin($args[0]);
            }
		}
	}
}
