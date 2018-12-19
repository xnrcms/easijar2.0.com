<?php

include 'vendor/rongcloud/rongcloud.php';

class ControllerExtensionInterfaceRongcloud extends Controller {
	private $appKey 	= '8luwapkv8blzl';
	private $appSecret 	= 'cm3H9MayG34';

	//注册融云用户
	public function reguser($userData)
	{
		$RongCloud 			= new RongCloud($this->appKey,$this->appSecret);
		$result 			= $RongCloud->user()->getToken($userData['rongcloud_uid'], $userData['rongcloud_nickname'], $userData['rongcloud_avatar']);

		$data 				= !empty($result) ? json_decode($result,true) : [];
		if (isset($data['code']) && $data['code'] == '200') {
			return $data;
		}

		$logger 		= new Log('rongcloud.log');
		$logger->write('rongcloud:'.$result);

		return [];
	}
}