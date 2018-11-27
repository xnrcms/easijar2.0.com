<?php
class ControllerExtensionPaymentPayssion extends Controller {
	protected $pm_id = '';
	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		if (!$this->config->get('payment_payssion_test')) {
			$data['action'] = 'https://www.payssion.com/payment/create.html';
		} else {
			$data['action'] = 'http://sandbox.payssion.com/payment/create.html';
		}

		$data['source'] = 'opencart';
		$data['pm_id'] = $this->pm_id;
		$data['api_key'] = $this->config->get('payment_payssion_apikey');
		$data['track_id'] = $order_info['order_id'];
		$data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data['currency'] = $order_info['currency_code'];
		$data['description'] = $this->config->get('config_name') . ' - #' . $order_info['order_id'];
		$data['payer_name'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];

// 		if (!$order_info['payment_address_2']) {
// 			$data['address'] = $order_info['payment_address_1'] . ', ' . $order_info['payment_city'] . ', ' . $order_info['payment_zone'];
// 		} else {
// 			$data['address'] = $order_info['payment_address_1'] . ', ' . $order_info['payment_address_2'] . ', ' . $order_info['payment_city'] . ', ' . $order_info['payment_zone'];
// 		}

		//$data['postcode'] = $order_info['payment_postcode'];
		$data['country'] = $order_info['payment_iso_code_2'];
		//$data['telephone'] = $order_info['telephone'];
		$data['payer_email'] = $order_info['email'];
		
		$data['notify_url'] = $this->url->link('extension/payment/payssion/notify', '', true);
		$data['success_url'] = $this->url->link('checkout/success', '', true);
		$data['return_url'] = $this->url->link('checkout/checkout', '', true);

		$data['api_sig'] = $this->generateSignature($data, $this->config->get('payment_payssion_secretkey'));
		
		$version_oc = substr(VERSION, 0, 3);
		if($version_oc == "2.3")
		{
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/payssion.twig')) {
				return $this->load->view($this->config->get('config_template') . '/template/extension/payment/payssion.twig', $data);
			} else {
				return $this->load->view('extension/payment/payssion', $data);
			}
		}
		else
		{
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/payssion')) {
				return $this->load->view($this->config->get('config_template') . '/template/extension/payment/payssion', $data);
			} else {
				return $this->load->view('default/template/extension/payment/payssion', $data);
			}
		}
	}
	
	private function generateSignature(&$req, $secretKey) {
		$arr = array($req['api_key'], $req['pm_id'], $req['amount'], $req['currency'],
				$req['track_id'], '', $secretKey);
		$msg = implode('|', $arr);
		return md5($msg);
	}

	public function notify() {
		$track_id = $this->request->post['track_id'];
		$this->load->model('checkout/order');
		if ($this->isValidNotify()) {
			if (!$this->request->server['HTTPS']) {
				$data['base'] = $this->config->get('config_url');
			} else {
				$data['base'] = $this->config->get('config_ssl');
			}
			
			$state = $this->request->post['state'];
			$message = '';
				
			if (isset($this->request->post['track_id'])) {
				$message .= 'track_id: ' . $this->request->post['track_id'] . "\n";
			}
				
			if (isset($this->request->post['pm_id'])) {
				$message .= 'pm_id: ' . $this->request->post['pm_id'] . "\n";
			}
			
			if (isset($this->request->post['state'])) {
				$message .= 'state: ' . $this->request->post['state'] . "\n";
			}
				
			if (isset($this->request->post['amount'])) {
				$message .= 'amount: ' . $this->request->post['amount'] . "\n";
			}
				
			if (isset($this->request->post['paid'])) {
				$message .= 'paid: ' . $this->request->post['paid'] . "\n";
			}
				
			if (isset($this->request->post['currency'])) {
				$message .= 'currency: ' . $this->request->post['currency'] . "\n";
			}
				
			if (isset($this->request->post['notify_sig'])) {
				$message .= 'notify_sig: ' . $this->request->post['notify_sig'] . "\n";
			}
				
			
			$status_list = array(
					'completed' => $this->config->get('payment_payssion_order_status_id'),
					'pending' => $this->config->get('payment_payssion_pending_status_id'),
					'expired' => $this->config->get('payment_payssion_expired_status_id'),
					'cancelled_by_user' => $this->config->get('payment_payssion_canceled_status_id'),
					'cancelled' => $this->config->get('payment_payssion_canceled_status_id'),
					'rejected_by_bank' => $this->config->get('payment_payssion_canceled_status_id'),
					'failed' => $this->config->get('payment_payssion_failed_status_id'),
					'error' => $this->config->get('payment_payssion_failed_status_id')
			);
				
			$this->model_checkout_order->addOrderHistory($track_id, $status_list[$state], $message);
			$this->response->setOutput('success');
			
		} else {
			$this->model_checkout_order->addOrderHistory($track_id, $this->config->get('config_order_status_id'), $this->language->get('text_pw_mismatch'));
			$this->response->setOutput('verify failed');
		}

	}
	
	public function isValidNotify() {
		$apiKey = $this->config->get('payment_payssion_apikey');;
		$secretKey = $this->config->get('payment_payssion_secretkey');
	
		// Assign payment notification values to local variables
		$pm_id = $this->request->post['pm_id'];
		$amount = $this->request->post['amount'];
		$currency = $this->request->post['currency'];
		$track_id = $this->request->post['track_id'];
		$sub_track_id = $this->request->post['sub_track_id'];
		$state = $this->request->post['state'];
	
		$check_array = array(
				$apiKey,
				$pm_id,
				$amount,
				$currency,
				$track_id,
				$sub_track_id,
				$state,
				$secretKey
		);
		$check_msg = implode('|', $check_array);
		$check_sig = md5($check_msg);
		$notify_sig = $this->request->post['notify_sig'];
		return ($notify_sig == $check_sig);
	}

	public function payFormForSm()
    {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrderByOrderSnUsePayInfoForMs($this->session->data['order_sn'],get_order_type($this->session->data['order_sn']));
		if (!$order_info)  return '';

		if (!$this->config->get('payment_payssion_test')) {
			$data['action'] = 'https://www.payssion.com/payment/create.html';
		} else {
			$data['action'] = 'http://sandbox.payssion.com/payment/create.html';
		}

		$data['source'] 		= 'opencart';
		$data['pm_id'] 			= $this->pm_id;
		$data['api_key'] 		= $this->config->get('payment_payssion_apikey');
		$data['track_id'] 		= $order_info['order_id'];
		$data['amount'] 		= $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data['currency'] 		= $order_info['currency_code'];
		$data['description']	= $this->config->get('config_name') . ' - #' . $order_info['order_id'];
		$data['payer_name'] 	= $order_info['payment_fullname'];
		$data['country'] 		= $order_info['payment_iso_code_2'];
		$data['payer_email'] 	= $order_info['email'];
		$data['notify_url'] 	= $this->url->link('extension/payment/payssion/notify', '', true);
		$data['success_url'] 	= $this->url->link('checkout/success', '', true);
		$data['return_url'] 	= $this->url->link('checkout/checkout', '', true);

		$data['api_sig'] 		= $this->generateSignature($data, $this->config->get('payment_payssion_secretkey'));
		
		$version_oc = substr(VERSION, 0, 3);

		if($version_oc == "2.3")
		{
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/payssion.twig')) {
				return $this->load->view($this->config->get('config_template') . '/template/extension/payment/payssion.twig', $data);
			} else {
				return $this->load->view('extension/payment/payssion', $data);
			}
		}
		else
		{
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/payssion')) {
				return $this->load->view($this->config->get('config_template') . '/template/extension/payment/payssion', $data);
			} else {
				return $this->load->view('default/template/extension/payment/payssion', $data);
			}
		}
		
