<?php
class ControllerExtensionTotalCredit extends Controller {
	public function index() {
		$balance = $this->customer->getBalance();

		$cart_total = $this->cart->getTotal();

		if ((float)$balance > 0 && $cart_total && $this->config->get('total_credit_status')) {
			$this->load->language('extension/total/credit');

			$data['heading_title'] = sprintf($this->language->get('heading_title'), $this->currency->format($balance, $this->session->data['currency']));
			$data['credit'] = array_get($this->session->data, 'credit');

			return $this->load->view('extension/total/credit', $data);
		}
	}

	public function credit() {
		$this->load->language('extension/total/credit');

		$json = array();

		$balance = $this->customer->getBalance();
		$cart_total = $this->cart->getTotal();

		$credit = array_get($this->session->data, 'credit');
		$this->session->data['credit'] = $credit ? 0 : 1;

		$this->session->data['success'] = $this->language->get('text_success');

		if (isset($this->request->post['redirect'])) {
			$json['redirect'] = $this->url->link($this->request->post['redirect']);
		} else {
			$json['redirect'] = $this->url->link('checkout/cart');
		}

		$this->jsonOutput($json);
	}
}
