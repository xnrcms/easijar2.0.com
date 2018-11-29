<?php
class ControllerApiPay extends Controller {

	//订单支付
	public function getorder() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');

        $allowKey       = ['api_token','order_sn'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/order');

        $order_payinfo                     = $this->model_account_order->getOrderPayinfoForMs($req_data['order_sn']);
        if (empty($order_payinfo)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        $order_info                     = [];
        $order_info['order_sn']         = $order_payinfo['order_sn'];
        $order_info['order_money']      = $this->currency->format($order_payinfo['total'], $order_payinfo['currency_code'], $order_payinfo['currency_value'], $this->session->data['currency']);

        $address            = [];
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
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=> $json ]));
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

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
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
        $json['payinfo']        = !empty($payment) ? $payment : '';
        $json['path']           = 'extension/payment/' . $req_data['payment_code'] . '/payFormForSm';
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    public function refund($order_sn)
    {
        return $order_sn;
    }
}
