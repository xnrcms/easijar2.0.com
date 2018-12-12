<?php
class ControllerApiHash extends Controller
{
	public function index() 
	{
		$req_data 			= array_merge($this->request->get,$this->request->post);
		$hash 				= '';

		/*$this->config->set('data_sign_key','a2a59c3e76604f1093f701ca670acf61');
		$this->config->set('data_api_id','f00344bad92dc5ab7b9a4c4d088f6485');*/

		if (!empty($req_data)) {
			$signKey 		= 'a2a59c3e76604f1093f701ca670acf61';

	        if(isset($req_data['hash'])) unset($req_data['hash']);
	        if(isset($req_data['route'])) unset($req_data['route']);

	        ksort($req_data);

	        $signStr    = "";
			foreach ($req_data as $key => $value) {
				$signStr  .= $key . str_replace( "&quot;", "\"",$value);
			}

			$signStr  .= (!empty($signKey) ? $signKey : time().mt_rand(1000,10000));
		}

		$req_data['hash'] 			= md5($signStr);
		
		echo '<pre>';
		print_r($req_data);exit();
	}
}
