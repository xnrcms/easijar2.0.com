<?php
/**
 * @package        OpenCart
 * @author        Daniel Kerr
 * @copyright    Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license        https://opensource.org/licenses/GPL-3.0
 * @link        https://www.opencart.com
 */

/**
 * Controller class
 *
 * @property Document document
 * @property Loader load
 * @property Request request
 * @property Language language
 * @property Session session
 * @property Response response
 * @property Url url
 * @property Url front_url
 * @property Config config
 */
abstract class Controller {
	protected $registry;
	protected $apiLogs;

	public function __construct($registry) {
		$this->registry = $registry;
		$this->apiLogs 	= false;
	}

	public function __get($key) {
		return $this->registry->get($key);
	}

	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}

	public function jsonOutput($json = null) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function redirectToHome()
	{
		$this->response->redirect($this->url->link('common/home'));
	}

	public function dataFilter($allowKey = [],$defKey = [])
	{	
		$defKey 			= !empty($defKey) ? $defKey : ['time','apiId','terminal','hash'];
		$allowKey 			= array_merge($allowKey,$defKey);
		$fileter_data 		= [];
		$req_data 			= array_merge($this->request->get,$this->request->post);
		if (!empty($req_data) && !empty($allowKey))
        {
        	foreach ($allowKey as $key => $value) {
    			$fileter_data[$value] 	= isset($req_data[$value]) ? str_replace( "&quot;", "\"",$req_data[$value]) : '';
    		}
        }

        return $fileter_data;
	}

    public function checkSign($data = [])
    {
        if (!empty($data))
        {
        	//签名秘钥
        	$signKey 		= $this->config->get('data_sign_key');
        	$hash 			= isset($data['hash']) ? $data['hash'] : '';

	        //hash字段不参与加密
	        if(isset($data['hash'])) unset($data['hash']);

	        //按字母排序
	        ksort($data);
	        
	        //签名串 key value拼接
	        $signStr    = "";
	        foreach ($data as $key => $value) {
	            $signStr  .= $key . str_replace( "&quot;", "\"",$value);
	        }

	        $signStr  .= (!empty($signKey) ? $signKey : time().mt_rand(1000,10000));
	        
	        wr([
	        	'apiUrl'=>current_route(),
	        	'hash'=>$hash,
	        	'signStr'=>$signStr,
	        	'hash2'=>md5($signStr),
	        	'signKey'=>$signKey,
	        	'data'=>$data
	        ],'sign.txt',false);

	        return (!empty($hash) && md5($signStr) === $hash) ? true : false;
        }

        return false;
    }

    public function returnData($data = []){
    	$data 	= array_merge(['code'=>'202','msg'=>'fail:system error','time'=>date('Y-m-d H:i:s',time()),'data'=>''],$data);
    	
    	if ($this->apiLogs) {
	    	wr("\n\n");
	    	wr(['api_url'=>current_route(),'returndata'=>$data],'returndata.txt');
	    	wr("\n\n");
    	}
    	
    	return json_encode($data);
    }

    public function set_apilog()
    {
    	$this->apiLogs 	= true;
    }
}
