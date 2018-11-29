<?php
class ControllerApiShippingtrack extends Controller {

	public function index() 
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

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/order');

        $order_info                     = $this->model_account_order->getOrderForMs($req_data['order_sn']);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }


        //快递单信息
        $this->load->model('extension/module/aftership');

        $data['order_tracking']          = array();
        if ($this->config->get('module_aftership_status')) {
            $order_aftership_tracking = $this->model_extension_module_aftership->getOrderShippingTrack($order_id);
            foreach ($order_aftership_tracking as $item) {
                $seller_name = $this->model_extension_module_aftership->getShippingSellerName($item['seller_id']);
                if ($seller_name) {
                    $show_name = '[' . $seller_name . ']';
                } else {
                    $show_name = '';
                }
                $data['order_tracking'][] = array(
                    'tracking_code'    => $item['tracking_code'],
                    'tracking_name'    => $this->model_extension_module_aftership->getTrackingNameByCode($item['tracking_code']) . $show_name,
                    'tracking_number'  => $item['tracking_number'],
                    'comment'          => $item['comment']
                );
            }
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$order_info]));
    }
}
