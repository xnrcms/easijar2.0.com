<?php
class ControllerNotifyReward extends Controller {
	public function index($route, $args, $output) {
		if (isset($args[0])) {
			$customer_id = $args[0];
		} else {
			$customer_id = '';
		}

		if (isset($args[2])) {
			$points = $args[2];
		} else {
			$points = '';
		}

        $this->load->model('notify/notify');
        $this->model_notify_notify->customerAddReward($customer_id, $points);
	}
}
