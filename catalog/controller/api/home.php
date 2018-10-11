<?php
class ControllerApiHome extends Controller {
	public function index() {
		$allowKey		= [];
		$req_data 		= $this->dataFilter($allowKey);

		if ($this->checkSign($req_data)) {
			
			//获取幻灯片
			$this->load->model('design/banner');
		    $this->load->model('tool/image');

		    $data['banners'] 	= [];
		    $results 			= $this->model_design_banner->getBanner(11);
		    if (!empty($results)) {
		    	foreach ($results as $result) {
			      if (is_file(DIR_IMAGE . $result['image'])) {
			        $data['banners'][] = array(
			          'title' => $result['title'],
			          'link'  => $result['link'],
			          'image' => $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height'])
			        );
			      }
			    }
		    }

		    $data['catrgory'] 	= [];
		    $this->load->model('setting/module');

		    //获取分类
		    $module_id 					= 51;
		    $code 						= 'icon';
		    $setting_info 				= $this->model_setting_module->getModule($module_id);

			if ($setting_info && $setting_info['status']) {
				$setting_info['module_id'] 	= $module_id;
				$setting_info['position'] 	= 'content_top';
				$cat 						= $this->load->controller('extension/module/' . $code, $setting_info,true);

				if ($cat) {
					$data['catrgory'] = $cat;
				}
			}

		    $data 		= $this->returnData(['code'=>'200','msg'=>'success','data'=>$data]);
		}else{

			$data 		= $this->returnData(['msg'=>'fail:sign error']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	public function uhash()
	{
		$allowKey		= ['code'];
		$req_data 		= $this->dataFilter($allowKey);

		if ($this->checkSign($req_data)) {

			if (isset($req_data['code']) && strlen($req_data['code']) === 256 || strlen($req_data['code']) === 32) {
				//防止时时刷新
				$cache_key 				= md5($req_data['code']);
				$cache_data 			= $this->cache->get($cache_key);
				$cache_data 			= !empty($cache_data) ? json_decode($cache_data,true) : [];

				if (isset($cache_data['expiry_time']) && $cache_data['expiry_time'] >= time()) {
					$json 				= ['code'=>202,'msg'=>'fail:frequent requests','data'=>''];
				}else{
					$json 				= ['code'=>200,'msg'=>'success','data'=>$this->makeHash($cache_key)];
					$this->cache->set($cache_key, json_encode(['expiry_time'=>time() + 10]));
				}
			}else{
				$json 					= ['code'=>201,'msg'=>'fail:code is error','data'=>''];
			}
		}else{

			$json 		= ['code'=>202,'msg'=>'fail:sign error','data'=>''];
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function makeHash($code = '')
	{
		$this->load->model('account/api');

		$api_info 			= $this->model_account_api->getApiInfoByCode($code);

		if ($api_info) {
			$api_id 				= $api_info['api_id'];
			$username 				= $api_info['username'];
			$key 					= $api_info['key'];

			/*$user_data				= ['username'=>$username,'key'=>$key,'status'=>(int)$api_info['status'],'code'=>$code];
			$hash 					= $this->encryption->encrypt($key, json_encode($user_data));*/

			/*
			if (!(isset($api_info['hash']) && !empty($api_info['hash']) && $api_info['hash'] === $hash)) return '';
			*/

		}else{
			
			$username 				= md5(random_string(32,7) . (time()+10));
			$key 					= md5(random_string(32,7) . time() . $this->config->get('config_encryption'));

			$user_data				= ['username'=>$username,'key'=>$key,'status'=>1,'code'=>$code];
			//$hash 					= $this->encryption->encrypt($key, json_encode($user_data));

			$api_id 				= $this->model_account_api->addApi($user_data);
		}

		$session 					= new Session($this->config->get('session_engine'), $this->registry);
		$session->start();
		
		$this->model_account_api->addApiSession($api_id, $session->getId(), $this->request->server['REMOTE_ADDR']);
		
		$session->data['api_id'] 			= $api_id;
		$session->data['api_name'] 			= $username;
		$session->data['api_key'] 			= $key;
		$session->data['api_token'] 		= $session->getId();

		return $session->getId();
	}

	public function testdf()
	{
		require "vendor/yiliandf/lib/YiLian.class.php";
    	$yilian = new YiLian();
    	$data = array(
		    'ACC_NO'=>'6227003811930123771',
		    'ACC_NAME'=>'笪飞亚',
			'ID_NO'=>'',
			'MOBILE_NO'=>'',
		//    'ACC_PROVINCE'=>'',
		//    'ACC_CITY'=>'',
		    'AMOUNT'=>'1000.00',
		    'CNY'=>'CNY',
		    'PAY_STATE'=>'',
		    'MER_ORDER_NO'=>'123456'
		);

		$res = $yilian->pay($data);
    	print_r($res);exit();
	}
}
