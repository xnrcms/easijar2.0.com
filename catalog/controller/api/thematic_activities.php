<?php
class ControllerApiThematicActivities extends Controller {

	//新人专题活动主页
	public function new_people() 
	{	
		$this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $this->load->model('extension/total/coupon');
        $isGet                  = $this->model_extension_total_coupon->isGetNewPeopleCouponTotal();

        $json['currency']       = $this->currency->getSymbolLeft($this->session->data['currency']);
        $json['is_get']         = (int)$isGet >0 ? 1 : 0;
        $json['coupon_list']    = [];
        $json['product_list']   = [];

        //新人优惠券
        $coupons                = $this->load->controller('extension/total/coupon/getNewPeopleCouponForApi');
        $coupon                 = [];

        foreach ($coupons as $key => $value)
        {
            $discount           = sprintf("%.2f", $value['discount']);
            if ($value['type'] == 2) {
                $discount       = round($value['discount']).'%';
            } else {
                $discount       = trim($this->currency->format($value['discount'], $this->session->data['currency']),$json['currency']);
            }

            $json['coupon_list'][]            = [
                'coupon_id' =>$value['coupon_id'],
                'name'      =>$value['name'],
                'type'      =>(int)$value['type'],
                'discount'  =>$discount,
            ];
        }

        $this->load->model('setting/module');

        //推荐商品
        $module_id                  = 58;
        $setting_info               = $this->model_setting_module->getModule($module_id);
        $setting_info['module_id']  = $module_id;
        $setting_info['api']        = true;

        $results                    = $this->load->controller('extension/module/thematic_activities', $setting_info,true);

        $recommend                  = [];
        if (isset($results['products']) && !empty($results['products'])) {
            foreach ($results['products'] as $rval)
            {
                $price              = trim($rval['price'],$json['currency']);
                $special            = !empty($rval['special']) ? trim($rval['special'],$json['currency']) : $price;

                $recommend[]        = [
                    'product_id'    => $rval['product_id'],
                    'name'          => $rval['name'],
                    'thumb'         => $rval['thumb'],
                    'price'         => $price,
                    'special'       => $special,
                    'rating'        => $rval['rating'],
                    'reviews'       => $rval['reviews'],
                ];
            }
        }
        
        $json['product_list']        = $recommend;
        
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    //低价包邮
    public function cheap_mail() 
    {   
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $json['currency']       = $this->currency->getSymbolLeft($this->session->data['currency']);
        $json['product_list']   = [];

        $this->load->model('setting/module');

        //推荐商品
        $module_id                  = 57;
        $setting_info               = $this->model_setting_module->getModule($module_id);
        $setting_info['module_id']  = $module_id;
        $setting_info['api']        = true;

        $results                    = $this->load->controller('extension/module/thematic_activities', $setting_info,true);

        $recommend                  = [];
        if (isset($results['products']) && !empty($results['products'])) {
            foreach ($results['products'] as $rval)
            {
                $price              = trim($rval['price'],$json['currency']);
                $special            = !empty($rval['special']) ? trim($rval['special'],$json['currency']) : $price;

                $recommend[]        = [
                    'product_id'    => $rval['product_id'],
                    'name'          => $rval['name'],
                    'thumb'         => $rval['thumb'],
                    'price'         => $special,
                    //'special'       => $special,
                    'rating'        => $rval['rating'],
                    'reviews'       => $rval['reviews'],
                ];
            }
        }

        $json['product_list']        = $recommend;
        
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    //折扣专区
    public function discount_zone() 
    {   
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $json['currency']       = $this->currency->getSymbolLeft($this->session->data['currency']);
        $json['product_list']   = [];

        $this->load->model('setting/module');

        //推荐商品
        $module_id                  = 59;
        $setting_info               = $this->model_setting_module->getModule($module_id);
        $setting_info['module_id']  = $module_id;
        $setting_info['api']        = true;

        $results                    = $this->load->controller('extension/module/thematic_activities', $setting_info,true);

        $recommend                  = [];
        if (isset($results['products']) && !empty($results['products'])) {
            foreach ($results['products'] as $rval)
            {
                $price              = trim($rval['price'],$json['currency']);
                $special            = !empty($rval['special']) ? trim($rval['special'],$json['currency']) : $price;

                $recommend[]        = [
                    'product_id'    => $rval['product_id'],
                    'name'          => $rval['name'],
                    'thumb'         => $rval['thumb'],
                    'price'         => $price,
                    'special'       => $special,
                    'rating'        => $rval['rating'],
                    'reviews'       => $rval['reviews'],
                    'discount'      => $rval['discount'] > 0 ? $rval['discount'] : 0,
                ];
            }
        }
        
        $json['product_list']        = $recommend;
        
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    //新人红包领取接口
    public function get_coupon_for_new()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('extension/total/coupon');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

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

        $this->load->model('extension/total/coupon');
        $this->load->model('account/order');
        
        //判断是否已经领取
        $isGet      = $this->model_extension_total_coupon->isGetNewPeopleCouponTotal();
        if ((int)$isGet > 0) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_coupon_get')]));
        }

        //判断是不是新人
        $order_total       = $this->model_account_order->getCustomerTotalOrders();
        if ((int)$order_total > 0) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_coupon_user')]));
        }

        //入库优惠券
        $this->load->model('extension/total/coupon');
        $coupons    = $this->model_extension_total_coupon->getNewPeopleCoupons();
        if (!empty($coupons)) {
            $this->model_extension_total_coupon->addNewPeopleCoupons($coupons);
        }else{
            return $this->response->setOutput($this->returnData(['msg'=>'fail:coupons is empty']));
        }
 
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success']));
    }

