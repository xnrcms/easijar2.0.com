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
				
				// getCouponList
				if (isset($this->session->data['getCouponList']) && is_array($this->session->data['getCouponList'])) {
					$this->load->model('marketing/coupon');

					foreach ($this->session->data['getCouponList'] as $key => $coupon_id)
					{
						if ( $this->model_marketing_coupon->isGetCoupon($coupon_id,$this->customer->getId()) <= 0 ) {
				        	$this->model_marketing_coupon->insertCoupon($coupon_id,$this->customer->getId());
				        }

						unset($this->session->data['getCouponList'][$key]);
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

		$allowKey       = ['api_token','regtype','account','password','confirm','verification_code'];
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
				return $this->response->setOutput($this->returnData(['msg'=>'regtype not open to the outside world']));
				/*$req_data['email'] 		= ''; 
				$req_data['telephone'] 	= $req_data['account']; 
		    	$req_dat['from'] 		= 'telephone';*/
				break;
			default: return $this->response->setOutput($this->returnData(['msg'=>'regtype is error']));break;
		}

		unset($req_data['account']);

		$this->load->model('account/customer');

        $validate 		= $this->register_validate($req_data);
        if ( !(isset($validate['code']) && $validate['code'] == '200') ) {
        	return $this->response->setOutput($this->returnData($validate));
        }

		unset($this->session->data['guest']);

		$req_data['fullname'] 		= '';
		$req_data['custom_field'] 	= '';
		$req_data['newsletter'] 	= 0;
		$req_data['safe'] 			= 0;
		$req_data['status'] 		= 0;

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
				//unset($this->session->data['getCouponList']);
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
		if ((utf8_strlen(html_entity_decode($req_data['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($req_data['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
			return ['msg'=>$this->language->get('error_password')];
		}

		if ($req_data['confirm'] !== $req_data['password']) {
			return ['msg'=>$this->language->get('error_confirm')];
		}

        $keys 										= md5('smscode-' . $req_data['email'] . '-1');
		if ((utf8_strlen(html_entity_decode($req_data['verification_code'], ENT_QUOTES, 'UTF-8')) != 6) || !isset($this->session->data['smscode'][$keys]) || $req_data['verification_code'] != $this->session->data['smscode'][$keys]['code'] ||  $this->session->data['smscode'][$keys]['expiry_time'] < time()){
			return ['msg'=>$this->language->get('error_smscode')];
		}

		if (array_get($req_data, 'email') && ((utf8_strlen($req_data['email']) > 96) || !filter_var($req_data['email'], FILTER_VALIDATE_EMAIL))) {
			return ['msg'=>$this->language->get('error_email')];
		}

		if (array_get($req_data, 'email') && $this->model_account_customer->getTotalCustomersByEmail($req_data['email'])) {
			return ['msg'=>$this->language->get('error_exists_email')];
		}

		if (!array_get($req_data, 'email') && !array_get($req_data, 'telephone')) {
			return ['msg'=>$this->language->get('error_email_telephone_all_null')];
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

        $code 										= mt_rand(100000, 999999); //生成校验码
        $this->session->data['smscode'][$email] 	= [
        	'code'      	=> $code,
            'send_time'		=> time()+(60*2),
            'expiry_time'   => time()+(60*10)
        ];

        $this->load->model('notify/notify');
        $ret = $this->model_notify_notify->customerRegisterVerify($telephone, $code);
        if ($ret === true) {
			return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'telephone smscode get success']));
        } else {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail: '.$ret]));
        }
	}

	public function get_emailcode()
	{
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('account/register');

        $allowKey       = ['api_token','email','scene'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:token is error']));
        }

        $email 						= array_get($req_data, 'email','');
        $tags 						= md5('smscode-'.$email.'-'.$req_data['scene']);

        if (empty($email)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:email is empty']));
        }

        if ( utf8_strlen($email) > 96 || !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
            return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_email')]));
		}

        if (isset($this->session->data['smscode']) && ($this->session->data['smscode'][$tags]['send_time']) >= time() ) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:send too fast']));
        }

        if ( isset($req_data['scene']) && (int)$req_data['scene'] == 1)
        {
        	$this->load->model('account/customer');
        	if ($this->model_account_customer->getTotalCustomersByEmail($email))
        	return $this->response->setOutput( $this->returnData(['msg'=>$this->language->get('error_exists_email')]) );
        }

        if (isset($this->session->data['smscode']))  unset($this->session->data['smscode']);

        $code 										= mt_rand(100000, 999999); //生成校验码
        $this->session->data['smscode'][$tags] 		= ['code' => $code, 'send_time' => time()+(60*2), 'expiry_time'  => time()+(60*10)];

        $this->load->language('mail/email_code');

        $data 					= [];
		$data['text_welcome'] 	= sprintf($this->language->get('text_welcome'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$data['text_login'] 	= $this->language->get('text_login');
		$data['text_thanks'] 	= $this->language->get('text_thanks');
		$data['email_code'] 	= $code;
		$data['store_url'] 		= HTTP_SERVER;
		$data['store'] 			= html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

		if ($this->config->get('config_logo')) {
			$data['logo'] = $this->url->imageLink(config('config_logo'));
		}

        $mail 					= new Mail();
		$mail->setTo($email);
		$mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
		$mail->setHtml($this->load->view('mail/email_code', $data));
		$mail->send();

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'email code get success']));
	}
}
