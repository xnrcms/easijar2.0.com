<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-23 11:12:00
 * @modified         2016-11-23 15:11:10
 */

include_once 'unionpay/acp_service.php';

class ControllerExtensionPaymentUnionpay extends Controller
{
    private $logger;
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->logger = new Log('payment.log');
    }

    public function index()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/unionpay');

        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $currency_code = $this->config->get('payment_unionpay_currency_code');                //人民币代号（CNY）

        $total = $order_info['total'];
        if ($currency_code == '') {
            $currency_code = 'CNY';
        }
        $amount = $this->currency->format($total, $currency_code, '', false);

        if (is_file(getcwd().'/callbacks/payment_unionpay.php')) {
            $notify_url = HTTP_SERVER.'callbacks/payment_unionpay.php';
        } else {
            //通知地址, 该方式需要配置伪静态规则，且要把文件callback/unionpay_callback.php删除
            $notify_url = HTTP_SERVER . 'payment_callback/unionpay';
        }

        $return_url = $this->url->link('checkout/success');                //成功后返回页面

        $params = array(
            //以下信息非特殊情况不需要改动
            'version' => '5.0.0',                 //版本号
            'encoding' => 'utf-8',          //编码方式
            'txnType' => '01',              //交易类型
            'txnSubType' => '01',          //交易子类
            'bizType' => '000201',          //业务类型
            'frontUrl' =>  $return_url,  //前台通知地址
            'backUrl' => $notify_url,    //后台通知地址
            'signMethod' => '01',                //签名方法
            'channelType' => '07',                //渠道类型，07-PC，08-手机
            'accessType' => '0',              //接入类型
            'currencyCode' => '156',            //交易币种，境内商户固定156
            //TODO 以下信息需要填写
            'merId' => $this->config->get('payment_unionpay_partner'),    //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'orderId' => $order_id . 'gd' . date('Ym', time()),  //商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime' => date("Ymdhis"),  //订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt' => $amount * 100,  //交易金额，单位分，此处默认取demo演示页面传递的参数
        //     'reqReserved' =>'透传信息',        //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据
            //TODO 其他特殊用法请查看 special_use_purchase.php
          );

        $sign_cert_pwd = $this->config->get('payment_unionpay_cert_pwd');
        $pfx_name = $this->config->get('payment_unionpay_pfx_name');
        com\unionpay\acp\sdk\AcpService::sign ( $params, 'catalog/controller/extension/payment/unionpay/certs/' . $pfx_name, $sign_cert_pwd );

        $data['params'] = $params;
        $data['reqUrl'] = com\unionpay\acp\sdk\SDK_FRONT_TRANS_URL;


        return $this->load->view('extension/payment/unionpay', $data);
    }

    public function callback()
    {
        $this->log('进入 public function callback()');

        if (isset ( $_POST ['signature'] )) {
            if (com\unionpay\acp\sdk\AcpService::validate ( $_POST, 'catalog/controller/extension/payment/unionpay/certs/' )) {
                $this->log('==认证合格====');

                $order_id = $_POST ['orderId']; //其他字段也可用类似方式获取
                $respCode = $_POST ['respCode']; //判断respCode=00或A6即可认为交易成功
                $this->log('==支付平台反馈参数，外部 out_trade_no===='.$order_id);

                $this->load->model('checkout/order');

                $this->log('==认证合格==1111111111111==');

                // 获取订单ID
                $order_info = $this->model_checkout_order->getOrder($order_id);

                // 存储订单至系统数据库
                if ($order_info) {
                    $this->log('==认证合格==3333333333333==');
                    $order_status_id = $order_info['order_status_id'];
                    $unionpay_trade_finished = $this->config->get('payment_unionpay_trade_finished');

                    // 避免处理已完成的订单,判断订单状态是否已经处理过。
                    $this->log('order_id='.$order_id.' order_status_id='.$order_status_id);

                    if ($order_status_id != $unionpay_trade_finished) {
                        $this->log('No finished.');

                        if (($respCode == '00' || $respCode == 'A6')) {    //交易成功
                            $this->log('==认证合格==88888888888==');
                            $this->model_checkout_order->addOrderHistory($order_id, $unionpay_trade_finished);
                            echo 'success';        //请不要修改或删除
                            $this->log('success - unionpay_trade_finished');
                        } else {
                            $this->log('==认证合格==000000==');
                            echo 'fail';
                            $this->log('verify_failed');
                        }
                    }
                }
            }
        } else {
            echo 'fail';
        }
    }

	private function log($data) {
        if (1) {
	        $this->logger->write($data);
        }
    }
}