    //用户资料详情
	public function updata() 
	{	
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('account/account');

        $allowKey       = ['api_token','field','updata'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

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

        $field 			= isset($req_data['field']) ? $req_data['field'] : '';
        $val 			= isset($req_data['updata']) ? $req_data['updata'] : '';

        if (!in_array($field, ['avatar','fullname','brithday','gender','telephone'])) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:field is error']));
        }

        if (empty($data)) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:data is empty']));
        }

        if ($field 	== 'avatar')
        {
        	$this->load->language('tool/upload');

        	if (!empty($this->request->files['files']['name']) && is_file($this->request->files['files']['tmp_name'])) {

	        	// Sanitize the filename
	            $filename 				= basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode($this->request->files['files']['name'], ENT_QUOTES, 'UTF-8')));

	            // Validate the filename length
	            if ((utf8_strlen($filename) < 2) || (utf8_strlen($filename) > 64)) {
                    return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filename')]));
	            }

	            // Allowed file extension types
	            $allowed = array();

	            $extension_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('config_file_ext_allowed'));

	            $filetypes = explode("\n", $extension_allowed);

	            foreach ($filetypes as $filetype) {
	                $allowed[] = trim($filetype);
	            }

	            if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
	        		return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filetype')]));
	            }

	            // Allowed file mime types
	            $allowed 				= [];
	            $mime_allowed 			= preg_replace('~\r?\n~', "\n", $this->config->get('config_file_mime_allowed'));
	            $filetypes 				= explode("\n", $mime_allowed);

	            foreach ($filetypes as $filetype) {
	                $allowed[] = trim($filetype);
	            }

	            if (!in_array($this->request->files['files']['type'], $allowed)) {
	        		return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filetype')]));
	            }

	            // Check to see if any PHP files are trying to be uploaded
	            $content 				= file_get_contents($this->request->files['files']['tmp_name']);

	            if (preg_match('/\<\?php/i', $content)) {
	        		return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filetype')]));
	            }

	            // Return any upload error
	            if ($this->request->files['files']['error'] != UPLOAD_ERR_OK) {
	        		return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_upload_' . $this->request->files['files']['error'])]));
	            }

	            $file 								= $this->customer->getId() . '.jpg';

	            move_uploaded_file($this->request->files['files']['tmp_name'], DIR_IMAGE . 'avatar/' . $file);

	            $val					            = image_resize('avatar/' . $file) . '?t=' . time();
        	}else{
        		return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_upload')]));
        	}
        }else{

        	$this->load->model('account/customer');
            $this->load->language('account/edit');

        	if ($field 	== 'fullname') {
                if ((utf8_strlen(trim($val)) < 2) || (utf8_strlen(trim($val)) > 10)) {
                    return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_fullname')]));
                }
        		$this->model_account_customer->editFullName($val);
        	}

        	if ($field 	== 'brithday') {
                $bri            = explode('-', $val);
                if ( (count($bri) != 3) || ( strlen($bri[0]) != 4 ) || ( strlen($bri[1]) != 2 ) || ( strlen($bri[2]) != 2 ) ) {
                    return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_brithday')]));
                }
        		$this->model_account_customer->editBrithday($val);
        	}

            if ($field  == 'gender') {
                $gender            = (int)$val;
                $gender            = in_array($gender, [0,1,2]) ? $gender : 0;
                $this->model_account_customer->editGender($val);
            }

            if ($field  == 'telephone') {
                //手机号设置 
                $updata         = explode('#', $val);
                if (count($updata) != 2) {
                    return $this->response->setOutput($this->returnData(['msg'=>'fail:updata is error']));
                }

                $val            = $updata[0];
                $smscode        = $updata[1];

                //手机号格式错误
                $telephones     = explode('-', $val);
                if (count($telephones) < 2 || !strlen($telephones[0]) || !strlen($telephones[1] || strlen($telephones[0]) > 4)) {
                    return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_telephone')]));
                }

                //校验用户手机号是否被占用
                if ($this->model_account_customer->getTotalCustomersByTelephone($val)) {
                    return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_exists_telephone')]));
                }

                //验证码校验
                $keys                                       = md5('smscode-' . $val . '-1');
                if (!isset($this->session->data['smscode'][$keys]['code']) || $smscode != $this->session->data['smscode'][$keys]['code'] || $this->session->data['smscode'][$keys]['expiry_time'] < time()) {
                    return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_smscode')]));
                }

                unset($this->session->data['smscode']);
                
                $this->model_account_customer->editTelephone($val);
            }
        }

        $json['field'] 				         = $field;
        $json['updata']                      = $val;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }
}
