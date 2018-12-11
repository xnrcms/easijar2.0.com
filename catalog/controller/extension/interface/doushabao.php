<?php
class ControllerExtensionInterfaceDoushabao extends Controller {
	private $apiUrl 	= 'http://test.api.dousha8ao.com/v1/order/';
	private $signKey 	= 'JQNOCLIQD29N4SFWH7KDT4B3LK94F8PY';
	private $source 	= 'PCEDIV';

	//投保
	public function insure()
	{
		$apiurl 						= $this->apiUrl . 'approval';
		$money 							= '10.00';
		$order_sn 						= date('YmdHis',time()) . random_string(11);
		$parame 						= [];
		$parame['source'] 				= $this->source;
		$parame['ticketNo'] 			= $order_sn;
		$parame['discountAmount'] 		= $money;

		$sign 							= strtoupper(md5(json_encode($parame).$this->signKey));

		$header 						= [];
		$header[] 						= "Content-type: application/json;charset='utf-8'";
		$header[] 						= "Accept: application/json";
		$header[] 						= "sign: ".$sign;

		$data 							= curl_http($apiurl,$parame,'JSON',$header);
		return $data;
	}


	//核保
	public function underwriting()
	{
		$apiurl 						= $this->apiUrl . 'underwriting';
		$orderProductList 				= [
			['productId'=>1,'productNum'=>2], 
			['productId'=>2,'productNum'=>2], 
		]; 	

		$parame 						= [];
		$parame['source'] 				= $this->source;//商户标识
		$parame['name'] 				= 'wangyuaniqng';//购买人姓名
		$parame['phoneNo'] 				= '18757156043';//手机号
		$parame['idCard'] 				= '411527198405240514';//身份证
		$parame['goodsValue'] 			= '10.00';//货值（单位：元）
		$parame['expressNo'] 			= '18062017115037702';//物流单号
		$parame['expressTime'] 			= '2018-12-11 15:15:15';//发货时间格式：(yyyy-MMddHH:mm:ss)
		$parame['expressLine'] 			= '德国-香港自提线路';//邮包线路
		$parame['shoppingSite'] 		= '购物网站';//购物网站
		$parame['loadingPort'] 			= '杭州';//起运地
		$parame['receiverAddress'] 		= '收货地址';//收货地址
		$parame['premium'] 				= 0;//保费（单位：元）
		$parame['shoppingTime'] 		= '2018-12-11 15:15:15';//购买日期
		$parame['destinationPort'] 		= '河南';//目的口岸
		$parame['expressChannel'] 		= '申通快递';//运输方式
		$parame['goodsValueDetail'] 	= '衬衫';//商品价值明细
		$parame['goodsKind'] 			= '其他';//商品种类
		$parame['receiverAddr'] 		= '河南省信阳市';//收件人地址
		$parame['senderAddress'] 		= '浙江省杭州市';//寄件人地址
		$parame['sellerAccount'] 		= '18757156043';//卖家账号或登录名
		$parame['buyerAccount'] 		= '18757156043';//买家账号或登录名
		$parame['sex'] 					= '男';//性别（男/女）
		$parame['goodsCategory'] 		= '衬衫';//商品列表
		$parame['goodsAmount'] 			= '2';//货物数量
		$parame['expressCompanyName'] 	= 'HK-ZT';//物流公司名称
		$parame['purchasOrderNo'] 		= '201812123121312114';//购物网站订单号/海淘商品订单号
		$parame['orderProductList'] 	= $orderProductList;//包含投保产品id和份数

		$sign 							= strtoupper(md5(json_encode($parame).$this->signKey));

		$header 						= [];
		$header[] 						= "Content-type: application/json;charset='utf-8'";
		$header[] 						= "Accept: application/json";
		$header[] 						= "sign: ".$sign;

		$data 							= curl_http($apiurl,$parame,'JSON',$header);
		return $data;
	}
}