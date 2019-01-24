<?php
class ControllerApiRongcloud extends Controller {

	//注册融云用户信息
	public function userinfo() 
	{	
		$this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','seller_id'];
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

        //获取融云用户信息
        $customer_id            = (int)$req_data['seller_id'] > 0 ? (int)$req_data['seller_id'] : $this->customer->getId();

        $this->load->model('rongcloud/rongcloud');
        $uinfo                  = $this->model_rongcloud_rongcloud->getUser(['customer_id'=>$customer_id]);
        $avatar                 = $this->customer->getAvatar($customer['customer_id']);

        $data                   = [];
        if (!empty($uinfo)) {
            $data['rongcloud_uid']              = $uinfo['rongcloud_uid'];
            $data['rongcloud_nickname']         = $uinfo['rongcloud_nickname'];
            $data['rongcloud_avatar']           = $this->model_tool_image->resize($avatar, 100, 100);
            $data['rongcloud_token']            = $uinfo['rongcloud_token'];
        }else{

            $this->load->model('account/customer');
            $this->load->model('tool/image');

            $customer                =  $this->model_account_customer->getCustomer($customer_id);

            if (empty($customer)) {
                return $this->response->setOutput($this->returnData(['msg'=>'fail:userinfo is error']));
            }

            $fullname                = !empty($customer['fullname']) ? $customer['fullname'] : 'user_'.$customer['customer_id'];
            $rongcloud_uid           = md5($customer['customer_id'] . '~~!!1#@1qaz2wsx');

            $data['rongcloud_uid']              = $rongcloud_uid;
            $data['rongcloud_nickname']         = $fullname;
            $data['rongcloud_avatar']           = $this->model_tool_image->resize($avatar, 100, 100);

            $reguser                            = $this->load->controller('extension/interface/rongcloud/reguser',$data);
            if (!empty($reguser)) {
                if (isset($reguser['code']) && $reguser['code'] == '200') {

                    $data['rongcloud_token']            = $reguser['token'];

                    $save_data                  = [];
                    $save_data['validity_time'] = 0;
                    $save_data['customer_id']   = $customer['customer_id'];
                    
                    $this->model_rongcloud_rongcloud->addUser(array_merge($save_data,$data));

                    //建立聊天关系
                    if ((int)$req_data['seller_id'] > 0 && !$this->model_rongcloud_rongcloud->isChatUser(['seller_id'=>$req_data['seller_id']])) {
                        $this->model_rongcloud_rongcloud->addChatUser(['seller_id'=>$req_data['seller_id']]);
                    }

                }else{
                    return $this->response->setOutput($this->returnData(['msg'=>'fail:']));
                }
            }else{
                return $this->response->setOutput($this->returnData(['msg'=>'fail:rongcloud is error']));
            }
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
    }

    public function userlist()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','page','limit'];
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

        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;

        $this->load->model('rongcloud/rongcloud');

        $filter_data = array(
            'start'     => ($page - 1) * $limit,
            'limit'     => $limit
        );

        $totals         = $this->model_rongcloud_rongcloud->getChatUserTotals($filter_data);
        $lists          = $this->model_rongcloud_rongcloud->getChatUser($filter_data);
        $chat_user      = [];

        foreach ($lists as $key => $value) {
            $chat_user[]       =[
                'rongcloud_avatar'         => $value['rongcloud_avatar'],
                'rongcloud_uid'            => $value['rongcloud_uid'],
                'rongcloud_nickname'       => $value['rongcloud_nickname'],
            ];
        }

        $remainder                  = intval($totals - $limit * $page);
        $data                       = [];
        $data['total_page']         = ceil($totals/$limit);
        $data['remainder']          = $remainder >= 0 ? $remainder : 0;
        $data['lists']              = $chat_user;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=> $data ]));
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
