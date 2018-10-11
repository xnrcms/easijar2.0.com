<?php
class ControllerExtensionPaymentChinabank extends Controller {
    private $logger;
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->logger = new Log('payment.log');
    }

	public function index() {
		$this->language->load('extension/payment/chinabank');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$currency_code = 'CNY';
		$total = $this->currency->format($order_info['total'], $currency_code, '', false);

		$data['remark2'] = '[url:=' . $this->url->link('extension/payment/chinabank/callback');
		$data['v_mid'] = trim($this->config->get('payment_chinabank_id'));
		$data['key'] = trim($this->config->get('payment_chinabank_key'));
		$data['v_oid'] = trim($this->session->data['order_id']);
		$data['v_amount'] = round($total,2);
		$data['v_moneytype'] = $currency_code;
		$data['v_url'] = $this->url->link('extension/payment/chinabank/callback');
		$data['text'] = $data['v_amount'].$data['v_moneytype'].$data['v_oid'].$data['v_mid'].$data['v_url'].$data['key'];
		$data['v_md5info'] = strtoupper(md5($data['text']));
		$data['remark1'] = $order_info['order_id'] . '-' . time();

		$data['cancel_return'] = $this->url->link('checkout/checkout', '', true);

		return $this->load->view('extension/payment/chinabank', $data);
	}
					
	public function callback() {
	    $this->log('chinabank enter callback');
		$this->load->model('checkout/order');
		
		$order_id  = (int)trim($_POST['v_oid']);
		
		$order_info = $this->model_checkout_order->getOrder($order_id);

		$v_oid     =trim($_POST['v_oid']);
		$v_pmode   =trim($_POST['v_pmode']);
		$v_pstatus =trim($_POST['v_pstatus']);
		$v_pstring =trim($_POST['v_pstring']);
		$v_amount  =trim($_POST['v_amount']);
		$v_moneytype  =trim($_POST['v_moneytype']);
		$remark1   =trim($_POST['remark1' ]);
		$remark2   =trim($_POST['remark2' ]);
		$v_md5str  =trim($_POST['v_md5str' ]);
		$key = trim($this->config->get('payment_chinabank_key'));
		$pending = $this->config->get('payment_chinabank_completed_status_id');
		$failed = 10;

		$md5string = strtoupper(md5($v_oid . $v_pstatus . $v_amount . $v_moneytype . $key));
		if ($v_md5str == $md5string) {
	        $this->log('chinabank verify success! v_pstatus = ' . $v_pstatus);
			if ($v_pstatus == "20") {
				// Success page redirect
				$this->model_checkout_order->addOrderHistory($order_id, $pending);
				$this->response->redirect($this->url->link('checkout/success'));
			} else {
				 // Failure
//				 $this->model_checkout_order->addOrderHistory($order_id, $failed);
//				 $this->response->redirect($this->url->link('checkout/failure'));
			}
		}
	}

	private function log($data) {
        if ($this->config->get('payment_chinabank_log')) {
	        $this->logger->write($data);
        }
    }
}
