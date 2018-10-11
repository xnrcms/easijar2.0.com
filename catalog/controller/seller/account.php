<?php
class ControllerSellerAccount extends Controller {
    public function index() {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('seller/account');

            $this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
        }

        $this->load->language('seller/layout');
        $this->load->language('seller/account');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('seller/account')
        );

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $this->load->model('multiseller/seller');
        $this->load->model('multiseller/transaction');
        $this->load->model('multiseller/order');

        $order_count = $this->model_multiseller_seller->getTotalOrdersBySellerId($this->customer->getId());
        $order_amount = $this->model_multiseller_seller->getOrdersTotalBySellerId($this->customer->getId());
        $product_count = $this->model_multiseller_seller->getTotalSellerProducts($this->customer->getId());
        $account_balance = $this->model_multiseller_transaction->getTransactionTotal();

        $data['order_count'] = $order_count;
        $data['order_amount'] = $this->currency->format($order_amount, $this->config->get('config_currency'));
        $data['product_count'] = $product_count;
        $data['account_balance'] = $this->currency->format($account_balance, $this->config->get('config_currency'));

        $order_data = $this->model_multiseller_seller->getTotalOrdersByMonth($this->customer->getId());

        foreach ($order_data as $item) {
            $data['chart']['date'][] = $item['day']; // 日期
            $data['chart']['number'][] = $item['amount']; //  订单金额
            $data['chart']['count'][] = $item['count']; // 订单数量
        }

		$data['orders'] = array();

		$filter_data = array(
			'sort'                   => 'o.date_added',
			'order'                  => 'DESC',
			'start'                  => 0,
			'limit'                  => 10
		);

		$results = $this->model_multiseller_order->getOrders($filter_data);

		foreach ($results as $result) {
			$data['orders'][] = array(
				'order_id'      => $result['order_id'],
				'customer'      => $result['customer'],
				'order_status'  => $result['order_status'] ? $result['order_status'] : $this->language->get('text_missing'),
				'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'date_added'    => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'date_modified' => date($this->language->get('datetime_format'), strtotime($result['date_modified'])),
				'shipping_code' => $result['shipping_code'],
				'view'          => $this->url->link('seller/order/info', 'order_id=' . $result['order_id']),
			);
		}

        $this->response->setOutput($this->load->view('seller/account', $data));
    }
}
