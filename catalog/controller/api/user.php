<?php
class ControllerApiUser extends Controller {
	private $error = array();

	public function login() 
	{
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('account/login');

        $allowKey       = ['api_token','type','account','password'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        $json       = [];

        if (!$this->customer->isLogged()) {
			
			if (!in_array($req_data['type'], [1,2])) return $this->response->setOutput($this->returnData(['msg'=>'fail:login type error']));

			$this->load->model('account/customer');

			if ($req_data['type'] == 1) {
                $customer_info = $this->model_account_customer->getCustomerByEmail($req_data['account']);
            } else {
                $customer_info = $this->model_account_customer->getCustomerByTelephone($req_data['account']);
            }

            if ($customer_info) {
	            // Check how many login attempts have been made.
	            $login_info = $this->model_account_customer->getLoginAttempts($customer_info['customer_id']);

	            if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
	            	return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_attempts')]));
	            }

	            if ($customer_info && !$customer_info['status']) {
	            	return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_approved')]));
				}

				if (!$customer_info || !$this->customer->login($customer_info['customer_id'], $req_data['password'])) {

					$this->model_account_customer->addLoginAttempt($customer_info['customer_id']);

					return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_login')]));
				}
				
				//删除锁定
				$this->model_account_customer->deleteLoginAttempts($customer_info['customer_id']);

				// Unset guest
				unset($this->session->data['guest']);

				// Default Shipping Address
				$this->load->model('account/address');

				if ($this->config->get('config_tax_customer') == 'payment') {
					$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				if ($this->config->get('config_tax_customer') == 'shipping') {
					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				// Wishlist
				if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
					$this->load->model('account/wishlist');

					foreach ($this->session->data['wishlist'] as $key => $product_id) {
						$this->model_account_wishlist->addWishlist($product_id);

						unset($this->session->data['wishlist'][$key]);
					}
				}

				

				// Log the IP info
				$this->model_account_customer->addLogin($this->customer->getId(), $this->request->server['REMOTE_ADDR']);

	            $data   = $this->returnData(['code'=>'200','msg'=>'success','data'=>'login success']);
	        }
		}else{
			$data   = $this->returnData(['code'=>'200','msg'=>'success','data'=>'already login']);
		}

        return $this->response->setOutput($data);
	}

	public function register()
	{
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('account/register');

		$allowKey       = ['api_token','regtype','account','password','confirm','smscode','captcha','agree'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($req_data['regtype']) && in_array(intval($req_data['regtype']), [1,2]))) {
        	return $this->response->setOutput($this->returnData(['msg'=>'regtype is error']));
		}

		if (!(isset($req_data['account']) && !empty($req_data['account']))) {
        	return $this->response->setOutput($this->returnData(['msg'=>'account is error']));
		}

		switch (intval($req_data['regtype'])) {
			case 1: 
				$req_data['email'] 		= $req_data['account'];
		    	$req_dat['from'] 		= 'email';
				$req_data['telephone'] 	= '';
				break;
			case 2:
				$req_data['email'] 		= ''; 
				$req_data['telephone'] 	= $req_data['account']; 
		    	$req_dat['from'] 		= 'telephone';
				break;
			default: return ['msg'=>'regtype is error'];break;
		}

		unset($req_data['account']);

		$this->load->model('account/customer');

        $validate 		= $this->register_validate($req_data);
        if ( !(isset($validate['code']) && $validate['code'] == '200') ) {
        	return $this->response->setOutput($this->returnData($validate));
        }

		unset($this->session->data['guest']);

		
		$customer_id = $this->model_account_customer->addCustomer($req_data);
		unset($this->session->data['smscode']);

		// Clear any previous login attempts for unregistered accounts.
		$this->model_account_customer->deleteLoginAttempts($customer_id);

		$this->customer->login($customer_id, $req_data['password']);

		// Log the IP info
		$this->model_account_customer->addLogin($this->customer->getId(), $this->request->server['REMOTE_ADDR']);

		return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'register success']));
	}

	public function logout() 
	{
		$this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if ($this->checkSign($req_data)){

        	if ($this->customer->isLogged()) {

	        	$this->customer->logout();

				unset($this->session->data['shipping_address']);
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_address']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
				unset($this->session->data['comment']);
				unset($this->session->data['order_id']);
				unset($this->session->data['coupon']);
				unset($this->session->data['reward']);
				unset($this->session->data['voucher']);
				unset($this->session->data['vouchers']);
				unset($this->session->data['credit']);
        	}

        	$data   = $this->returnData(['code'=>'200','msg'=>'success','data'=>'loginout success']);
        }else{
            $data       = $this->returnData(['msg'=>'fail:sign error']);
        }

        return $this->response->setOutput($data);
	}

	private function register_validate($req_data = [])
	{
		if (array_get($req_data, 'email') && ((utf8_strlen($req_data['email']) > 96) || !filter_var($req_data['email'], FILTER_VALIDATE_EMAIL))) {
			return ['msg'=>$this->language->get('error_email')];
		}

		if (array_get($req_data, 'email') && $this->model_account_customer->getTotalCustomersByEmail($req_data['email'])) {
			return ['msg'=>$this->language->get('error_exists_email')];
		}
		
		if (is_ft()) {
            if (array_get($req_data, 'telephone')) {
                $telephones = explode('-', $req_data['telephone']);
                if (count($telephones) < 2 || !strlen($telephones[0]) || !strlen($telephones[1] || strlen($telephones[0]) > 4)) {
                    return ['msg'=>$this->language->get('error_telephone')];
                }
            }
        } else {
            if (array_get($req_data, 'telephone') && ((utf8_strlen($req_data['telephone']) < 3) || (utf8_strlen($req_data['telephone']) > 32))) {
                return ['msg'=>$this->language->get('error_telephone')];
            }
        }

        if ($this->config->get('module_sms_status') && $this->config->get('module_sms_customer_register_verify_message') && array_get($req_data, 'telephone')) {
            if (!$req_data['smscode'] || !isset($this->session->data['smscode']) || $req_data['smscode'] != $this->session->data['smscode']['code'] || $this->session->data['smscode']['time'] < time() - 600) {
                return ['msg'=>$this->language->get('error_smscode')];
            } else if ($req_data['telephone'] != $this->session->data['smscode']['telephone']) {
            	return ['msg'=>$this->language->get('error_telephone_eq')];
            }
        }

		if (array_get($req_data, 'telephone') && $this->model_account_customer->getTotalCustomersByTelephone($req_data['telephone'])) {
			return ['msg'=>$this->language->get('error_exists_telephone')];
		}

		if (!array_get($req_data, 'email') && !array_get($req_data, 'telephone')) {
			return ['msg'=>$this->language->get('error_email_telephone_all_null')];
        }

		// Customer Group
		if (isset($req_data['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($req_data['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $req_data['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		// Custom field validation
		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'account') {
				if ($custom_field['required'] && empty($req_data['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
					return ['msg'=>sprintf($this->language->get('error_custom_field'), $custom_field['name'])];
				} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($req_data['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
					return ['msg'=>sprintf($this->language->get('error_custom_field'), $custom_field['name'])];
				}
			}
		}

		if ((utf8_strlen(html_entity_decode($req_data['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($req_data['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
			return ['msg'=>$this->language->get('error_password')];
		}

		if ($req_data['confirm'] !== $req_data['password']) {
			return ['msg'=>$this->language->get('error_confirm')];
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				return ['msg'=>$captcha];
			}
		}

		// Agree to terms
		if ($this->config->get('config_account_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

			if ($information_info && !isset($req_data['agree'])) {
				return ['msg'=>sprintf($this->language->get('error_agree'), $information_info['title'])];
			}
		}

		return ['code'=>'200'];
	}

	public function get_captcha()
	{
		$this->load->controller('extension/captcha/basic/captcha');
	}

	public function get_smscode()
	{
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('account/register');

        $allowKey       = ['api_token','telephone','captcha'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        $telephone 		= array_get($req_data, 'telephone','');

        if (empty($telephone)) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:telephone is empty']));
        }

		if (is_ft()) {
            $telephones 		= explode('-', $telephone);
            if (count($telephones) < 2 || !strlen($telephones[0]) || !strlen($telephones[1] || strlen($telephones[0]) > 4)) {
            	return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_telephone')]));
            }
        } else {
            if (array_get($this->request->post, 'telephone') && ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32))) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_telephone')]));
            }
        }

        $this->load->model('account/customer');
        if ($this->model_account_customer->getTotalCustomersByTelephone($telephone)) {
        	return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_telephone_exists')]));
        }

		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
            $this->load->language('extension/captcha/basic', 'captcha');
            if (!isset($this->session->data['captcha']) || empty($this->session->data['captcha'])
                || !isset($this->request->post['captcha']) || ($this->session->data['captcha'] != $this->request->post['captcha'])
            ) {
        		return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('captcha')->get('error_captcha')]));
            }
        }

        $code 							= mt_rand(100000, 999999); //生成校验码
        $this->session->data['smscode'] = [
        	'code'      => $code,
            'telephone' => $telephone,
            'time'      => time()
        ];

        $this->load->model('notify/notify');
        $ret = $this->model_notify_notify->customerRegisterVerify($telephone, $code);
        if ($ret === true) {
			return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'telephone smscode get success']));
        } else {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail: '.$ret]));
        }
	}
}
