<?php 
/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

require_once DIR_SYSTEM . '/library/cw/OPPCw/init.php';
require_once 'OPPCw/Language.php';
require_once 'OPPCw/IPaymentMethodDefinition.php';
require_once 'OPPCw/Template.php';
require_once 'OPPCw/PaymentMethod.php';
require_once 'OPPCw/AbstractController.php';

abstract class ControllerPaymentOPPCwAbstract extends OPPCw_AbstractController implements OPPCw_IPaymentMethodDefinition
{
	private $paySingKey = '~~!!@#@#1';
	private $payUrl 	= 'https://test.oppwa.com';
	private $callback 	= 'http://10.5.151.185:8081/#/';

	public function index()
	{
		// Translations:
		$this->load->model('checkout/order');
		$orderId = $this->session->data['order_id'];
		if (!empty($orderId)) {
			
			$order_info = $this->model_checkout_order->getOrder($orderId);
			if (!$order_info)  return '';

			$entity_id 					= $this->config->get('module_oppcw_global_entity_id');
			$user_id 					= $this->config->get('module_oppcw_user_id');
			$user_password 				= $this->config->get('module_oppcw_user_password');
			$url 						= $this->payUrl . "/v1/checkouts";

			$payData 								= [];
			$payData['authentication.userId'] 		= $user_id;
			$payData['authentication.password'] 	= $user_password;
			$payData['authentication.entityId'] 	= $entity_id;
			$payData['amount'] 						= round($order_info['total'],2);
			$payData['currency'] 					= $order_info['currency_code'];
			$payData['paymentType'] 				= 'DB';
			$payData['merchantTransactionId'] 		= $order_info['order_sn'];

			$this->load->model('extension/payment/oppcw_creditcard');
	        $registration_ids = $this->model_extension_payment_oppcw_creditcard->getRegistrationId($order_info['customer_id']);
	        $registrations 	  = [];
	        if (!empty($registration_ids)) {
	        	foreach ($registration_ids as $key => $value) {
	        		$payData['registrations[' . $key . '].id'] 	=  $value['registrations'] ;
	        	}
	        }

			$pay  									= curl_http($url,$payData,'POST');
			$pay 									= !empty($pay) ? json_decode($pay,true) : [];

			if (isset($pay['result']['code']) && !empty($pay['result']['code'])) {

				if (preg_match('/000\\.200|800\\.400\\.5|100\\.400\\.500/', $pay['result']['code']) || preg_match('/000\\.400\\.0[^3]|000\\.400\\.100/', $pay['result']['code'])) {
					
					$sign 					= md5($order_info['order_sn'] . $order_info['customer_id'] . $pay['id'] . $this->paySingKey);
					$data 					= [];
					$data['callback'] 		= HTTP_SERVER . 'payment_callback/oppcw_creditcard?checkoutId=' . $pay['id'] . '&paysign='. $sign;
					$data['jsurl'] 			= $this->payUrl . '/v1/paymentWidgets.js?checkoutId=' . $pay['id'];

					return $this->renderView(OPPCw_Template::resolveTemplatePath('template/oppcw/pay'), $data);
				}else{
					return 'pay fail';
				}
			}

		}
		else {
			return 'The order ID is not set in the session. This happens when the order could not be 
					created in the database. A common cause is a not completely executed OpenCart database schema migration.';
		}
	}
	
	public function payFormForSm()
    {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrderByOrderSnUsePayInfoForMs($this->session->data['order_sn'],get_order_type($this->session->data['order_sn']));
		if (!$order_info)  return '';

		$entity_id 					= $this->config->get('module_oppcw_global_entity_id');
		$user_id 					= $this->config->get('module_oppcw_user_id');
		$user_password 				= $this->config->get('module_oppcw_user_password');
		$url 						= $this->payUrl . "/v1/checkouts";

		$payData 								= [];
		$payData['authentication.userId'] 		= $user_id;
		$payData['authentication.password'] 	= $user_password;
		$payData['authentication.entityId'] 	= $entity_id;
		$payData['amount'] 						= round($order_info['total'],2);
		$payData['currency'] 					= $order_info['currency_code'];
		$payData['paymentType'] 				= 'DB';
		$payData['merchantTransactionId'] 		= $order_info['order_sn'];

		$pay  									= curl_http($url,$payData,'POST');
		$pay 									= !empty($pay) ? json_decode($pay,true) : [];

		if (isset($pay['result']['code']) && !empty($pay['result']['code'])) {

			if (preg_match('/000\\.200|800\\.400\\.5|100\\.400\\.500/', $pay['result']['code']) || preg_match('/000\\.400\\.0[^3]|000\\.400\\.100/', $pay['result']['code'])) {
				
				$sign 					= md5($order_info['order_sn'] . $order_info['customer_id'] . $pay['id'] . $this->paySingKey);
				$data 					= [];
				$data['callback'] 		= $this->callback . 'orderFinish?paycode=oppcw_creditcard&checkoutId=' . $pay['id'] . '&paysign='. $sign . '&';
				$data['jsurl'] 			= $this->payUrl . '/v1/paymentWidgets.js?checkoutId=' . $pay['id'];
				wr($data);
				return $data;
			}else{
				$logger 				= new Log('pingpong.log');
				$logger->write(date('Y-m-d H:i:s') . 'pingpong pay error:'.json_encode($pay));
				return 'pay fail';
			}
		}
    }

