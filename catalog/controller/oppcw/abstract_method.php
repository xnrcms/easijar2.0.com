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
			$url 						= "https://test.oppwa.com/v1/checkouts";

			$payData 								= [];
			$payData['authentication.userId'] 		= $user_id;
			$payData['authentication.password'] 	= $user_password;
			$payData['authentication.entityId'] 	= $entity_id;
			$payData['amount'] 						= round($order_info['total'],2);
			$payData['currency'] 					= $order_info['currency_code'];
			$payData['paymentType'] 				= 'DB';

			$pay  									= curl_http($url,$payData,'POST');
			$pay 									= !empty($pay) ? json_decode($pay,true) : [];

			if (isset($pay['result']['code']) && !empty($pay['result']['code'])) {

				if (preg_match('/000\\.200|800\\.400\\.5|100\\.400\\.500/', $pay['result']['code']) || preg_match('/000\\.400\\.0[^3]|000\\.400\\.100/', $pay['result']['code'])) {
					
					$data 					= [];
					$data['callback'] 		= 'http://v2.easijar.com/payment_callback/oppcw_creditcard';
					$data['jsurl'] 			= 'https://test.oppwa.com/v1/paymentWidgets.js?checkoutId=' . $pay['id'];

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
		$url 						= "https://test.oppwa.com/v1/checkouts";

		$payData 								= [];
		$payData['authentication.userId'] 		= $user_id;
		$payData['authentication.password'] 	= $user_password;
		$payData['authentication.entityId'] 	= $entity_id;
		$payData['amount'] 						= round($order_info['total'],2);
		$payData['currency'] 					= $order_info['currency_code'];
		$payData['paymentType'] 				= 'DB';

		$pay  									= curl_http($url,$payData,'POST');
		$pay 									= !empty($pay) ? json_decode($pay,true) : [];

		if (isset($pay['result']['code']) && !empty($pay['result']['code'])) {

			if (preg_match('/000\\.200|800\\.400\\.5|100\\.400\\.500/', $pay['result']['code']) || preg_match('/000\\.400\\.0[^3]|000\\.400\\.100/', $pay['result']['code'])) {
				
				$data 					= [];
				$data['callback'] 		= 'http://v2.easijar.com/payment_callback/oppcw_creditcard';
				$data['jsurl'] 			= 'https://test.oppwa.com/v1/paymentWidgets.js?checkoutId=' . $pay['id'];

				return $this->renderView(OPPCw_Template::resolveTemplatePath('template/oppcw/pay'), $data);
			}else{
				return 'pay fail';
			}
		}
    }

    public function callback() {
		//$this->logger->write('alipay pay notify:');

		/*$this->load->model('checkout/order');
		$this->model_checkout_order->addOrderHistoryForMs('2018111514465212381382381', 5);*/
		$req_data 			= array_merge($this->request->get,$this->request->post);
		print_r($req_data);
		print_r('sssss');exit();
		/*$arr = $_POST;
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
		$this->load->model('extension/payment/alipay');
		$this->logger->write('POST' . var_export($_POST,true));
		$result = $this->model_extension_payment_alipay->check($arr, $config);

		if($result) {//check successed
			$this->log('Alipay check successed');

			if($_POST['trade_status'] == 'TRADE_FINISHED') {
			}
			else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {

				$order_sn = isset($_POST['out_trade_no']) ? explode('-', $_POST['out_trade_no']) : [];
				$order_sn = isset($order_sn[0]) ? $order_sn[0] : '';

				$this->load->model('checkout/order');
				$this->model_checkout_order->addOrderHistoryForMs($order_sn, $this->config->get('payment_alipay_order_status_id'));
			}
			echo "success";	//Do not modified or deleted
		}else {
			$this->log('Alipay check failed');
			//chedk failed
			echo "fail";

		}*/
	}
}

