<?php
class ControllerNotifyReturn extends Controller {
	public function index($route, $args, $output) {
		if (isset($args[0])) {
			$return_id = $args[0];
		} else {
			$return_id = '';
		}
		
		if (isset($args[3])) {
			$notify = $args[3];
		} else {
			$notify = '';
		}		
		
		if ($notify) {
			$this->load->model('sale/return');
			
			$return_info = $this->model_sale_return->getReturn($return_id);
			
			if ($return_info) {
                $this->load->model('notify/notify');
                $this->model_notify_notify->returnUpdateMessage($return_info['customer_id'], $return_id, $return_info['return_status']);
			}
		}
	}
}	