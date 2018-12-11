<?php
class ControllerApiAddress extends Controller {

	//用户收货地址列表
	public function index() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        $this->load->language('api/address');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $json           = [];

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

        $this->load->model('account/address');

        $addresses          = [];
        $results            = $this->model_account_address->getAddresses();

        foreach ($results as $result) {
            $addresses[]    = [
                'address_id'    => $result['address_id'],
                'fullname'      => $result['fullname'],
                'telephone'     => $result['telephone'],
                'address_1'     => !empty($result['address_1']) ? $result['address_1'] : '',
                'address_2'     => !empty($result['address_2']) ? $result['address_2'] : '',
                'postcode'      => !empty($result['postcode']) ? $result['postcode'] : '',
                'is_default'    => $this->customer->getAddressId() == $result['address_id'] ? 1 : 0,
            ];
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$addresses]));
    }

    // 获取收货地址详情
    public function get_address()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','address_id'];
        $req_data       = $this->dataFilter($allowKey);
        $json           = [];

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

        $address_id             = isset($req_data['address_id']) ? (int)$req_data['address_id'] : 0;
        $json['address_id']     = $address_id;

        if ($address_id >= 0)
        {
            $this->load->model('account/address');

            $address                    = $this->model_account_address->getAddress($address_id);
            
            if ($address)
            {
                $json['address_id']     = $address_id;
                $json['fullname']       = $address['fullname'];
                $json['telephone']      = $address['telephone'];
                $json['address_1']      = $address['address_1'];
                $json['address_2']      = $address['address_2'];
                $json['postcode']       = $address['postcode'];
                $json['city']           = $address['city'];
                $json['zone_id']        = $address['zone_id'];
                $json['country_id']     = $address['country_id'];
                $json['default']        = ($this->customer->getAddressId() == $address['address_id']) ? 1 : 0;
            }
        }else{

            $json['country_id']         = array_get($this->session->data, 'shipping_address.country_id', config('config_country_id'));
            $json['zone_id']            = array_get($this->session->data, 'shipping_address.zone_id', config('config_zone_id'));
            $json['postcode']           = array_get($this->session->data, 'shipping_address.postcode');
        }

        $this->load->model('localisation/country');
        $results                        = $this->model_localisation_country->getCountries();

        foreach ($results as $result) {
            $json['countries'][] = array(
                'county_id'     => $result['country_id'],
                'county_name'   => $result['name'],
                'selected'      => $result['country_id'] == $address['country_id'] ? 1 : 0,
            );
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    //保存收货地址
    public function save_address()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/address');

        $allowKey       = ['api_token','fullname','telephone','address_1','address_2','country_id','zone_id','city','postcode','default','address_id','save_type'];
        $req_data       = $this->dataFilter($allowKey);
        $json           = [];

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

        //数据检验
        if ((utf8_strlen(trim($req_data['fullname'])) < 1) || (utf8_strlen(trim($req_data['fullname'])) > 32))
            return $this->response->setOutput($this->returnData(['msg'=>t('error_fullname')]));

        if ((utf8_strlen(trim($req_data['telephone'])) < 5) || (utf8_strlen(trim($req_data['telephone'])) > 32))
            return $this->response->setOutput($this->returnData(['msg'=>t('error_telephone')]));

        if ((utf8_strlen(trim($req_data['address_1'])) < 3) || (utf8_strlen(trim($req_data['address_1'])) > 128))
            return $this->response->setOutput($this->returnData(['msg'=>t('error_address_1')]));

        if ((utf8_strlen(trim($req_data['postcode'])) < 2 || utf8_strlen(trim($req_data['postcode'])) > 10))
            return $this->response->setOutput($this->returnData(['msg'=>t('error_postcode')]));

        /*if (!isset($req_data['zone_id']) || (int)$req_data['zone_id'] <= 0 ) 
            return $this->response->setOutput($this->returnData(['msg'=>t('error_zone')]));*/

        if ((utf8_strlen(trim($req_data['city'])) < 2) || (utf8_strlen(trim($req_data['city'])) > 128))
            return $this->response->setOutput($this->returnData(['msg'=>t('error_city')]));

        $this->load->model('account/address');

        $address_id     = (int)array_get($req_data, 'address_id',0);
        $save_type      = (int)$req_data['save_type'] == 2 ? 2 : 1;

        $req_data['city_id']    = 0;
        $req_data['county_id']  = 0;
        $req_data['company']    = '';

        if ($address_id > 0) {
            $this->model_account_address->editAddress($address_id, $req_data);
        } else {
            $address_id = $this->model_account_address->addAddress($this->customer->getId(), $req_data);
        }

        $address                    = $this->model_account_address->getAddress($address_id);
        
        //订单处保存需要处理一些数据
        if ($save_type == 2) {
            $this->load->model('checkout/checkout');

            $type           = 'shipping';
            $this->syncAddressSession($type, $address);

            if (empty($this->session->data["payment_address"]['address_id'])) {
                $this->syncAddressSession('payment', $address);
            }
        }

        if ($address)
        {
            $json['address_id']     = (int)$address_id;
            $json['fullname']       = $address['fullname'];
            $json['telephone']      = $address['telephone'];
            $json['address_1']      = $address['address_1'];
            $json['address_2']      = $address['address_2'];
            $json['postcode']       = $address['postcode'];
            $json['city']           = $address['city'];
            $json['zone_id']        = (int)$address['zone_id'];
            $json['country_id']     = (int)$address['country_id'];
            $json['default']        = ($this->customer->getAddressId() == $address['address_id']) ? 1 : 0;
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    public function del_address()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/address');

        $allowKey       = ['api_token','address_id'];
        $req_data       = $this->dataFilter($allowKey);
        $json           = [];

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

        $address_id       = isset($req_data['address_id']) ? (int)$req_data['address_id'] : 0;
        if ($address_id <= 0 ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:address_id is error']));
        }

        $this->load->model('account/address');

        if ($this->model_account_address->getTotalAddresses() == 1) {
            return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_delete')]));
        }

        if ($this->customer->getAddressId() == $address_id) {
            return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_default')]));
        }

        $this->model_account_address->deleteAddress($address_id);

        // Default Shipping Address
        if (isset($this->session->data['shipping_address']['address_id']) && ($address_id == $this->session->data['shipping_address']['address_id'])) {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
        }

        // Default Payment Address
        if (isset($this->session->data['payment_address']['address_id']) && ($address_id == $this->session->data['payment_address']['address_id'])) {
            unset($this->session->data['payment_address']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$this->language->get('text_delete')]));
    }

    private function syncAddressSession($type, $address)
    {
        if (!in_array($type, ['payment', 'shipping'])) return false;

        if ($type == 'shipping' && !$this->cart->hasShipping()) {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['shipping_method']);
            return false;
        }

        $this->session->data["{$type}_address"] = $address;

        $method = 'set' . ucfirst($type) . 'Method';
        if ($code = array_get($this->session->data, "{$type}_method.code")) {
            if (!$this->model_checkout_checkout->{$method}($code)) {
                $this->model_checkout_checkout->{$method}();
            }
        } else {
            $this->model_checkout_checkout->{$method}();
        }
    }
}