    public function return_callback()
    {
    	$req_data 			= array_merge($this->request->get,$this->request->post);
    	$body 				= file_get_contents('php://input');

    	$key_from_configuration 	= "A33C82C19CC1F6B63B37E4B3F0BACBFEBD2BB3E3B6E7292023786AF97F771BAC";
		$iv_from_http_header 		= isset($this->request->server['HTTP_X_INITIALIZATION_VECTOR']) ? $this->request->server['HTTP_X_INITIALIZATION_VECTOR'] : '';
		$auth_tag_from_http_header 	= isset($this->request->server['HTTP_X_AUTHENTICATION_TAG']) ? $this->request->server['HTTP_X_AUTHENTICATION_TAG'] : '';
		$http_body 					= !empty($body) ? $body : '';
		$key 						= hex2bin($key_from_configuration);
		$iv 						= hex2bin($iv_from_http_header);
		$cipher_text 				= hex2bin($http_body . $auth_tag_from_http_header);
		$result 					= sodium_crypto_aead_aes256gcm_decrypt($cipher_text, NULL, $iv, $key);
		$result 					= !empty($result) ? json_decode($result,true) : [];
    	wr("==========1");
    	wr([$result]);
    	wr("==========2");
    	return $req_data;
    }

    public function callback() 
    {
		$res 		= $this->get_callback();
		if (isset($this->session->data['api_id']) && $this->session->data['api_id'] > 0) return $res;
		if ( isset($res[0]) && $res[0] == 'success') {
			$this->response->redirect($this->url->link('checkout/success'));
		}else{
			$this->response->redirect($this->url->link('checkout/failure'));
		}
	}

	private function get_callback()
	{
		$logger 					= new Log('pingpong.log');
		$req_data 					= array_merge($this->request->get,$this->request->post);
		$checkoutId 				= isset($req_data['checkoutId']) ? $req_data['checkoutId'] : '';
		$sign 						= isset($req_data['paysign']) ? $req_data['paysign'] : '';
		$id 						= isset($req_data['id']) ? $req_data['id'] : '';

		if (empty($id) || empty($checkoutId) || empty($sign)) return 'pay callback parameter error';
		
		$entity_id 					= $this->config->get('module_oppcw_global_entity_id');
		$user_id 					= $this->config->get('module_oppcw_user_id');
		$user_password 				= $this->config->get('module_oppcw_user_password');
		$url 						= $this->payUrl . "/v1/checkouts/" . $id . "/payment?";

		$payData 								= [];
		$payData['authentication.userId'] 		= $user_id;
		$payData['authentication.password'] 	= $user_password;
		$payData['authentication.entityId'] 	= $entity_id;

		$url 									= $url . http_build_query($payData);
		$pay  									= curl_http($url,'','GET');
		$pay 									= !empty($pay) ? json_decode($pay,true) : [];
		
		if (isset($pay['result']['code']) && !empty($pay['result']['code']))
		{
			if (preg_match('/000\\.000\\.|000\\.100\\.1|000\\.[36]/', $pay['result']['code']) || preg_match('/000\\.400\\.0[^3]|000\\.400\\.100/', $pay['result']['code']))
			{	
				$payid 									= isset($pay['id']) ? $pay['id'] : '';
				$order_sn 								= isset($pay['merchantTransactionId']) ? $pay['merchantTransactionId'] : '';
				$registrationId 						= isset($pay['registrationId']) ? $pay['registrationId'] : '';

				//订单合法性校验
				$this->load->model('checkout/order');
				$order_info 							= $this->model_checkout_order->getOrderByOrderSnUsePayInfoForMs($order_sn,get_order_type($order_sn));
				
				if (!$order_info) return 'order info error';
				if ($sign !== md5($order_sn . $order_info['customer_id'] . $checkoutId . $this->paySingKey)) return 'order sign error';

				//记录用户支付卡ID
				$this->load->model('extension/payment/oppcw_creditcard');
		        $method = $this->model_extension_payment_oppcw_creditcard->setRegistrationId($order_info['customer_id'], $registrationId);

				$this->load->model('checkout/order');
				$this->model_checkout_order->addOrderHistoryForMs($order_sn, $this->config->get('payment_alipay_order_status_id'));
				$this->model_checkout_order->updateSubOrderPayCode($order_sn, $payid);
				
				return ['success',$order_sn];
			}
		}

		$logger->write(date('Y-m-d H:i:s') . 'pingpong pay error:'.json_encode($pay));

		return 'pay fail';
	}

	public function returnPay($payid,$amount,$currency)
	{
		if (empty($payid) || $amount <= 0 || empty($currency)) return 'fail';

		$id 									= '8ac7a49f679c6d210167a175cdeb2992';
		$entity_id 								= $this->config->get('module_oppcw_global_entity_id');
		$user_id 								= $this->config->get('module_oppcw_user_id');
		$user_password 							= $this->config->get('module_oppcw_user_password');
		$url 									= $this->payUrl . "/v1/payments/" . $payid;

		$payData 								= [];
		$payData['authentication.userId'] 		= $user_id;
		$payData['authentication.password'] 	= $user_password;
		$payData['authentication.entityId'] 	= $entity_id;
		$payData['amount'] 						= $amount;
		$payData['currency'] 					= $currency;
		$payData['paymentType'] 				= 'RF';

		$pay  									= curl_http($url,$payData,'POST');
		$pay 									= !empty($pay) ? json_decode($pay,true) : [];

		if (isset($pay['result']['code']) && !empty($pay['result']['code']))
		{
			if (preg_match('/000\\.000\\.|000\\.100\\.1|000\\.[36]/', $pay['result']['code']) || preg_match('/000\\.400\\.0[^3]|000\\.400\\.100/', $pay['result']['code']))
			{
				return 'success';
			}
		}

		return 'fail';
	}
}

