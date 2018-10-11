<?php
/**
 * @package		OpenCart
 * @author		Meng Wenbin
 * @copyright	Copyright (c) 2010 - 2017, Chengdu Guangda Network Technology Co. Ltd. (https://www.opencart.cn/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.cn
 */

class ControllerExtensionPaymentWechatPay extends Controller {
    private $logger;
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->logger = new Log('payment.log');
    }

    public function index() {
        if ($this->config->get('is_weixin')) {
            return $this->webbasedPay();
        } else if ($this->config->get('is_mobile')) {
            return $this->h5Pay();
        } else {
            return $this->qrodePay();
        }
    }

	public function qrodePay() {
		$this->log('WECHAT QRCODE PAY');
		$data['button_confirm'] = $this->language->get('button_confirm');

		$data['redirect'] = $this->url->link('extension/payment/wechat_pay/qrcode');

		return $this->load->view('extension/payment/wechat_pay', $data);
	}

	public function webbasedPay() {
		$this->log('WECHAT WEBBASE PAY');
		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->language('extension/payment/wechat_pay');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order_id = trim($order_info['order_id']);
		$data['order_id'] = $order_id = $order_id . '-' . time();
		$subject = trim($this->config->get('config_name'));
		$currency = $this->config->get('payment_wechat_pay_currency');
		$total_amount = trim($this->currency->format($order_info['total'], $currency, '', false));
		$notify_url = HTTP_SERVER . "payment_callback/wechat_pay"; //$this->url->link('wechat_pay/callback');

		$options = array(
			'appid'			    =>  $this->config->get('payment_wechat_pay_app_id'),
			'appsecret'		    =>  $this->config->get('payment_wechat_pay_app_secret'),
			'mch_id'			=>  $this->config->get('payment_wechat_pay_mch_id'),
			'partnerkey'		=>  $this->config->get('payment_wechat_pay_mch_secret'),
			'cachepath'         =>  DIR_LOGS . 'wechat/'
		);

		$this->log('options: ' . var_export($options, true));
		\Wechat\Loader::config($options);
		$pay = new \Wechat\WechatPay();

		$open_id = Models\Customer::findOrFail($this->customer->getId())->authentications()->where('provider', 'weixin_gz')->get()->first()->uid;
		$result = $pay->getPrepayId($open_id, $subject, $order_id, $total_amount * 100, $notify_url, $trade_type = "JSAPI", NULL, $currency);

		$this->log('prepay_id: ' . var_export($result, true));
		if($result === FALSE){
			echo $pay->errMsg;
		} else {
            $data['options'] = $pay->createMchPay($result);
		    $this->log('MchPay: ' . var_export($data['options'], true));
		}

		$data['action_success'] = $this->url->link('checkout/success');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		return $this->load->view('extension/payment/wechat', $data);
	}

    public function h5Pay()
    {
		$this->log('WECHAT H5 PAY');
		$data['mweb_url'] = $this->mwebUrl();

		return $this->load->view('extension/payment/wechath5', $data);
    }

	public function mwebUrl() {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order_id = trim($order_info['order_id']);
		$data['order_id'] = $order_id = $order_id . '-' . time();
		$subject = trim($this->config->get('config_name'));
		$currency = $this->config->get('payment_wechat_pay_currency');
		$total_amount = trim($this->currency->format($order_info['total'], $currency, '', false));
		$notify_url = HTTP_SERVER . "payment_callback/wechat_pay"; //$this->url->link('wechat_pay/callback');

		$options = array(
            'appid'        =>  $this->config->get('payment_wechat_pay_app_id'),
            'appsecret'	   =>  $this->config->get('payment_wechat_pay_app_secret'),
            'mch_id'       =>  $this->config->get('payment_wechat_pay_mch_id'),
            'partnerkey'   =>  $this->config->get('payment_wechat_pay_mch_secret'),
            'cachepath'    =>  DIR_CACHE . 'wechat/'
		);
        $this->log('options: ' . var_export($options, true));

		\Wechat\Loader::config($options);
		$pay = new \Wechat\WechatPay();

		$result = $pay->getPrepayId(NULL, $subject, $order_id . '-' . time(), $total_amount * 100, $notify_url, "MWEB", NULL, $currency);

        $this->log('PrepayId: ' . var_export($options, true));
		if($result === FALSE){
            return $pay->errMsg;
		} else {
            return  $result . '&redirect_url=' . urlencode($this->url->link('extension/payment/wechat_pay/confirm'));
		}
    }

    public function confirm()
    {
		$this->load->language('extension/payment/wechat_pay');

		$data['order_id'] = $this->session->data['order_id'];

		$data['mweb_url'] = $this->mwebUrl();

		$data['action_success'] = $this->url->link('checkout/success');

        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/payment/wechath5_confirm', $data));
	}

	public function qrcode() {
		$this->load->language('extension/payment/wechat_pay');

		$this->document->setTitle($this->language->get('text_title'));
		$this->document->addScript('catalog/view/javascript/qrcode.js');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_qrcode'),
			'href' => $this->url->link('extension/payment/wechat_pay/qrcode')
		);

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order_id = trim($order_info['order_id']);
		$data['order_id'] = $order_id = $order_id . '-' . time();
		$subject = trim($this->config->get('config_name'));
		$currency = $this->config->get('payment_wechat_pay_currency');
		$total_amount = trim($this->currency->format($order_info['total'], $currency, '', false));
		$notify_url = HTTP_SERVER . "payment_callback/wechat_pay"; //$this->url->link('wechat_pay/callback');

		$options = array(
			'appid'			 =>  $this->config->get('payment_wechat_pay_app_id'),
			'appsecret'		 =>  $this->config->get('payment_wechat_pay_app_secret'),
			'mch_id'			=>  $this->config->get('payment_wechat_pay_mch_id'),
			'partnerkey'		=>  $this->config->get('payment_wechat_pay_mch_secret'),
			'cachepath'         =>  DIR_LOGS . 'wechat/'
		);

		\Wechat\Loader::config($options);
		$pay = new \Wechat\WechatPay();

		$result = $pay->getPrepayId(NULL, $subject, $order_id . '-' . time(), $total_amount * 100, $notify_url, $trade_type = "NATIVE", NULL, $currency);

		$data['error'] = '';
		$data['code_url'] = '';
		if($result === FALSE){
			$data['error_warning'] = $pay->errMsg;
		} else {
			$data['code_url'] = $result;
		}

		$data['action_success'] = $this->url->link('checkout/success');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/payment/wechat_pay_qrcode', $data));
	}

	public function isOrderPaid() {
		$json = array();

		$json['result'] = false;

		if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];

			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($order_id);

			if ($order_info['order_status_id'] == $this->config->get('payment_wechat_pay_completed_status_id')) {
				$json['result'] = true;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function callback() {
		$options = array(
			'appid'			 =>  $this->config->get('payment_wechat_pay_app_id'),
			'appsecret'		 =>  $this->config->get('payment_wechat_pay_app_secret'),
			'mch_id'			=>  $this->config->get('payment_wechat_pay_mch_id'),
			'partnerkey'		=>  $this->config->get('payment_wechat_pay_mch_secret'),
			'cachepath'         =>  DIR_LOGS . 'wechat/'
		);

		\Wechat\Loader::config($options);
		$pay = new \Wechat\WechatPay();
		$notifyInfo = $pay->getNotify();
        $this->log('Wechat Pay Notify: ' . var_export($notifyInfo, true));

		if ($notifyInfo === FALSE) {
			echo \Wechat\Lib\Tools::arr2xml(['return_code' => 'FAIL', 'return_msg' => $pay->errMsg]);
			$this->log('Wechat Pay Error: ' . $pay->errMsg);
		} else {
            $this->log('Wechat Pay Verify Success! notifyInfo='.var_export($notifyInfo, true));
			if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
				$order_id = (int)$notifyInfo['out_trade_no'];
				$this->load->model('checkout/order');
				$order_info = $this->model_checkout_order->getOrder($order_id);
				if ($order_info) {
					$order_status_id = $order_info["order_status_id"];
                    $this->log('order_status_id='.$order_status_id);
                    $this->log('config_unpaid_status_id='.$this->config->get('config_unpaid_status_id'));
                    if (!$order_status_id || $order_status_id == $this->config->get('config_unpaid_status_id')) {
                        $this->log('Wechat Pay Addorderhistory');
						$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_wechat_pay_completed_status_id'));
					}
				}
                $this->response->addHeader('Content-Type: application/xml');
                $this->response->setOutput(\Wechat\Lib\Tools::arr2xml(['return_code' => 'SUCCESS', 'return_msg' => 'DEAL WITH SUCCESS']));
			}
		}
	}

	private function log($data) {
        if ($this->config->get('payment_wechat_pay_log')) {
	        $this->logger->write($data);
        }
    }
}
