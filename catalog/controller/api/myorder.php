<?php
class ControllerApiMyorder extends Controller {

	//我的订单
	public function index()
	{
		$this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','order_type','page'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        //允许订单类型
        if (!in_array($req_data['order_type'], [0,1,2,3])) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_type is error']));
        }

        $page 			= isset($req_data['page']) ? ((int)$req_data['page'] > 0 ? (int)$req_data['page'] : 1) : 1;

        $this->load->model('account/order');
		$this->load->model('tool/image');

        //订单类型 0-所有订单 1-待付款 2-待发货 3-待收货 4-待评论 5-退货退款
        $results = $this->model_account_order->getOrdersForMs($req_data['order_type'],($page - 1) * 10, 10);

        foreach ($results as $keys =>$result)
        {
            $results[$keys]['oid']     = $result['soid'];

            unset($results[$keys]['currency_code']);
            unset($results[$keys]['currency_value']);
            unset($results[$keys]['soid']);

            $results[$keys]['total']   = $this->currency->format($result['total'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']);

        	foreach ($result['product_info'] as $reskey => $resval) {
                $results[$keys]['product_info'][$reskey]['price']   = $this->currency->format($resval['price'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']);
        		$results[$keys]['product_info'][$reskey]['total'] 	= $this->currency->format($resval['total'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']);

        		$results[$keys]['product_info'][$reskey]['image'] 	= $this->model_tool_image->resize($resval['image'], 100, 100);

                unset($results[$keys]['product_info'][$reskey]['tax']);
        	}
        }

        /*foreach ($results as $result) {
			$product_total = $this->model_account_order->getTotalOrderProductsByOrderId($result['order_id']);
			$voucher_total = $this->model_account_order->getTotalOrderVouchersByOrderId($result['order_id']);
			$recharge_total = $this->model_account_order->getTotalOrderRechargesByOrderId($result['order_id']);

            $product_list = array();
            $product_results = $this->model_account_order->getOrderProducts($result['order_id']);
            foreach($product_results as $product) {
                $product_list[] = array(
                    'name'  => $product['name'],
                    'href'  => $this->url->link('product/product', 'product_id=' . $product['product_id']),
                    'image' => $this->model_tool_image->resize($product['image'], 100, 100),
                    'total' => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $result['currency_code'], $result['currency_value'], $this->session->data['currency']),
                );
            }

            $voucher_list = array();
            $voucher_results = $this->model_account_order->getOrderVouchers($result['order_id']);
            foreach($voucher_results as $voucher) {
                $voucher_list[] = array(
                    'name'  => $voucher['description'],
                    'href'  => '',
                    'image' => $this->model_tool_image->resize('placeholder.png', 100, 100),
                    'total' => $this->currency->format($voucher['amount'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']),
                );
            }

            $recharge_list = array();
            $recharge_results = $this->model_account_order->getOrderRecharges($result['order_id']);
            foreach($recharge_results as $recharge) {
                $recharge_list[] = array(
                    'name'  => $recharge['description'],
                    'href'  => '',
                    'image' => $this->model_tool_image->resize('placeholder.png', 100, 100),
                    'total' => $this->currency->format($recharge['amount'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']),
                );
            }

            if ($result['order_status_id'] == $this->config->get('config_unpaid_status_id')) {
                $href_cancel = $this->url->link('account/order/cancel', 'order_id=' . $result['order_id']);
            } else {
                $href_cancel = '';
            }

            if ($result['order_status_id'] == $this->config->get('config_shipped_status_id')) {
                $href_confirm = $this->url->link('account/order/confirm', 'order_id=' . $result['order_id']);
            } else {
                $href_confirm = '';
            }

			$data['orders'][] = array(
			    'product_list' => array_merge($product_list, $voucher_list, $recharge_list),
				'order_id'   => $result['order_id'],
				'name'       => $result['fullname'],
				'status'     => $result['status'],
				'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'products'   => ($product_total + $voucher_total + $recharge_total),
				'total'      => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                'cancel'     => $href_cancel,
                'confirm'    => $href_confirm,
				'view'       => $this->url->link('account/order/info', 'order_id=' . $result['order_id']),
			);
		}*/

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$results]));
	}

	public function details()
	{
		$this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','order_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/order');

        $order_info 					= [];
        $product_info 					= [];
        $seller_info 					= [];

        $order_info 					= $this->model_account_order->getOrderForMs($req_data['order_id']);

        //商品信息
        $order_id 						= isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0;
        $seller_id 						= isset($order_info['seller_id']) ? (int)$order_info['seller_id'] : 0;
        $product_info 					= $this->model_account_order->getOrderProductsForMs($order_id,$seller_id);

        $seller_info['avatar'] 			= isset($order_info['avatar']) ? $order_info['avatar'] : '';
        $seller_info['store_name'] 		= isset($order_info['store_name']) ? $order_info['store_name'] : '';

        unset($order_info['avatar']);
        unset($order_info['store_name']);

        $json['order_info'] 			= $order_info;
        $json['product_info'] 			= $product_info;
        $json['seller_info'] 			= $seller_info;

        if($order_info['order_status_id'] == $this->config->get('config_unpaid_status_id') && $order_info['payment_code'] != 'cod') {
            $this->session->data['order_id'] = $order_id;
            $payment = $this->load->controller('extension/payment/' . $order_info['payment_code']);
            $json['payment'] = str_replace($this->language->get('button_confirm'), $this->language->get('button_pay_continue'), $payment);
        }else{
            $json['payment'] = '';
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
	}

	//订单取消
	public function cancel()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');

        $allowKey       = ['api_token','order_id','reason_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $order_id 					= (isset($req_data['order_id']) && $req_data['order_id']>0) ? (int)$req_data['order_id'] : 0;
        $reason_id 					= (isset($req_data['reason_id']) && $req_data['reason_id']>0) ? (int)$req_data['reason_id'] : 0;

        if ($order_id <= 0 ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_id is error']));
        }

        if ($reason_id <= 0 ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:reason_id is error']));
        }

        $this->load->model('account/order');
        $this->load->model('multiseller/checkout');
		$this->load->model('localisation/return_reason');

        $order_info 					= $this->model_account_order->getOrderStatusForMs($order_id);

        //未付款 直接取消
        if( isset($order_info['order_status_id']) && $order_info['order_status_id'] === $this->config->get('config_unpaid_status_id')){

        	//取消原因
        	$reason 			= $this->model_localisation_return_reason->getRsasonNameByType($reason_id,0);
        	if (!(isset($reason['name']) && !empty($reason['name']) )) {
        		return $this->response->setOutput($this->returnData(['msg'=>'fail:reason_id is error']));
        	}

        	$this->model_multiseller_checkout->addSubOrderHistory($order_info['order_id'], $order_info['seller_id'], $this->config->get('config_cancelled_status_id'),$reason['name'],false,true);

        	return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'order cancel success']));
        }
        else{

        	//提示已经操作的状态
        	$this->load->model('localisation/order_status');
        	$order_status = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

        	if (!empty($order_status) && isset($order_status['name']) && !empty($order_status['name'])) {
            	return $this->response->setOutput($this->returnData(['msg'=>'fail:' . $order_status['name']]));
        	}

            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_status is error']));
        }
    }

