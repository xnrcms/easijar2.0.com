<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-11 13:00:00
 * @modified         2016-11-11 13:00:00
 */

namespace sms;

class ihuyi
{
    private $URL = 'http://106.ihuyi.cn/webservice/sms.php?method=Submit';
    private $params;

    public function __construct($controller)
    {
        $this->params = array(
            'account' => $controller->config->get('module_sms_ihuyi_account'),                                             //用户账号
            'password' => $controller->config->get('module_sms_ihuyi_password'),                                 //认证密钥
            'mobile' => '',                                             //号码,多个号码用逗号隔开
            'content' => '',  //iconv('gbk','utf-8',$c),		                 //如果页面是gbk编码，则转成utf-8编码，如果是页面是utf-8编码，则不需要转码
        );
    }

    /*
    * 设置消息内容和要发送到的手机号码，手机号码以分号隔开，消息内容为utf8格式，如不是需转换成utf8格式
    */
    public function setParams($msg, $mobile)
    {
        $this->params['mobile'] = trim($mobile);
        $this->params['content'] = trim($msg);
    }

    /*
    * 发送状态说明：
    * 1 操作成功
    * 0 帐户格式不正确(正确的格式为:员工编号@企业编号)
    * -1 服务器拒绝(速度过快、限时或绑定IP不对等)如遇速度过快可延时再发
    * -2 密钥不正确
    * -3 密钥已锁定
    * -4 参数不正确(内容和号码不能为空，手机号码数过多，发送时间错误等)
    * -5 无此帐户
    * -6 帐户已锁定或已过期
    * -7 帐户未开启接口发送
    * -8 不可使用该通道组
    * -9 帐户余额不足
    * -10 内部错误
    * -11 扣费失败
    */
    public function send()
    {
        $this->log_result($this->params['content']);
        $this->log_result($this->params['mobile']);
        $re = $this->postSMS();            //POST方式提交
        $this->log_result('发送消息：'.$this->params['content']);

        $res = $this->xml_to_array($re);
        $res = $res['SubmitResult'];

        $this->log_result('发送结果：'.$res['msg']);   //发送成功返回的值
        if ($res['code'] == 2) {
            return true;
        } else {
            return $res['msg'];
        }
    }

    /*
    * 解析xml格式为数组
    */

    public function log_result($word)
    {
        $fp = fopen(DIR_LOGS.'log_sms_ihuyi.txt', 'a');
        flock($fp, LOCK_EX);
        fwrite($fp, $word.'::Date：'.strftime('%Y-%m-%d %H:%I:%S', time())."\t\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /*
     * 需已设置$params和$URL的值
     */

    public function postSMS()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->URL);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->params);
        $return_str = curl_exec($curl);
        curl_close($curl);

        return $return_str;
    }

    public function xml_to_array($xml)
    {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; ++$i) {
                $subxml = $matches[2][$i];
                $key = $matches[1][$i];
                if (preg_match($reg, $subxml)) {
                    $arr[$key] = $this->xml_to_array($subxml);
                } else {
                    $arr[$key] = $subxml;
                }
            }
        }

        return $arr;
    }
}
