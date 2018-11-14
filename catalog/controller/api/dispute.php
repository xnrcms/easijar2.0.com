<?php
class ControllerApiDispute extends Controller {

	//申请售后
	public function apply() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');

        $allowKey       = ['api_token','order_sn','order_product_id','refund_money','is_receive','is_service','reason_id','evidences'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/order');

        $order_info                     = $this->model_account_order->getOrderForMs($req_data['order_sn']);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        //获取商品信息
        $product_info                   = $this->model_account_order->getOrderProductForMsByOrderProductId($req_data['order_product_id']);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:product info is error']));
        }

        $returnData                     = [];
        $returnData['order_id']         = $order_info['order_id'];
        $returnData['product_id']       = $product_info['product_id'];
        $returnData['fullname']         = $order_info['fullname'];
        $returnData['email']            = $order_info['email'];
        $returnData['telephone']        = $order_info['telephone'];
        $returnData['product']          = $order_info['product'];
        $returnData['model']            = $order_info['model'];
        $returnData['quantity']         = $order_info['quantity'];
        $returnData['opened']           = $order_info['opened'];
        $returnData['return_reason_id'] = $order_info['return_reason_id'];
        $returnData['return_action_id'] = $order_info['return_action_id'];
        $returnData['return_status_id'] = $order_info['return_status_id'];
        $returnData['comment']          = $order_info['comment'];
        $returnData['date_ordered']     = $order_info['date_ordered'];
        $returnData['date_added']       = $order_info['date_added'];
        $returnData['date_modified']    = $order_info['date_modified'];
        $returnData['seller_id']        = $order_info['seller_id'];
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=> $product_info ]));

        /*$address            = [];
        foreach ($order_payinfo as $key=>$val) {
            if (strpos($key,'payment_') === 0) {
                $address[ltrim($key,'payment_')]    = (int)$val;
            }
        }

        $this->load->model('checkout/checkout');

        $payment_methods            = $this->model_checkout_checkout->getPaymentMethodsForApi($address);

        $payment_option             = [];
        $selected_pay               = [];
        foreach ($payment_methods as $key => $value) {
            if ($address['code'] == $value['code']) {
                $selected_pay[]           = ['code' => $value['code'],'title' => $value['title']];
            }else{
                $payment_option[]       = ['code' => $value['code'],'title' => $value['title']];
            }
        }

        $payment_option         = array_merge($selected_pay,$payment_option);

        $json                   = [];
        $json['order_info']     = $order_info;
        $json['payment_option'] = $payment_option;
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=> $order_info ]));*/
    }

    //获取支付信息
    public function getpay() 
    {   
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','order_sn','payment_code'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }
        
        //验证支付订单
        $this->load->model('account/order');

        $order_payinfo                     = $this->model_account_order->getOrderPayinfoForMs($req_data['order_sn']);
        if (empty($order_payinfo)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        if (!isset($order_payinfo['order_status_id']) || $order_payinfo['order_status_id'] != 1) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_status')]));
        }

        $this->session->data['order_sn'] = $req_data['order_sn'];

        //修改支付方式
        $this->load->model('checkout/checkout');
        $this->model_checkout_checkout->setPaymentMethodsForMs($order_payinfo['order_id'],$req_data['payment_code']);

        if ($req_data['payment_code'] == 'cod')
        {
            $this->load->model('checkout/order');
            
            $payment            = '';
            $this->model_checkout_order->addOrderHistoryForMs($req_data['order_sn'],15, '买家用户中心发起支付');
        } else {
            $payment = $this->load->controller('extension/payment/' . $req_data['payment_code'] . '/payFormForSm');
        }

        $json                   = [];
        $json['payment']        = $req_data['payment_code'];
        $json['payinfo']        = $payment;
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    public function refund($order_sn)
    {
        return $order_sn;
    }
}
