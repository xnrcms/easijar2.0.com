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

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        //允许订单类型
        if (!in_array($req_data['order_type'], [0,1,2,3,4,5])) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_type is error']));
        }

        $page 			= isset($req_data['page']) ? ((int)$req_data['page'] > 0 ? (int)$req_data['page'] : 1) : 1;
        $limit          = 10;

		$this->load->model('tool/image');

        if ($req_data['order_type'] == 5) {
            
            $this->load->model('account/return');

            $result                     = $this->model_account_return->getReturnsForMs(($page - 1) * $limit, $limit);
            $results                    = [];
            $results1                   = [];

            foreach ($result as $keys =>$value)
            {
                $results1[$value['seller_id']]['msid']            = $value['seller_id']; 
                $results1[$value['seller_id']]['store_name']      = $value['store_name'];
                $results1[$value['seller_id']]['product_info'][]  = [
                    'return_id'     => (int)$value['return_id'],
                    'order_id'      => (int)$value['order_id'],
                    'product_id'    => (int)$value['product_id'],
                    'name'          => $value['product'],
                    'quantity'      => (int)$value['quantity'],
                    'image'         => $this->model_tool_image->resize($value['image'], 100, 100),
                    'option'        => $value['model'],
                    'action'        => $value['action'],
                ];
            }

            foreach ($results1 as $value1) {
                $results[]  = $value1;
            }
        }
        elseif ($req_data['order_type'] == 4)
        {
            $this->load->model('account/oreview');

            $filter_data = [
                'filter_customer_id'    => $this->customer->getId(),
                'start'                 => ($page - 1) * $limit,
                'limit'                 => $limit,
            ];

            $result                     = $this->model_account_oreview->getOreviewsForMs($filter_data);
            $results                    = [];
            $results1                   = [];

            foreach ($result as $keys =>$value)
            {
                $results1[$value['msid'].'-'.$value['order_sn']]['msid']            = $value['msid'];
                $results1[$value['msid'].'-'.$value['order_sn']]['store_name']      = $value['store_name'];
                $results1[$value['msid'].'-'.$value['order_sn']]['order_sn']        = $value['order_sn'];
                $results1[$value['msid'].'-'.$value['order_sn']]['product_info'][]  = [
                    'product_id'=> $value['product_id'],
                    'order_sn'  => $value['order_sn'],
                    'name'      => $value['name'],
                    'image'     => $this->model_tool_image->resize($value['image'], 100, 100),
                    'price'     => $this->currency->format((float)$value['price'], $value['currency_code'], $value['currency_value'], $this->session->data['currency']),
                    'quantity'  => (int)$value['quantity'],
                    'option'    => $value['sku']
                ];
            }

            foreach ($results1 as $value1) {
                $results[]  = $value1;
            }
        }
        else
        {
            $this->load->model('account/order');

            //订单类型 0-所有订单 1-待付款 2-待发货 3-待收货 4-待评论 5-退货退款
            $results = $this->model_account_order->getOrdersForMs($req_data['order_type'],($page - 1) * $limit, $limit);

            $oid        = [];
            foreach ($results as $key => $value) {
                $oid[$value['oid']]     = $value['oid'];
            }

            $ms_total                  = $this->model_account_order->getTotalsForMsByCode($oid,'multiseller_shipping');
            $shipping                  = [];
            foreach ($ms_total as $mskey => $msval) {
                $shipping[$msval['order_id'].'-'.$msval['seller_id']]   = $msval['value'];
            }

            foreach ($results as $keys =>$result)
            {
                $shipping                  = isset($shipping[$result['oid'].'-'.$result['msid']]) ? $shipping[$result['oid'].'-'.$result['msid']] : 0;

                $results[$keys]['oid']     = $result['soid'];

                $results[$keys]['total']     = $this->currency->format($result['total'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']);
                $results[$keys]['shipping']  = $this->currency->format($shipping, $result['currency_code'], $result['currency_value'], $this->session->data['currency']);

                foreach ($result['product_info'] as $reskey => $resval) {
                    $results[$keys]['product_info'][$reskey]['price']   = $this->currency->format($resval['price'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']);
                    $results[$keys]['product_info'][$reskey]['total']   = $this->currency->format($resval['total'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']);

                    $results[$keys]['product_info'][$reskey]['image']       = $this->model_tool_image->resize($resval['image'], 100, 100);

                    unset($results[$keys]['product_info'][$reskey]['tax']);
                    unset($results[$keys]['product_info'][$reskey]['order_product_id']);
                }

                unset($results[$keys]['currency_code']);
                unset($results[$keys]['currency_value']);
                unset($results[$keys]['soid']);
            }
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$results]));
	}

	public function details()
	{
		$this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');

        $allowKey       = ['api_token','order_sn'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/order');
        $this->load->model('tool/image');

        $order_info 					= [];
        $product_info 					= [];
        $seller_info 					= [];

        $order_info 					= $this->model_account_order->getOrderForMs($req_data['order_sn']);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        $order_info['shipping_address'] = (!empty($order_info['shipping_country']) ? $order_info['shipping_country'] : '') . ' ' .
        (!empty($order_info['shipping_zone']) ? $order_info['shipping_zone'] : '') . ' ' .
        (!empty($order_info['shipping_city']) ? $order_info['shipping_city'] : '') . ' ' .
        (!empty($order_info['shipping_address_1']) ? $order_info['shipping_address_1'] : '');

        $currency_code                  = !empty($order_info['currency_code']) ? $order_info['currency_code'] : '';
        $currency_value                 = !empty($order_info['currency_value']) ? $order_info['currency_value'] : '';

        //商品信息
        $order_id 						= isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0;
        $seller_id 						= isset($order_info['seller_id']) ? (int)$order_info['seller_id'] : 0;
        $product_info 					= $this->model_account_order->getOrderProductsForMs($order_id,$seller_id);

        $seller_info['avatar'] 			= isset($order_info['avatar']) ? $order_info['avatar'] : '';
        $seller_info['store_name'] 		= isset($order_info['store_name']) ? $order_info['store_name'] : '';

        $subtotal                       = 0;
        foreach ($product_info as $pkey => $pval) {
            $subtotal                       += $pval['total'];

            $this->load->model('account/oreview');
            $this->load->model('account/return');

            $is_reviewed                        = $this->model_account_oreview->isReviewed($pval['order_product_id']);
            $complated                          = in_array($order_info['order_status_id'], $this->config->get('config_complete_status'));

            $product_info[$pkey]['price']       = $this->currency->format($pval['price'], $currency_code, $currency_value, $this->session->data['currency']);
            $product_info[$pkey]['total']       = $this->currency->format($pval['total'], $currency_code, $currency_value, $this->session->data['currency']);
            $product_info[$pkey]['image']       = $this->model_tool_image->resize($pval['image'], 100, 100);
            $product_info[$pkey]['oreview']     = ($is_reviewed || !$complated) ? 0 : 1;
            $product_info[$pkey]['return_id']   = $this->model_account_return->getReturnIdByOrderProductId($order_info['order_id'],$pval['order_product_id']);
        }

        unset($order_info['avatar']);
        unset($order_info['store_name']);
        unset($order_info['shipping_address_format']);
        unset($order_info['shipping_address_format']);
        unset($order_info['shipping_country']);
        unset($order_info['shipping_zone']);
        unset($order_info['shipping_city']);
        unset($order_info['shipping_address_2']);
        unset($order_info['currency_code']);
        unset($order_info['email']);
        unset($order_info['fullname']);
        unset($order_info['telephone']);
        unset($order_info['currency_value']);

        $json['order_info'] 			= $order_info;
        $json['product_info'] 			= $product_info;
        $json['seller_info'] 			= $seller_info;

        $ms_total                       = $this->model_account_order->getTotalsForMs($order_id,$seller_id);
        $shipping                       = 0;
        $coupon                         = 0;

        foreach ($ms_total as $msto) {
            if ($msto['code'] == 'multiseller_shipping' && !empty($msto['value'])) {
                $shipping       = $msto['value'];
            }
            if ($msto['code'] == 'multiseller_coupon' && !empty($msto['value'])) {
                $coupon         = $msto['value'];
            }
        }

        $total                          = $subtotal + $shipping - $coupon;

        $json['total']                  = $this->currency->format($total, $currency_code, $currency_value, $this->session->data['currency']);
        $json['total_shipping']         = $this->currency->format($shipping, $currency_code, $currency_value, $this->session->data['currency']);
        $json['total_coupon']           = $this->currency->format($coupon, $currency_code, $currency_value, $this->session->data['currency']);
        $json['subtotal']               = $this->currency->format($subtotal, $currency_code, $currency_value, $this->session->data['currency']);
        
        if($order_info['order_status_id'] == $this->config->get('config_unpaid_status_id') && $order_info['payment_code'] != 'cod') {
            /*$this->session->data['order_id'] = $order_id;
            $payment = $this->load->controller('extension/payment/' . $order_info['payment_code']);
            $json['payment'] = str_replace($this->language->get('button_confirm'), $this->language->get('button_pay_continue'), $payment);*/
            $json['payment'] = $order_info['payment_code'];
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

        $allowKey       = ['api_token','order_sn','reason_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $order_sn 					= isset($req_data['order_sn']) ? (string)$req_data['order_sn'] : '';
        $reason_id 					= (isset($req_data['reason_id']) && $req_data['reason_id']>0) ? (int)$req_data['reason_id'] : 0;

        if ( empty($order_sn) ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_sn is error']));
        }

        if ($reason_id <= 0 ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:reason_id is error']));
        }

        $this->load->model('account/order');
        $this->load->model('multiseller/checkout');
		$this->load->model('localisation/return_reason');

        $order_info 					= $this->model_account_order->getOrderStatusForMs($order_sn);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        $isReturn                       = $this->model_account_order->isReturn($order_sn);
        if ($isReturn) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_is_return')]));
        }

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

        $allowKey       = ['api_token','order_sn'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $order_sn                   = isset($req_data['order_sn']) ? (string)$req_data['order_sn'] : '';
        if ( empty($order_sn) ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_sn is error']));
        }

        $this->load->model('account/order');

        $order_info 					= $this->model_account_order->getOrderStatusForMs($order_sn);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        $isReturn                       = $this->model_account_order->isReturn($order_sn);
        if ($isReturn) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_is_return')]));
        }

        if( isset($order_info['order_status_id']) && $order_info['order_status_id'] === $this->config->get('config_shipped_status_id')){

        	$this->load->model('multiseller/checkout');

        	$complete_status = $this->config->get('config_complete_status');

        	$this->model_multiseller_checkout->addSubOrderHistory($order_info['order_id'], $order_info['seller_id'], $complete_status[0],t('text_customer_confirm'),false,true);

        	return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'order confirm success']));
        }else{

        	$this->load->model('localisation/order_status');
        	$order_status = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

        	if (!empty($order_status) && isset($order_status['name']) && !empty($order_status['name'])) {
            	return $this->response->setOutput($this->returnData(['msg'=>'fail:' . $order_status['name']]));
        	}

            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_status is error']));
        }
    }

    public function delete()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');

        $allowKey       = ['api_token','order_sn'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $order_sn                   = isset($req_data['order_sn']) ? (string)$req_data['order_sn'] : '';
        if ( empty($order_sn) ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_sn is error']));
        }

        $this->load->model('account/order');

        $order_info                     = $this->model_account_order->getOrderStatusForMs($order_sn);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        $isReturn                       = $this->model_account_order->isReturn($order_sn);
        if ($isReturn) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_is_return')]));
        }

        $this->model_account_order->deleteSubOrder($order_sn);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'order delete success']));
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
        
        return $this->response->setOutput($this->returnData(['msg'=>'接口暂时弃用']));
        
        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $order_sn                   = isset($req_data['order_sn']) ? (string)$req_data['order_sn'] : '';
        if ( empty($order_sn) ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_sn is error']));
        }

        $this->load->model('account/order');
        $this->load->model('multiseller/checkout');
		$this->load->model('localisation/return_reason');

        $order_info 					= $this->model_account_order->getOrderStatusForMs($order_sn);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }
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
