<?php
class ControllerApiCoupon extends Controller {
	public function index()
	{
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('extension/total/coupon');

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
        	'sort' 			=> 'date_added',
        	'order' 		=> 'ASC',
            'start' 		=> ($page - 1) * $limit,
            'limit' 		=> $limit,
        ];

        $this->load->model('customercoupon/coupon');
        $this->load->model('tool/image');

        $totals             = $this->model_customercoupon_coupon->getCouponsTotalByCustomerIdForApi($filter_data);
        $results 			= $this->model_customercoupon_coupon->getCouponsByCustomerIdForApi($filter_data);
        $coupon 			= [];
        $seller_ids         = [];          

        if (!empty($results)) {
        	foreach ($results as $key => $value) {
        		$avatar                 		= !empty($value['avatar']) ? $value['avatar'] : 'no_image.png';
		        $value['avatar']        		= $this->model_tool_image->resize($avatar, 100, 100);
                $value['discount']              = sprintf("%.2f", $value['discount']);
                $value['store_name']            = !empty($value['store_name']) ? htmlspecialchars_decode($value['store_name']) : 'EasiJAR';

		        $overdue 	= ((int)$value['over_time'] > 0 && (int)$value['uses_limit'] > (int)$value['status']) ? 1 : 0;

		        if ($value['type'] == 2) {
                    $discount = '-'.round($value['discount']).'%';
                } else {
                    $discount = $this->currency->format($value['discount'], $this->session->data['currency']);
                }

                $order_total                    = $this->currency->format($value['order_total'], $this->session->data['currency']);
                $name                           = sprintf($this->language->get('text_coupon_explain'), $order_total);
                $value['over_time']             = $overdue;
                $value['discount']              = $discount;
                $value['name']                  = $name;

	            unset($value['type']);
	            //unset($value['over_time']);
	            //unset($value['total']);

		        $coupon[$overdue.'_'.$value['seller_id']]['seller_id'] 	= $value['seller_id'];
		        $coupon[$overdue.'_'.$value['seller_id']]['store_name']	= $value['store_name'];
		        $coupon[$overdue.'_'.$value['seller_id']]['avatar']		= $value['avatar'];
		        $coupon[$overdue.'_'.$value['seller_id']]['coupon'][] 	= $value;
        	}
        }

        krsort($coupon);

        $coupon     = array_values($coupon);

        $remainder                  = intval($totals - $limit * $page);
        $data['total_page']         = ceil($totals/$limit);
        $data['remainder']          = $remainder >= 0 ? $remainder : 0;
        $data['coupon']             = $coupon;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
	}

	public function lists()
	{
        $this->response->addHeader('Content-Type: application/json');

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
        
        //获取商家优惠券
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

            if ($result['type'] == 2) {
                $result['discount']          = round($result['discount']).'%';
            } else {
                $result['discount']          = $this->currency->format($result['discount'], $this->session->data['currency']);
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
        $this->response->addHeader('Content-Type: application/json');

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

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $coupon_id 		= isset($req_data['coupon_id']) ? (int)$req_data['coupon_id'] : 0;
        
        $this->load->model('marketing/coupon');

        //获取优惠券
        $coupon_info 	= $this->model_marketing_coupon->getCoupon2($coupon_id);
        $get_limit      = (int)$coupon_info['get_limit'];

        //优惠券失效
        if ( !($coupon_info['date_start'] <= date('Y-m-d', time()) && $coupon_info['date_end'] >= date('Y-m-d', time())) ) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:coupon is invalid']));
        }

        if ( $coupon_info['status'] != 1 ) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:coupon is unavailable']));
        }

        //if ($this->customer->isLogged()) {
        	if ( $get_limit >0 && $this->model_marketing_coupon->isGetCoupon($coupon_id) >= $get_limit ) {
	        	return $this->response->setOutput($this->returnData(['msg'=>'fail:coupon already gets']));
	        }

	        $is_get    = $this->model_marketing_coupon->insertCoupon($coupon_id);
        /*}else{
        	if (!isset($this->session->data['getCouponList'])) $this->session->data['getCouponList'] = [];

        	if (in_array($coupon_id, $this->session->data['getCouponList'])) {
        		return $this->response->setOutput($this->returnData(['msg'=>'fail:coupon already gets']));
        	}

        	$this->session->data['getCouponList'][] 	= $coupon_id;
        }*/

        if ($is_get) {
            return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'gets coupon success']));
        }else{
            return $this->response->setOutput($this->returnData(['msg'=>'fail:gets error']));
        }
	}
}
