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
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
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
        
        if ($req_data['sys_field'] == 'country_code') {
            $this->session->data['is_set_country']  = 1;

            //是否第一次设置
            $is_first        = isset($this->session->data['is_first_setting']) ? (int)$this->session->data['is_first_setting'] : 0;
            if ($is_first == 0) {
                $def_currency                               = [86=>'CNY',60=>'MYR',65=>'SGD'];
                $this->session->data['language']            = 'en-gb';
                $this->session->data['currency']            = isset($def_currency[(int)$req_data['sys_updata']]) ? $def_currency[(int)$req_data['sys_updata']] : 'USD';
                $this->session->data['is_first_setting']    = 1;
            }
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
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        $json['version']            = '1.0.0';
        $json['is_update']          = 0;
        $json['country_code']       = isset($this->session->data['country_code']) ? $this->session->data['country_code'] : '65';
        $json['languages_code']     = isset($this->session->data['language']) ? $this->session->data['language'] : 'en-gb';
        $json['is_set_country']     = isset($this->session->data['is_set_country']) ? (int)$this->session->data['is_set_country'] : 0;

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
            if ($result['status'] && $result['code'] != 'CNY') {
                $json['currencies'][] = array(
                    'name'      => $result['title'],
                    'code'      => $result['code'],
                    'selected'  =>(isset($this->session->data['currency']) && $this->session->data['currency'] == $result['code']) ? 1 : 0,
                );
            }
        }

        //国家列表
        $json['country']            = [];
        //$results                    = get_calling_codes();
        $results                    = [
            ['name'=>'Malaysia','code'=>'60'],
            ['name'=>'Singapore','code'=>'65'],
        ];

        foreach ($results as $result) {
            $json['country'][] = array(
                'name'      => $result['name'],
                'code'      => $result['code'],
                'selected'  =>(isset($this->session->data['country_code']) && $this->session->data['country_code'] == $result['code']) ? 1 : 0,
            );
        }
        
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    public function get_logistics()
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
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        $dtype                      = (int)$req_data['dtype'];
        
        //快递数据
        $kd_tracking_data           = $this->config->get('module_aftership_data');
        $allow_tracking             = [];

        foreach ($kd_tracking_data as $key => $value) {
            if ($value['status'] == 0)  continue;

            if ($dtype == 0 || ($dtype == 1 && $value['sort_order'] < 500) || ($dtype == 2 && $value['sort_order'] >= 500) ) {
                $allow_tracking[]     = ['code'=>$value['code'],'name'=>$value['name']];
            }
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$allow_tracking]));
    }
}
