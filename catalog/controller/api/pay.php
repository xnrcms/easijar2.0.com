<?php
class ControllerApiPay extends Controller {

	//订单支付
	public function index() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        return $this->response->setOutput($this->returnData());
    }

    //获取支付信息
    public function getinfo() 
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
        
        $this->session->data['order_sn'] = $req_data['order_sn'];

        $payment = $this->load->controller('extension/payment/' . $req_data['payment_code'] . '/payFormForSm');
        
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>['payinfo'=>$payment]]));
    }

    public function refund($order_sn)
    {
        return $order_sn;
    }
}
