<?php
class ControllerApiCoupon extends Controller {
	public function index() {
		$this->load->language('api/coupon');

		// Delete past coupon in case there is an error
		unset($this->session->data['coupon']);

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/total/coupon');

			if (isset($this->request->post['coupon'])) {
				$coupon = $this->request->post['coupon'];
			} else {
				$coupon = '';
			}

			$coupon_info = $this->model_extension_total_coupon->getCoupon($coupon);

			if ($coupon_info) {
				$this->session->data['coupon'] = $this->request->post['coupon'];

				$json['success'] = $this->language->get('text_success');
			} else {
				$json['error'] = $this->language->get('error_coupon');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function lists()
	{
		$allowKey       = ['api_token','seller_id','page'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();
        $data 			= [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $page 				= ( !isset($req_data['page']) || (int)$req_data['page'] <= 0 ) ? 1 : (int)$req_data['page'];

        $data['platform']	= [];
        $data['business']	= [];

        $this->load->model('marketing/coupon');
		$coupons 		= [];

		$filter_data 	= [
			'sort'  => 'discount',
			'order' => 'DESC',
			'start' => ($page - 1) * 5,
			'limit' => 5,
			'date' => 1
		];

		$results = $this->model_marketing_coupon->getCoupons($filter_data,$req_data['seller_id']);

		foreach ($results as $result) {
			$data['business'][] = array(
				'coupon_id'  => $result['coupon_id'],
				'name'       => $result['name'],
				'discount'   => $result['discount'],
				'date_start' => date($this->language->get('date_format_short'), strtotime($result['date_start'])),
				'date_end'   => date($this->language->get('date_format_short'), strtotime($result['date_end'])),
			);
		}

		$json 			= $this->returnData(['code'=>'200','msg'=>'success','data'=>$data]);
        return $this->response->setOutput($json);
	}

	public function gets()
	{
		$allowKey       = ['api_token','coupon_id'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();
        $data 			= [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $coupon_id 		= isset($req_data['coupon_id']) ? (int)$req_data['coupon_id'] : 0;
        
        $this->load->model('marketing/coupon');

        //获取优惠券
        $coupon_info 	= $this->model_marketing_coupon->getCoupon($coupon_id);

        //优惠券失效
        if ( !($coupon_info['date_start'] <= date('Y-m-d', time()) && $coupon_info['date_end'] >= date('Y-m-d', time())) ) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:coupon is invalid']));
        }

        if ( $coupon_info['status'] != 1 ) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:coupon is unavailable']));
        }

        if ($this->customer->isLogged()) {
        	if ( $this->model_marketing_coupon->isGetCoupon($coupon_id,$this->customer->getId() ) > 0 ) {
	        	return $this->response->setOutput($this->returnData(['msg'=>'fail:coupon already gets']));
	        }

	        $this->model_marketing_coupon->insertCoupon($coupon_id,$this->customer->getId());
        }else{
        	if (!isset($this->session->data['getCouponList'])) $this->session->data['getCouponList'] = [];

        	if (in_array($coupon_id, $this->session->data['getCouponList'])) {
        		return $this->response->setOutput($this->returnData(['msg'=>'fail:coupon already gets']));
        	}

        	$this->session->data['getCouponList'][] 	= $coupon_id;
        }

        $json 			= $this->returnData(['code'=>'200','msg'=>'success','data'=>'gets coupon success']);
        return $this->response->setOutput($json);
	}
}
