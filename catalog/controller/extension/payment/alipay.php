<?php

use Payment\Common\PayException;
use Payment\Client\Refund;
use Payment\Config;

class ControllerExtensionPaymentAlipay extends Controller {
    private $logger;
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->logger = new Log('payment.log');
    }

	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');

        $this->document->addScript('catalog/view/javascript/other/ap.js');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$config = array (
			'app_id'               => $this->config->get('payment_alipay_app_id'),
			'merchant_private_key' => $this->config->get('payment_alipay_merchant_private_key'),
			'notify_url'           => HTTP_SERVER . "payment_callback/alipay",
			'return_url'           => OPPCW_CALLBACK . "orderFinish?paycode=alipay",
			'charset'              => "UTF-8",
			'sign_type'            => "RSA2",
			'gateway_url'          => $this->config->get('payment_alipay_test') == "sandbox" ? "https://openapi.alipaydev.com/gateway.do" : "https://openapi.alipay.com/gateway.do",
			'alipay_public_key'    => $this->config->get('payment_alipay_alipay_public_key'),
		);
		$out_trade_no = trim($order_info['order_sn']) . '-' . time();

		$this->load->model('account/order');
		$products = $this->model_account_order->getOrderProducts($order_info['order_id']);
		$products_name = '';
		foreach ($products as $product) {
		    if ($products_name) {
		         $products_name .= ';';
            }
		    $products_name .= $product['name'];
        }

		$subject = sub_string($products_name, 250, '');//trim($this->config->get('config_name'));
        $subject = $subject ? $subject : trim($this->config->get('config_name'));
		$total_amount = trim($this->currency->format($order_info['total'], 'CNY', '', false));
		$body = '';//trim($_POST['WIDbody']);

		$payRequestBuilder = array(
			'body'         => $body,
			'subject'      => str_replace('&', '-', $subject),
			'total_amount' => $total_amount,
			'out_trade_no' => $out_trade_no,
			'product_code' => 'FAST_INSTANT_TRADE_PAY'
		);

		$this->load->model('extension/payment/alipay');

		$response = $this->model_extension_payment_alipay->pagePay($payRequestBuilder,$config);
		$data['action'] = $config['gateway_url'];
		$data['form_params'] = $response;

		$data['pay_url'] = $this->url->link('extension/payment/alipay/pay');

		return $this->load->view('extension/payment/alipay', $data);
	}

	public function pay() {
	    $this->load->language('extension/payment/alipay');

        $data['title'] = $this->language->get('text_pay_title');

        $data['pay_url'] = $this->url->link('extension/payment/alipay/pay');

        $this->response->setOutput($this->load->view('extension/payment/alipay_pay', $data));
    }

	public function callback() {
		$this->logger->write('alipay pay notify:');

		/*$this->load->model('checkout/order');
		$this->model_checkout_order->addOrderHistoryForMs('2018111514465212381382381', 5);*/
		$req_data 					= array_merge($this->request->get,$this->request->post);
		$is_app 					= isset($req_data['is_app']) ? $req_data['is_app'] : 0;

		if (isset($req_data['route'])) unset($req_data['route']);
		if (isset($req_data['paycode'])) unset($req_data['paycode']);
		if (isset($req_data['is_app'])) unset($req_data['is_app']);

		$config 					= [
			'app_id'               => $this->config->get('payment_alipay_app_id'),
			'merchant_private_key' => $this->config->get('payment_alipay_merchant_private_key'),
			'notify_url'           => HTTP_SERVER . "payment_callback/alipay",
			'return_url'           => OPPCW_CALLBACK . "orderFinish?paycode=alipay",
			'charset'              => "UTF-8",
			'sign_type'            => "RSA2",
			'gateway_url'          => $this->config->get('payment_alipay_test') == "sandbox" ? "https://openapi.alipaydev.com/gateway.do" : "https://openapi.alipay.com/gateway.do",
			'alipay_public_key'    => $this->config->get('payment_alipay_alipay_public_key'),
		];

		$this->load->model('extension/payment/alipay');

		//$this->logger->write('POST' . var_export($_POST,true));
		$result = $this->model_extension_payment_alipay->check($req_data, $config);

		if($result)
		{
			$this->log('Alipay check successed');

			if ($is_app == 1 || ($is_app == 0 && $req_data['trade_status'] == 'TRADE_SUCCESS'))
			{
				$order_sn = isset($req_data['out_trade_no']) ? explode('-', $req_data['out_trade_no']) : [];
				$order_sn = isset($order_sn[0]) ? $order_sn[0] : '';
				$trade_no = isset($req_data['trade_no']) ? $req_data['trade_no'] : '';

				$this->load->model('checkout/order');

				$this->model_checkout_order->addOrderHistoryForMs($order_sn, $this->config->get('payment_alipay_order_status_id'));
				$this->model_checkout_order->updateSubOrderPayCode($order_sn, $trade_no);
			}
			/*if($req_data['trade_status'] == 'TRADE_FINISHED') {
			}
			else if ($req_data['trade_status'] == 'TRADE_SUCCESS') {

				$order_sn = isset($req_data['out_trade_no']) ? explode('-', $req_data['out_trade_no']) : [];
				$order_sn = isset($order_sn[0]) ? $order_sn[0] : '';
				$trade_no = isset($req_data['trade_no']) ? $req_data['trade_no'] : '';

				$this->load->model('checkout/order');
				$this->model_checkout_order->addOrderHistoryForMs($order_sn, $this->config->get('payment_alipay_order_status_id'));
				$this->model_checkout_order->updateSubOrderPayCode($order_sn, $trade_no);
			}*/

			if ($is_app == 1) return ['success',$order_sn];

			echo "success";	//Do not modified or deleted
		}else {
			$this->log('Alipay check failed');
			
			if ($is_app == 1) return 'pay fail';

			echo "fail";
		}
	}

	private function log($data) {
        if ($this->config->get('payment_alipay_log')) {
	        $this->logger->write($data);
        }
    }

    public function payFormForSm()
    {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrderByOrderSnUsePayInfoForMs($this->session->data['order_sn'],get_order_type($this->session->data['order_sn']));
		if (!$order_info)  return '';

		$config = array (
			'app_id'               => $this->config->get('payment_alipay_app_id'),
			'merchant_private_key' => $this->config->get('payment_alipay_merchant_private_key'),
			'notify_url'           => HTTP_SERVER . "payment_callback/alipay",
			'return_url'           => OPPCW_CALLBACK . "orderFinish?paycode=alipay",
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

    public function returnPay($data)
    {
    	$config = array (
			'app_id'               => $this->config->get('payment_alipay_app_id'),
			'rsa_private_key' 	   => $this->config->get('payment_alipay_merchant_private_key'),
			'ali_public_key' 	   => $this->config->get('payment_alipay_alipay_public_key'),
			'notify_url'           => HTTP_SERVER . "payment_callback/alipay",
			'return_url'           => $this->url->link('checkout/success'),
			'charset'              => "UTF-8",
			'sign_type'            => "RSA2",
			'gateway_url'          => $this->config->get('payment_alipay_test') == "sandbox" ? "https://openapi.alipaydev.com/gateway.do" : "https://openapi.alipay.com/gateway.do",
		);

		$refund_no 					= date('YmdHis',time()) . random_string(11);

		$postData = [
		    'out_trade_no' => '',
		    'trade_no' => isset($data['pay_code']) ? $data['pay_code'] : '',// 支付宝交易号， 与 out_trade_no 必须二选一
		    'refund_fee' => isset($data['amount']) ? $data['amount'] : 0,
		    'reason' => '用户申请退款',
		    'refund_no' => $refund_no,
		];

		try {
			$returnRes 		= Refund::run(Config::ALI_REFUND, $config, $postData);
			if ((isset($returnRes['code']) && $returnRes['code'] === '10000') || isset($returnRes['msg']) && $returnRes['msg'] === 'Success') {
				return 'success';
			}
		} catch (PayException $e) {
			wr(['$e->errorMessage()'=>$e->errorMessage()]);
		    //return $e->errorMessage();
		    return 'fail';
		}
    }
}