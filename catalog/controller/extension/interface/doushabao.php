<?php
class ControllerExtensionInterfaceDoushabao extends Controller {
	private $apiUrl 	= 'http://test.api.dousha8ao.com/v1/order/';
	private $signKey 	= 'VG3MJQLY8TUAE2EO2M5U4FUQTT9OILLK';

	//投保
	public function insure()
	{
		$apiurl 			= $this->apiUrl . 'wishApproval';

		$parame 						= [];
		$parame['source'] 				= '';
		$parame['ticketNo'] 			= '';
		$parame['discountAmount'] 		= '';

		$sign 							= md5(json_encode($parame)) . $this->signKey;
		$parame['sign'] 				= $sign;

		$data 							= curl_http($apiurl,$parame,'POST');
		return $data;
	}


	//核保
	public function underwriting()
	{
		$apiurl 		= $this->apiUrl . 'wishUnderwriting';

		$parame 						= [];
		$parame['source'] 				= '';//商户标识
		$parame['name'] 				= '';//购买人姓名
		$parame['phoneNo'] 				= '';//手机号
		$parame['idCard'] 				= '';//身份证
		$parame['goodsValue'] 			= '';//货值（单位：元）
		$parame['expressNo'] 			= '';//物流单号
		$parame['expressTime'] 			= '';//发货时间格式：(yyyy-MMddHH:mm:ss)
		$parame['expressLine'] 			= '';//邮包线路
		$parame['shoppingSite'] 		= '';//购物网站
		$parame['purchasOrderNo'] 		= '';//购物网站订单号/海淘商品订单号
		$parame['receiverAddress'] 		= '';//收货地址
		$parame['goodsCategory'] 		= '';//商品列表
		$parame['premium'] 				= '';//保费（单位：元）
		$parame['shoppingTime'] 		= '';//购买日期
		$parame['loadingPort'] 			= '';//起运地
		$parame['destinationPort'] 		= '';//目的口岸
		$parame['expressChannel'] 		= '';//运输方式
		$parame['goodsValueDetail'] 	= '';//商品价值明细
		$parame['goodsKind'] 			= '';//商品种类
		$parame['goodsAmount'] 			= '';//货物数量
		$parame['expressCompanyName'] 	= '';//物流公司名称
		$parame['receiverInfo'] 		= '';//收件人信息
		$parame['senderInfo'] 			= '';//寄件人信息
		$parame['receiverAddr'] 		= '';//收件人地址
		$parame['senderAddress'] 		= '';//寄件人地址
		$parame['sellerAccount'] 		= '';//卖家账号或登录名
		$parame['buyerAccount'] 		= '';//买家账号或登录名
		$parame['sex'] 					= '';//性别（男/女）
		$parame['orderProductList'] 	= '';//包含投保产品id和份数

		$sign 							= md5(json_encode($parame)) . $this->signKey;
		$parame['sign'] 				= $sign;

		$data 							= curl_http($apiurl,$parame,'POST');
		return $data;
	}
}