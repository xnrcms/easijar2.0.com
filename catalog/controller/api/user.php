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

        if ($this->checkSign($req_data)) {

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
        }else{
            $data       = $this->returnData(['msg'=>'fail:sign error']);
        }
        wr($data);
        return $this->response->setOutput($data);
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

	public function token() {
		$this->load->language('account/login');

		if (isset($this->request->get['email'])) {
			$email = $this->request->get['email'];
		} else {
			$email = '';
		}

		if (isset($this->request->get['login_token'])) {
			$token = $this->request->get['login_token'];
		} else {
			$token = '';
		}

		// Login override for admin users
		$this->customer->logout();
		$this->cart->clear();

		unset($this->session->data['order_id']);
		unset($this->session->data['payment_address']);
		unset($this->session->data['payment_method']);
		unset($this->session->data['payment_methods']);
		unset($this->session->data['shipping_address']);
		unset($this->session->data['shipping_method']);
		unset($this->session->data['shipping_methods']);
		unset($this->session->data['comment']);
		unset($this->session->data['coupon']);
		unset($this->session->data['reward']);
		unset($this->session->data['voucher']);
		unset($this->session->data['vouchers']);
        unset($this->session->data['credit']);

		$this->load->model('account/customer');

		$customer_info = $this->model_account_customer->getCustomerByEmail($email);

		if ($customer_info && $customer_info['token'] && $customer_info['token'] == $token && $this->customer->login($customer_info['customer_id'], '', true)) {
			// Default Addresses
			$this->load->model('account/address');

			if ($this->config->get('config_tax_customer') == 'payment') {
				$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			if ($this->config->get('config_tax_customer') == 'shipping') {
				$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			$this->model_account_customer->editToken($customer_info['customer_id'], '');

			$this->response->redirect($this->url->link('account/account'));
		} else {
			$this->session->data['error'] = $this->language->get('error_login');

			$this->model_account_customer->editToken($customer_info['customer_id'], '');

			$this->response->redirect($this->url->link('account/login'));
		}
	}
}
