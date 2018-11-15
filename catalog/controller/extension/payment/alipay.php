<?php
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
			'return_url'           => $this->url->link('checkout/success'),
			'charset'              => "UTF-8",
			'sign_type'            => "RSA2",
			'gateway_url'          => $this->config->get('payment_alipay_test') == "sandbox" ? "https://openapi.alipaydev.com/gateway.do" : "https://openapi.alipay.com/gateway.do",
			'alipay_public_key'    => $this->config->get('payment_alipay_alipay_public_key'),
		);
		$out_trade_no = trim($order_info['order_id']) . '-' . time();

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

		$arr = $_POST;
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