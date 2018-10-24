<?php
class ControllerApiSystem extends Controller {

	//用户中心首页
	public function index() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        return $this->response->setOutput($this->returnData());
    }

    //设置系统配置信息
	public function setinfo() 
	{	
		$this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','sys_field','sys_updata'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        //允许设置配置
        if (!in_array($req_data['sys_field'], ['language','currency','country_code'])) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sys_field is error']));
        }

        $allow_updata            = [];

        switch ($req_data['sys_field']) {
            case 'language':

                $this->load->model('localisation/language');

                $results                    = $this->model_localisation_language->getLanguages();
                foreach ($results as $result) {
                    if ($result['status'])  $allow_updata[] = $result['code'];
                }
                break;
            case 'currency':

                $this->load->model('localisation/currency');

                $results                    = $this->model_localisation_currency->getCurrencies();
                foreach ($results as $result) {
                    if ($result['status'])  $allow_updata[] = $result['code'];
                }
                break;
            case 'country_code':

                $results                    = get_calling_codes();
                foreach ($results as $result) {
                    $allow_updata[]         = $result['code'];
                }
                break;
            default: return $this->response->setOutput($this->returnData(['msg'=>'fail:sys_field is error']));break;
        }

        if (!in_array($req_data['sys_updata'], $allow_updata)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sys_updata is error']));
        }
        
        $this->session->data[$req_data['sys_field']]      = $req_data['sys_updata'];

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'system info set success']));
    }

    //获取系统配置信息
    public function getinfo() 
    {   
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        $json['version']            = '1.0.0';
        $json['is_update']          = 0;

        //语言列表
        $this->load->model('localisation/language');
        
        $json['languages']          = [];
        $results                    = $this->model_localisation_language->getLanguages();

        foreach ($results as $result) {
            if ($result['status']) {
                $json['languages'][] = array(
                    'name'      => $result['name'],
                    'code'      => $result['code'],
                    'selected'  =>(isset($this->session->data['language']) && $this->session->data['language'] == $result['code']) ? 1 : 0,
                );
            }
        }

        //货币列表
        $this->load->model('localisation/currency');

        $json['currencies']         = [];
        $results                    = $this->model_localisation_currency->getCurrencies();

        foreach ($results as $result) {
            if ($result['status']) {
                $json['currencies'][] = array(
                    'name'      => $result['symbol_left'] . $result['title'] . $result['symbol_right'],
                    'code'      => $result['code'],
                    'selected'  =>(isset($this->session->data['currency']) && $this->session->data['currency'] == $result['code']) ? 1 : 0,
                );
            }
        }

        //国家列表
        $json['country']            = [];
        $results                    = get_calling_codes();

        foreach ($results as $result) {
            $name       = explode('(',$result['name']);
            $json['country'][] = array(
                'name'      => isset($name[0]) ? $name[0] : '',
                'code'      => $result['code'],
                'selected'  =>(isset($this->session->data['country_code']) && $this->session->data['country_code'] == $result['code']) ? 1 : 0,
            );
        }
        
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }
}
