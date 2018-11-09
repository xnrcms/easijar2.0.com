<?php
class ControllerExtensionTotalCoupon extends Controller {
	public function index() {
		if ($this->config->get('total_coupon_status')) {
			$this->load->language('extension/total/coupon');

			if (isset($this->session->data['coupon'])) {
				$data['coupon'] = $this->session->data['coupon'];
			} else {
				$data['coupon'] = '';
			}

            $data['entry_select_coupon'] = $this->language->get('entry_select_coupon');
            $this->load->model("customercoupon/coupon");
            $this->load->model('extension/total/coupon');
            $customer_coupons = $this->model_customercoupon_coupon->getCouponsByCustomer($this->customer, true);
            if($customer_coupons){
                foreach($customer_coupons as $key => $value){
                    $coupon_info = $this->model_extension_total_coupon->getCoupon($value['code']);
                    if(!$coupon_info){
                        unset($customer_coupons[$key]);
                    }
                }
            }
            $data['customer_coupons'] = $customer_coupons ? $customer_coupons : array();

			return $this->load->view('extension/total/coupon', $data);
		}
	}

	public function coupon() {
		$this->load->language('extension/total/coupon');

		$json = array();

		$this->load->model('extension/total/coupon');

		if (isset($this->request->post['coupon'])) {
			$coupon = $this->request->post['coupon'];
		} else {
			$coupon = '';
		}

		$coupon_info = $this->model_extension_total_coupon->getCoupon($coupon);

		if (empty($this->request->post['coupon'])) {
			$json['error'] = $this->language->get('error_empty');

			unset($this->session->data['coupon']);
		} elseif ($coupon_info) {
			$this->session->data['coupon'] = $this->request->post['coupon'];

			$this->session->data['success'] = $this->language->get('text_success');

			$json['redirect'] = $this->url->link('checkout/cart');
		} else {
			$json['error'] = $this->language->get('error_coupon');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function useCouponForApi($couponcode = '')
	{
		$this->load->language('extension/total/coupon');

		$coupon_info 	= $this->model_extension_total_coupon->getCoupon($couponcode);
		$ret 			= [];

		if (empty($couponcode)) {

			$ret['error'] = $this->language->get('error_empty');
			
		} elseif ($coupon_info) {

			$this->session->data['coupon'][$coupon_info['seller_id']] = $couponcode;
			$ret['success'] = 'success';
		} else {
			$ret['error'] = $this->language->get('error_coupon');
		}

		return $ret;
	}

	public function getCouponForApi($data) {
		if ($this->config->get('total_coupon_status'))
		{
            $this->load->model("customercoupon/coupon");
            $this->load->model('extension/total/coupon');

            $customer_coupons 			= $this->model_customercoupon_coupon->getCouponsByCustomer($this->customer, true,(isset($data['seller_id']) ? (int)$data['seller_id'] : 0));
            if($customer_coupons){
                foreach($customer_coupons as $key => $value){
                    $coupon_info 		= $this->model_extension_total_coupon->getCoupon($value['code']);
                    if(!$coupon_info){
                        unset($customer_coupons[$key]);
                    }
                }
            }

			return $customer_coupons ? $customer_coupons : array();;
		}
	}
}
