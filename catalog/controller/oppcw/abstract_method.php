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
			
			$failedTransaction = null;
			$paymentMethod = new OPPCw_PaymentMethod($this);
			$orderContext = $paymentMethod->newOrderContext($order_info, $this->registry);
			$adapter = $paymentMethod->getPaymentAdapterByOrderContext($orderContext);
			
			$data = $adapter->getCheckoutPageHtml($paymentMethod, $orderContext, $this->registry, $failedTransaction);
			
			require_once 'Customweb/Licensing/OPPCw/License.php';
			Customweb_Licensing_OPPCw_License::run('41fdca1lfnq2j68k');
			$vars = array();
			$vars['checkout_form'] = $data;
			return $this->renderView(OPPCw_Template::resolveTemplatePath(OPPCw_Template::PAYMENT_FORM_TEMPLATE), $vars);
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

		$failedTransaction 			= null;
		$paymentMethod 				= new OPPCw_PaymentMethod($this);
		$orderContext 				= $paymentMethod->newOrderContext($order_info, $this->registry);
		$adapter 					= $paymentMethod->getPaymentAdapterByOrderContext($orderContext);
		
		$data = $adapter->getCheckoutPageHtml($paymentMethod, $orderContext, $this->registry, $failedTransaction);
		
		/*require_once 'Customweb/Licensing/OPPCw/License.php';
		Customweb_Licensing_OPPCw_License::run('41fdca1lfnq2j68k');*/
		$vars = array();
		$vars['checkout_form'] = $data;

		return $this->renderView(OPPCw_Template::resolveTemplatePath(OPPCw_Template::PAYMENT_FORM_TEMPLATE), $vars);
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

