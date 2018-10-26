<?php
class ControllerApiRegion extends Controller {

	//用户中心首页
	public function index() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        $this->load->language('api/address');

        $allowKey       = ['api_token','country_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $country_id         = isset($req_data['country_id']) ? (int)$req_data['country_id'] : 0;
        $json['region']     = [];
        $results            = [];

        if ($country_id <= 0) 
        {
            $this->load->model('localisation/country');
            $results        = $this->model_localisation_country->getCountries();
            $idname         = 'country_id';
        }
        else
        {
            $this->load->model('localisation/zone');
            $results        = $this->model_localisation_zone->getZonesByCountryId($country_id);
            $idname         = 'zone_id';

        }

        foreach ($results as $result) {
            $json['region'][] = array(
                'region_id'         => $result[$idname],
                'region_name'       => $result['name']
            );
        }

        return $this->response->setOutput($this->returnData($json));
    }
}