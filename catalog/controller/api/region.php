<?php
class ControllerApiRegion extends Controller {

	public function index() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        $this->load->language('api/address');

        $allowKey       = ['api_token','country_id'];
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

        $country_id         = isset($req_data['country_id']) ? (int)$req_data['country_id'] : 0;
        $region             = [];
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
            $region[] = array(
                'region_id'         => $result[$idname],
                'region_name'       => $result['name']
            );
        }

        /*$json = array();
        $this->load->model('localisation/zone');
        $this->load->model('localisation/city');
        $zoneId = $this->request->get['zone_id'];
        $isTop = $this->request->get['is_top'];

        if ($isTop) {
            $zone_info = $this->model_localisation_zone->getZone($zoneId);
        } else {
            $zone_info = $this->model_localisation_city->getCity($zoneId);
        }
        if ($zone_info) {
            $json = array(
                'zone_id'   => $zone_info['zone_id'],
                'name'      => $zone_info['name'],
                'city'      => $this->model_localisation_city->getCitiesByZoneId($zoneId, $isTop),
                'status'    => $zone_info['status']
            );
        }*/

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$region]));
    }

    public function linkage()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('api/address');

        $allowKey       = ['api_token','parent_id','level'];
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

        $level           = (int)$req_data['level'];
        $parent_id       = (int)$req_data['parent_id'];

        if ($level >= 2 && $parent_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:parent_id error']));
        }

        switch ($level) {
            case 1:
                $this->load->model('localisation/country');
                $results        = $this->model_localisation_country->getCountries();
                $idname         = 'country_id';
                break;
            case 2:
                $this->load->model('localisation/zone');
                $results        = $this->model_localisation_zone->getZonesByCountryId($parent_id);
                $idname         = 'zone_id';
                break;
            case 3:
                $this->load->model('localisation/city');
                $results        = $this->model_localisation_city->getCitiesByZoneId($parent_id, 1);
                $idname         = 'city_id';
                break;
            case 4:
                $this->load->model('localisation/city');
                $results        = $this->model_localisation_city->getCitiesByZoneId($parent_id, 0);
                $idname         = 'city_id';
                break;
            default:
                return $this->response->setOutput($this->returnData(['msg'=>'fail:level error']));
                break;
        }

        $region                 = [];

        foreach ($results as $result) {
            $region[] = array(
                'region_id'         => $result[$idname],
                'region_name'       => $result['name']
            );
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$region]));
    }
}
