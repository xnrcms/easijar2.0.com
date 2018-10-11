<?php
class ControllerNotifyOrder extends Controller {
	public function index(&$route, &$args) {
		if (isset($args[0])) {
			$order_id = $args[0];
		} else {
			$order_id = 0;
		}

		if (isset($args[1])) {
			$order_status_id = $args[1];
		} else {
			$order_status_id = 0;
		}

		if (isset($args[2])) {
			$comment = $args[2];
		} else {
			$comment = '';
		}

		// We need to grab the old order status ID
		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info) {
			// If order status is 0 then becomes greater than 0 send main html email
			if ($order_info['order_status_id'] == $this->config->get('config_unpaid_status_id') && $order_status_id  != $this->config->get('config_unpaid_status_id')) {
				$this->add($order_info, $order_status_id, $comment);
			}

			// If order status is not 0 then send update text email
			if (!in_array($order_info['order_status_id'], array(0, $this->config->get('config_unpaid_status_id'))) && $order_status_id) {
				$this->edit($order_info, $order_status_id, $comment);
			}
		}
	}

	public function add($order_info, $order_status_id, $comment) {
		$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

		if ($order_status_query->num_rows) {
			$order_status = $order_status_query->row['name'];
		} else {
			$order_status = '';
		}

		$this->load->model('notify/notify');
        $this->model_notify_notify->orderEffect($order_info['order_id'], $order_status);
	}

	public function edit($order_info, $order_status_id, $comment) {
		$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

		if ($order_status_query->num_rows) {
			$order_status = $order_status_query->row['name'];
		} else {
			$order_status = '';
		}

		$this->load->model('notify/notify');
        $this->model_notify_notify->orderUpdate($order_info['order_id'], $order_status);
	}

	// Admin Alert Mail
	public function alert(&$route, &$args) {
		if (isset($args[0])) {
			$order_id = $args[0];
		} else {
			$order_id = 0;
		}

		if (isset($args[1])) {
			$order_status_id = $args[1];
		} else {
			$order_status_id = 0;
		}

		if (isset($args[2])) {
			$comment = $args[2];
		} else {
			$comment = '';
		}

		if (isset($args[3])) {
			$notify = $args[3];
		} else {
			$notify = '';
		}

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info && $order_info['order_status_id'] == $this->config->get('config_unpaid_status_id') && $order_status_id != $this->config->get('config_unpaid_status_id') && in_array('order', (array)$this->config->get('config_mail_alert'))) {
			$order_total = $this->currency->format($order_info['total'], $order_info['currency_code']);
		    $this->load->model('notify/notify');
			$this->model_notify_notify->orderPaidAlert($order_id, $order_total, $order_info['fullname']);
		}
	}
}
