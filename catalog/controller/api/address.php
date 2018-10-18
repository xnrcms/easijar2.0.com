<?php
class ControllerApiAddress extends Controller {
	public function address_details() 
	{
		$this->response->addHeader('Content-Type: application/json');

		$allowKey		= ['api_token','address_id','type'];
		$req_data 		= $this->dataFilter($allowKey);
        $json           =  $this->returnData();

		if ($this->checkSign($req_data)) {
			
			$json       = [];

            if (!$this->isLogged()){
                $json['code']       = '201';
                $json['msg']        = t('warning_login');
                return $this->response->setOutput($this->returnData($json));
            }

            if ($address_id = array_get($req_data, 'address_id')) {
	            $address = $this->model_account_address->getAddress($address_id);
	            if (!$address) {
	                $address_id = 0;
	            } else {
	                $json['fullname'] 		= $address['fullname'];
	                $json['telephone'] 		= $address['telephone'];
	                $json['company'] 		= $address['company'];
	                $json['address_1'] 		= $address['address_1'];
	                $json['address_2'] 		= $address['address_2'];
	                $json['postcode'] 		= $address['postcode'];
	                $json['city'] 			= $address['city'];
	                $json['zone_id'] 		= $address['zone_id'];
	                $json['zone'] 			= $address['zone'];
	                $json['zone_code'] 		= $address['zone_code'];
	                $json['country_id'] 	= $address['country_id'];
	                $json['country'] 		= $address['country'];
	                $json['city_id'] 		= $address['city_id'];
	                $json['county_id'] 		= $address['county_id'];
	                $json['address_custom_field'] = $address['custom_field'];
	                $json['default'] 		= $this->customer->getAddressId() == $address['address_id'];
	            }
        	}

        	if (!$address_id) {
	            $data['country_id'] 		= array_get($this->session->data, 'shipping_address.country_id', config('config_country_id'));
	            $data['zone_id'] 			= array_get($this->session->data, 'shipping_address.zone_id', config('config_zone_id'));
	            $data['postcode'] 			= array_get($this->session->data, 'shipping_address.postcode');
	        }

	        $this->load->model('localisation/country');
	        $data['countries'] 				= $this->model_localisation_country->getCountries();

	        // Custom Fields
	        $this->load->model('account/custom_field');
	        $custom_fields 					= $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));
	        $data['custom_fields'] 			= [];
	        foreach ($custom_fields as $custom_field) {
	            if ($custom_field['location'] == 'address') {
	                $data['custom_fields'][] 	= $custom_field;
	            }
	        }

	        $data['address_id'] 			= $address_id;
	        $data['type'] 					= array_get($req_data, 'type', 'shipping');


		    $json 		= $this->returnData(['code'=>'200','msg'=>'success','data'=>$json]);
		}else{

			$json 		= $this->returnData(['msg'=>'fail:sign error']);
		}

		return $this->response->setOutput($json);
	}
}
