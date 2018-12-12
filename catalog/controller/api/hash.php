<?php
class ControllerApiHash extends Controller
{
	public function index() 
	{
		$req_data 			= array_merge($this->request->get,$this->request->post);
		$hash 				= '';

		if (!empty($req_data)) {
			$signKey 		= $this->config->get('data_sign_key');

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