    //订单确认
    public function confirm()
    {
    	$this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');

        $allowKey       = ['api_token','order_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $order_id 					= (isset($req_data['order_id']) && $req_data['order_id']>0) ? (int)$req_data['order_id'] : 0;
        if ($order_id <= 0 ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_id is error']));
        }

        $this->load->model('account/order');

        $order_info 					= $this->model_account_order->getOrderStatusForMs($order_id);

        if( isset($order_info['order_status_id']) && $order_info['order_status_id'] === $this->config->get('config_shipped_status_id')){

        	$this->load->model('multiseller/checkout');

        	$complete_status = $this->config->get('config_complete_status');

        	$this->model_multiseller_checkout->addSubOrderHistory($order_info['order_id'], $order_info['seller_id'], $complete_status[0],t('text_customer_confirm'),false,true);

        	return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'order cancel success']));
        }else{

        	$this->load->model('localisation/order_status');
        	$order_status = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

        	if (!empty($order_status) && isset($order_status['name']) && !empty($order_status['name'])) {
            	return $this->response->setOutput($this->returnData(['msg'=>'fail:' . $order_status['name']]));
        	}

            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_status is error']));
        }
    }

    //订单退款
    public function refund()
    {
    	$this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');

        $allowKey       = ['api_token','order_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $order_id 					= (isset($req_data['order_id']) && $req_data['order_id']>0) ? (int)$req_data['order_id'] : 0;
        if ($order_id <= 0 ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_id is error']));
        }

        $this->load->model('account/order');
        $this->load->model('multiseller/checkout');
		$this->load->model('localisation/return_reason');

        $order_info 					= $this->model_account_order->getOrderStatusForMs($order_id);

        /*$this->load->model('checkout/order');
		$this->model_checkout_order->addOrderHistoryForMs($order_info['order_sn'], 15);
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'order cancel success']));*/

        //已付款-待发货发生退款需要判断商家未发货时间 如果商家超时发货直接取消订单  并退款
        if (isset($order_info['order_status_id']) && (int)$order_info['order_status_id'] === 15)
        {
        	//退款原因
        	$reason 			= $this->model_localisation_return_reason->getRsasonNameByType($reason_id,2);
        	if (!(isset($reason['name']) && !empty($reason['name']) )) {
        		return $this->response->setOutput($this->returnData(['msg'=>'fail:reason_id is error']));
        	}

        	//获取订单操作历史中操作时间 
        	$history_info 		= $this->model_account_order->getOrderHistoriesDateForMs($order_info['order_id'],$order_info['seller_id'],$order_info['order_status_id']);
        	$history_date 		= isset($history_info['date_added']) ? strtotime($history_info['date_added']) : 0;

        	$overtime 			= $history_date + (3600*24*3);
        	$t 					= time();

        	//判断是否超过三天
        	if ($history_date >= $t) {
        		//三天内 需要买家响应
        		$this->model_multiseller_checkout->addSubOrderHistory($order_info['order_id'], $order_info['seller_id'], 3,$reason['name'],false,true);
        	}else{
        		//直接取消并退款
        		$this->model_multiseller_checkout->addSubOrderHistory($order_info['order_id'], $order_info['seller_id'], $this->config->get('config_cancelled_status_id'),$reason['name'],false,true);

        		//退款
        		$this->load->controller('api/pay/refund',$order_info['order_sn']);
        	}

        	return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'order cancel success']));
        }//已发货 - 待收货发生退款 
        elseif (isset($order_info['order_status_id']) && (int)$order_info['order_status_id'] === 2) {
        	
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_status is error11111111']));
        }
        else{

        	//提示已经操作的状态
        	$this->load->model('localisation/order_status');
        	$order_status = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

        	if (!empty($order_status) && isset($order_status['name']) && !empty($order_status['name'])) {
            	return $this->response->setOutput($this->returnData(['msg'=>'fail:' . $order_status['name']]));
        	}

            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_status is error']));
        }
    }
}
