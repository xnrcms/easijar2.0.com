<?php
class ControllerApiAccount extends Controller {

	//用户中心首页
	public function index() 
	{	
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('account/account');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('tool/image');

        $json['account_info'] 				= [];

        $avatar 							= !empty($this->customer->getAvatar()) ? $this->customer->getAvatar() : 'no_image.png';
        $account_info['avatar'] 			= $this->model_tool_image->resize($avatar, 100, 100);
        $account_info['uname'] 				= !empty($this->customer->getFullName()) ? $this->customer->getFullName() : 'not set nickname';

        $json['account_info'] 				= $account_info;
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    //用户资料详情
	public function details() 
	{	
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('account/account');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('tool/image');

        $json['account_info'] 				= [];

        $avatar 							= !empty($this->customer->getAvatar()) ? $this->customer->getAvatar() : 'no_image.png';
        $account_info['avatar'] 			= $this->model_tool_image->resize($avatar, 100, 100);
        $account_info['uname'] 				= !empty($this->customer->getFullName()) ? $this->customer->getFullName() : 'not set nickname';

        $json['account_info'] 				= $account_info;
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
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
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }
        
        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $field 			= isset($req_data['field']) ? $req_data['field'] : '';
        $val 			= isset($req_data['updata']) ? $req_data['updata'] : '';

        if (!in_array($field, ['avatar','fullname','brithday'])) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:field is error']));
        }

        if (empty($data)) {
        	return $this->response->setOutput($this->returnData(['msg'=>'fail:data is empty']));
        }

        if ($field 	== 'avatar')
        {
        	$this->load->language('tool/upload');

        	if (!empty($this->request->files[$val]['name']) && is_file($this->request->files[$val]['tmp_name'])) {

	        	// Sanitize the filename
	            $filename 				= basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode($this->request->files[$val]['name'], ENT_QUOTES, 'UTF-8')));

	            // Validate the filename length
	            if ((utf8_strlen($filename) < 2) || (utf8_strlen($filename) > 64)) {
	                $json['error'] = $this->language->get('error_filename');
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

	            if (!in_array($this->request->files[$val]['type'], $allowed)) {
	        		return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filetype')]));
	            }

	            // Check to see if any PHP files are trying to be uploaded
	            $content 				= file_get_contents($this->request->files[$val]['tmp_name']);

	            if (preg_match('/\<\?php/i', $content)) {
	        		return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filetype')]));
	            }

	            // Return any upload error
	            if ($this->request->files[$val]['error'] != UPLOAD_ERR_OK) {
	        		return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_upload_' . $this->request->files[$val]['error'])]));
	            }

	            $file 								= $this->customer->getId() . '.jpg';

	            move_uploaded_file($this->request->files[$val]['tmp_name'], DIR_IMAGE . 'avatar/' . $file);

	            $val					            = image_resize('avatar/' . $file);
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

        }

        $json['field'] 				         = $field;
        $json['updata']                      = $val;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }
}