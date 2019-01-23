<?php
class ControllerApiInformation extends Controller {

    //信息详情
	public function details() 
	{	
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('information/information');

        $allowKey       = ['api_token','information_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $information_id = (int)array_get($req_data,'information_id');

        if ($information_id <= 0) return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('text_error')]));

        $this->load->model('catalog/information');
        $information_info = $this->model_catalog_information->getInformation($information_id);

        if (empty($information_info)) return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('text_error')]));

        $json                   = [];
        $json['title']          = $information_info['title'];
        $json['content']        = htmlspecialchars_decode($information_info['description']);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    public function get_reason()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','dtype'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

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

        $dtype              = (int)$req_data['dtype'];

        if (!in_array($dtype, [0,1,2,3,4])) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:dtype is error']));
        }

        $this->load->model('localisation/return_reason');
        
        $dataFilter             = [];
        $dataFilter['start']    = 0;
        $dataFilter['limit']    = 100;
        
        if ($dtype != 4) {
            $dataFilter['type']     = $dtype;
        }

        $reason                 = $this->model_localisation_return_reason->getReturnReasons($dataFilter);
        
        $json                   = [];
        
        // 0-订单取消 1-已收到货 2-未收到货 3-待发货 
        if (!empty($reason)) {
            foreach ($reason as $key => $value) {
                $json['reason'][] = ['return_reason_id'=>$value['return_reason_id'],'name'=>$value['name']];
            }
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }
}
