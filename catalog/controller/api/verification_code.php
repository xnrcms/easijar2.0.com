<?php
class ControllerApiVerificationCode extends Controller
{
	//物流订单列表
	public function index() 
	{	
		$this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','scene','account','verification_code'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        $scene                                      = isset($req_data['scene']) ? (int)$req_data['scene'] : 0;
        $account                                    = isset($req_data['account']) ? (string)$req_data['account'] : '';
        $verification_code                          = isset($req_data['verification_code']) ? (string)$req_data['verification_code'] : '';
        $keys                                       = md5('smscode-' . $account . '-' . $scene);

        if (!isset($this->session->data['smscode'][$keys]['code']) || $verification_code != $this->session->data['smscode'][$keys]['code'] || $this->session->data['smscode'][$keys]['expiry_time'] < time()) {
            return $this->response->setOutput($this->returnData(['msg'=>'verification_code is error']));
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success']));
    }
}
