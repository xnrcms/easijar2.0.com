<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-11 13:00:00
 * @modified         2016-11-11 13:00:00
 */

namespace sms;

class c123
{
    private $URL = 'http://smsapi.c123.cn/OpenPlatform/OpenApi';
    private $params;

    public function __construct($controller)
    {
        $this->params = array(
            'action' => 'sendOnce',                                //发送类型 ，可以有sendOnce短信发送，sendBatch一对一发送，sendParam	动态参数短信接口
            'ac' => $controller->config->get('module_sms_c123_ac'),                                             //用户账号
            'authkey' => $controller->config->get('module_sms_c123_authkey'),                                 //认证密钥
            'cgid' => $controller->config->get('module_smsc123_cgid'),    //通道组编号
            'm' => '',                                             //号码,多个号码用逗号隔开
            'c' => '',  //iconv('gbk','utf-8',$c),		                 //如果页面是gbk编码，则转成utf-8编码，如果是页面是utf-8编码，则不需要转码
            'csid' => trim($controller->config->get('module_sms_c123_csid')),     //签名编号 ，可以为空，为空时使用系统默认的签名编号
            't' => '', //$t                                              //定时发送，为空时表示立即发送
        );
    }

    /*
    * 设置消息内容和要发送到的手机号码，手机号码以分号隔开，消息内容为utf8格式，如不是需转换成utf8格式
    */
    public function setParams($msg, $mobile)
    {
        $this->params['m'] = str_replace(';', ',', $mobile);
        /*
        if(false !== strpos($mobile,";")) {
            $this->params["action"] = 'sendBatch';
        }
        */
        $this->params['c'] = $msg;
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
        $this->log_result(iconv('utf-8', 'gbk//IGNORE', var_export($this->params['c'], true)));
        $this->log_result($this->params['m']);
        $this->log_result($this->params['action']);
        $re = $this->postSMS();            //POST方式提交
        $this->log_result('发送消息：'.iconv('utf-8', 'gbk//IGNORE', var_export($this->params, true)));
        preg_match_all('/result="(.*?)"/', $re, $res);
        if (trim($res[1][0]) == '1') {  //发送成功 ，返回企业编号，员工编号，发送编号，短信条数，单价，余额

          preg_match_all('/\<Item\s+(.*?)\s+\/\>/', $re, $item);
            for ($i = 0; $i < count($item[1]); ++$i) {
                preg_match_all('/cid="(.*?)"/', $item[1][$i], $cid);
                preg_match_all('/sid="(.*?)"/', $item[1][$i], $sid);
                preg_match_all('/msgid="(.*?)"/', $item[1][$i], $msgid);
                preg_match_all('/total="(.*?)"/', $item[1][$i], $total);
                preg_match_all('/price="(.*?)"/', $item[1][$i], $price);
                preg_match_all('/remain="(.*?)"/', $item[1][$i], $remain);

                $send['cid'] = $cid[1][0];             //企业编号
              $send['sid'] = $sid[1][0];             //员工编号
                $send['msgid'] = $msgid[1][0];         //发送编号
                $send['total'] = $total[1][0];         //计费条数
                $send['price'] = $price[1][0];         //短信单价
                $send['remain'] = $remain[1][0];       //余额
                $send_arr[] = $send;                   //数组send_arr 存储了发送返回后的相关信息
            }
            $this->log_result('发送成功,状态为'.$res[1][0]);   //发送成功返回的值
            return true;
        } else {  //发送失败的返回值
          switch (trim($res[1][0])) {
                case  0: $ret = '帐户格式不正确(正确的格式为:员工编号@企业编号)'; break;
                case  -1: $ret = '服务器拒绝(速度过快、限时或绑定IP不对等)如遇速度过快可延时再发'; break;
                case  -2: $ret = ' 密钥不正确'; break;
                case  -3: $ret = '密钥已锁定'; break;
                case  -4: $ret = '参数不正确(内容和号码不能为空，手机号码数过多，发送时间错误等)'; break;
                case  -5: $ret = '无此帐户'; break;
                case  -6: $ret = '帐户已锁定或已过期'; break;
                case  -7: $ret = '帐户未开启接口发送'; break;
                case  -8: $ret = '不可使用该通道组'; break;
                case  -9: $ret = '帐户余额不足'; break;
                case  -10: $ret = '内部错误'; break;
                case  -11: $ret = '扣费失败'; break;
                default:$ret = '未知错误'; break;
            }
            $this->log_result('发送失败：'.$ret);   //发送成功返回的值
            return $ret;
        }
    }

    /*
     * 需已设置$params和$URL的值
     */

    public function log_result($word)
    {
        $fp = fopen(DIR_LOGS.'sms_c123.txt', 'a');
        flock($fp, LOCK_EX);
        fwrite($fp, $word.'::Date：'.strftime('%Y-%m-%d %H:%I:%S', time())."\t\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public function postSMS()
    {
        $row = parse_url($this->URL);
        $host = $row['host'];
        $port = isset($row['port']) && $row['port'] ? $row['port'] : 80;
        $file = $row['path'];
        $post = '';
        while (list($k, $v) = each($this->params)) {
            $post .= rawurlencode($k).'='.rawurlencode($v).'&';    //转URL标准码
        }
        $post = substr($post, 0, -1);
        $len = strlen($post);
        $fp = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$fp) {
            return "$errstr ($errno)\n";
        } else {
            $receive = '';
            $out = "POST $file HTTP/1.0\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Content-type: application/x-www-form-urlencoded\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Content-Length: $len\r\n\r\n";
            $out .= $post;
            fwrite($fp, $out);
            while (!feof($fp)) {
                $receive .= fgets($fp, 128);
            }
            fclose($fp);
            $receive = explode("\r\n\r\n", $receive);
            unset($receive[0]);

            return implode('', $receive);
        }
    }
}
