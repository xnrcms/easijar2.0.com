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
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
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

        $order_tracking     = [];
        if ($this->config->get('module_aftership_status'))
        {
            $order_aftership_tracking    = $this->model_extension_module_aftership->getOrderShippingTrackForMs($order_info['order_id'],$order_info['seller_id']);
            $aftership_ids               = [];

            foreach ($order_aftership_tracking as $item)
            {
                $aftership_ids[]        = $item['id'];

                $seller_name = $this->model_extension_module_aftership->getShippingSellerName($item['seller_id']);
                if ($seller_name) {
                    $show_name = '[' . $seller_name . ']';
                } else {
                    $show_name = '';
                }

                $this->request->get['slug']         = $item['tracking_code'];
                $this->request->get['number']       = $item['tracking_number'];
                $tracking_data                      = $this->load->controller('extension/module/aftership/getTraceForApi');
                if (isset($tracking_data['data']) && !empty($tracking_data['data'])) {
                    $tracking_data                  = $tracking_data['data'];
                }else{
                    $tracking_data                  = [];
                }

                $order_tracking = [
                    'tracking_name'    => $this->model_extension_module_aftership->getTrackingNameByCode($item['tracking_code']) . $show_name,
                    'tracking_data'    => $tracking_data
                ];
            }

            if (!empty($aftership_ids)) {
                $this->model_extension_module_aftership->updateOrderLogisticsReadStatus($aftership_ids);
            }
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$order_tracking]));
    }
}
