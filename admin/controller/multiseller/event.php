<?php
class ControllerMultisellerEvent extends Controller {
	public function viewCommonColumnLeftBefore(&$route, &$data, &$template) {
	    if(!$this->config->get('module_multiseller_status')) {
	        return;
        }

        $this->load->language('common/column_left_gd');
        $multiseller = array();

        if ($this->user->hasPermission('access', 'extension/module/multiseller')) {
            $multiseller[] = array(
                'name'     => $this->language->get('text_multiseller_setting'),
                'href'     => $this->url->link('extension/module/multiseller', 'user_token=' . $this->session->data['user_token']),
                'children' => array()
            );
        }

        if ($this->user->hasPermission('access', 'multiseller/seller')) {
            $multiseller[] = array(
                'name'     => $this->language->get('text_seller'),
                'href'     => $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token']),
                'children' => array()
            );
        }

        if ($this->user->hasPermission('access', 'multiseller/seller_group')) {
            $multiseller[] = array(
                'name'     => $this->language->get('text_seller_group'),
                'href'     => $this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token']),
                'children' => array()
            );
        }

        if ($this->user->hasPermission('access', 'multiseller/product')) {
            $multiseller[] = array(
                'name'     => $this->language->get('text_product_list'),
                'href'     => $this->url->link('multiseller/product', 'user_token=' . $this->session->data['user_token']),
                'children' => array()
            );
        }

//        if ($this->user->hasPermission('access', 'multiseller/withdraw')) {
//            $multiseller[] = array(
//                'name'     => $this->language->get('text_withdraw'),
//                'href'     => $this->url->link('multiseller/withdraw', 'user_token=' . $this->session->data['user_token']),
//                'children' => array()
//            );
//        }

        if ($this->user->hasPermission('access', 'multiseller/transaction')) {
            $multiseller[] = array(
                'name'     => $this->language->get('text_transaction'),
                'href'     => $this->url->link('multiseller/transaction', 'user_token=' . $this->session->data['user_token']),
                'children' => array()
            );
        }

        if ($multiseller) {
            $data['menus'][] = array(
                'id'       => 'menu-multiseller',
                'icon'     => 'fa-dropbox',
                'name'     => $this->language->get('text_multiseller'),
                'href'     => '',
                'children' => $multiseller
            );
        }
	}

	public function viewSaleOrderInfoBefore(&$route, &$data, &$template) {
	    if(!$this->config->get('module_multiseller_status')) {
	        return;
        }

        $this->load->model('multiseller/seller');
        $this->load->model('multiseller/order');

        $this->load->language('multiseller/common', 'seller');

	    $products = array();

        foreach ($data['products'] as $key => $product) {
            $seller_info = $this->model_multiseller_seller->getSellerByProductId($product['product_id']);
            $seller_id = $seller_info ? $seller_info['seller_id'] : 0;
            if (isset($products[$seller_id])) {
                $products[$seller_id]['products'][] = $product;
            } else {
                $suborder_info = $this->model_multiseller_order->getSuborderStatusId((int)$this->request->get['order_id'], $seller_id);
                $products[$seller_id] = array(
                    'store_name'   => $seller_info ? $seller_info['store_name'] : $this->config->get('config_name'),
                    'products'     => array($product),
                    'order_stauts' => $suborder_info ? $suborder_info['order_status_name'] : ''
                );
            }
	    }
	    $data['products'] = $products;

        $data['sellers'] = array();
        $data['sellers'][] = array(
            'seller_id'   => -1,
            'name'        => $this->language->get('seller')->get('text_whole_order')
        );

        $sellers = $this->model_multiseller_order->getOrderSellers((int)$this->request->get['order_id']);
        foreach ($sellers as $seller) {
            $data['sellers'][] = array(
            'seller_id'   => $seller['seller_id'] ? (int)$seller['seller_id'] : 0,
            'name'        => $seller['seller_id'] ? $seller['store_name'] : $this->config->get('config_name')
            );
        }
        $data['entry_seller'] = $this->language->get('seller')->get('entry_seller');
    }

	public function controllerCustomerCustomerApprovalApproveBefore() {
		$this->load->language('customer/customer_approval');

		$json = array();

		if (!$this->user->hasPermission('modify', 'customer/customer_approval')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('customer/customer_approval');

			if ($this->request->get['type'] == 'seller') {
			    $this->load->model('multiseller/seller');
				$this->model_multiseller_seller->approveSeller($this->request->get['customer_id']);
            }

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
