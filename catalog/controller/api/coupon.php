<?php
class ControllerApiCoupon extends Controller {
	public function index()
	{
		$allowKey       = ['api_token','page','dtype','seller_id','limit'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();
        $data 			= [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $dtype 				= ( !isset($req_data['dtype']) || (int)$req_data['dtype'] <= 0 ) ? 0 : (int)$req_data['dtype'];
        $seller_id 			= ( !isset($req_data['seller_id']) || (int)$req_data['seller_id'] <= 0 ) ? 0 : (int)$req_data['seller_id'];
        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;

        $filter_data 		= [
        	'customer_id'	=> $this->customer->getId(),
        	'seller_id'		=> $seller_id,
        	'dtype' 		=> $dtype,
        	'sort' 			=> 'over_time',
        	'order' 		=> 'DESC',
            'start' 		=> ($page - 1) * $limit,
            'limit' 		=> $limit,
        ];

        $this->load->model('customercoupon/coupon');
        $this->load->model('tool/image');

        $totals             = $this->model_customercoupon_coupon->getCouponsTotalByCustomerIdForApi($filter_data);
        $results 			= $this->model_customercoupon_coupon->getCouponsByCustomerIdForApi($filter_data);
        $coupon 			= [];
        if (!empty($results)) {
        	foreach ($results as $key => $value) {
        		$avatar                 		= !empty($value['avatar']) ? $value['avatar'] : 'no_image.png';
		        $value['avatar']        		= $this->model_tool_image->resize($avatar, 100, 100);
                $value['discount']              = sprintf("%.2f", $value['discount']);

		        $overdue 						= $value['over_time'] > 0 ? 1 : 0;

		        if ($value['type'] == 'F') {
                	$value['discount'] 			= $this->currency->format($value['discount'], $this->session->data['currency']);
	            } else {
	                $value['discount'] 			= '-'.round($value['discount']).'%';
	            }

                $value['over_time']             = $overdue;
                
	            unset($value['type']);
	            //unset($value['over_time']);
	            unset($value['total']);

		        $coupon[$value['seller_id'].'_'.$overdue]['seller_id'] 	= $value['seller_id'];
		        $coupon[$value['seller_id'].'_'.$overdue]['store_name']	= $value['store_name'];
		        $coupon[$value['seller_id'].'_'.$overdue]['avatar']		= $value['avatar'];
		        $coupon[$value['seller_id'].'_'.$overdue]['coupon'][] 	= $value;
        	}
        }

        sort($coupon);

        $remainder                  = intval($totals - $limit * $page);
        $data['total_page']         = ceil($totals/$limit);
        $data['remainder']          = $remainder >= 0 ? $remainder : 0;
        $data['coupon']             = $coupon;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
	}

	public function lists()
	{
		$allowKey       = ['api_token','seller_id','page','limit'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();
        $data 			= [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;

        $data['platform']	= [];
        $data['business']	= [];

        $this->load->model('marketing/coupon');
		$coupons 		= [];

		$filter_data 	= [
			'sort'  => 'discount',
			'order' => 'DESC',
			'start' => ($page - 1) * $limit,
			'limit' => $limit,
			'date' => 1
		];

        $seller_id                  = $req_data['seller_id'];
        //获取用户已经领取的优惠券
        $getCouponList              = [];
        if ($this->customer->isLogged()) {
            $coupons            = $this->model_marketing_coupon->getCustomerCoupons();
            foreach ($coupons as $value) {
                $getCouponList[]    = $value['coupon_id'];
            }
        }else{
            $getCouponList    =  isset($this->session->data['getCouponList']) ? $this->session->data['getCouponList'] : [];
        }

        $totals  = $this->model_marketing_coupon->getCouponsTotals($filter_data,$seller_id);
        //商家优惠券列表
		$results = $this->model_marketing_coupon->getCoupons($filter_data,$seller_id);
		foreach ($results as $result) {

            $result['discount']              = sprintf("%.2f", $result['discount']);

            if ($result['type'] == 'F') {
                $result['discount']          = $this->currency->format($result['discount'], $this->session->data['currency']);
            } else {
                $result['discount']          = round($result['discount']).'%';
            }

			$data['business'][] = array(
				'coupon_id'  => $result['coupon_id'],
				'name'       => $result['name'],
				'discount'   => $result['discount'],
                'get_status' => in_array($result['coupon_id'], $getCouponList) ? 1 : 0,
				'date_start' => date($this->language->get('date_format_short'), strtotime($result['date_start'])),
				'date_end'   => date($this->language->get('date_format_short'), strtotime($result['date_end'])),
			);
		}

        $remainder                  = intval($totals - $limit * $page);
        $data['total_page']         = ceil($totals/$limit);
        $data['remainder']          = $remainder >= 0 ? $remainder : 0;

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
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
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