return $data;
		$config = array (
			'app_id'               => $this->config->get('payment_alipay_app_id'),
			'merchant_private_key' => $this->config->get('payment_alipay_merchant_private_key'),
			'notify_url'           => HTTP_SERVER . "payment_callback/alipay",
			'return_url'           => $this->url->link('checkout/success'),
			'charset'              => "UTF-8",
			'sign_type'            => "RSA2",
			'gateway_url'          => $this->config->get('payment_alipay_test') == "sandbox" ? "https://openapi.alipaydev.com/gateway.do" : "https://openapi.alipay.com/gateway.do",
			'alipay_public_key'    => $this->config->get('payment_alipay_alipay_public_key'),
		);

		$out_trade_no 				= trim($order_info['order_sn']) . '-' . time();

		$this->load->model('account/order');

		$products 					= $this->model_account_order->getOrderProductsNameForMs($order_info['order_id'],$order_info['seller_id']);

		$products_name 				= '';
		foreach ($products as $product) {
		    if ($products_name) {
		         $products_name .= ';';
            }
		    $products_name .= $product['name'];
        }

		$subject 				= sub_string($products_name, 250, '');//trim($this->config->get('config_name'));
        $subject 				= $subject ? $subject : trim($this->config->get('config_name'));
		$total_amount 			= trim($this->currency->format($order_info['total'], 'CNY', '', false));
		$body 					= '';//trim($_POST['WIDbody']);

		
		$payRequestBuilder = array(
			'body'         => $body,
			'subject'      => str_replace('&', '-', $subject),
			'total_amount' => $total_amount,
			'out_trade_no' => $out_trade_no,
			'product_code' => 'FAST_INSTANT_TRADE_PAY'
		);

		$this->load->model('extension/payment/alipay');

		$response 				= $this->model_extension_payment_alipay->pagePay($payRequestBuilder,$config);
		
		$queryParam 		= '';
		foreach ($response as $key => $value) {
			$queryParam .= '&' . $key . "=" . urlencode(str_replace( "&quot;", "\"",$value));
		}

		$data['action'] 		= $config['gateway_url'];
		$data['form_params'] 	= $queryParam;
		$data['pay_url'] 		= $this->url->link('extension/payment/alipay/pay');

		return $data;
    }
}
