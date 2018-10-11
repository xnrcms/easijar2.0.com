<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-11 13:00:00
 * @modified         2016-11-11 13:00:00
 */

class Sms
{
    private $plant;
    private $params;
    private $config;
    private $load;
    private $language;
    private $log;

    public function __construct()
    {
        $this->config = registry()->get('config');
        $this->load = registry()->get('load');
        $this->language = registry()->get('language');
        $this->log = new \Log('sms.log');
        if (!$this->config->get('module_sms_status')) {
            return;
        }

        if ($this->config->get('module_sms_plant') == 'yunpian') {
            $this->plant = \Yunpian\Sdk\YunpianClient::create($this->config->get('module_sms_yunpian_apikey'));
        } else {
            $class = 'Sms\\'.$this->config->get('module_sms_plant');
            if (class_exists($class)) {
                $this->plant = new $class($this);
            } else {
                exit('Error: Could not load sms library '.$class.'!');
            }
        }
    }

    /*
    * 设置消息内容和要发送到的手机号码，手机号码以分号隔开，消息内容为utf8格式，如不是需转换成utf8格式
    */
    public function setParams($msg, $mobile)
    {
        if (is_ft()) {
            $mobile = '+' . preg_replace("/\D/","", $mobile);
        }
        if (!$this->plant) {
            return $this;
        }
        $this->log->write('set params');
        if ($this->config->get('module_sms_plant') == 'yunpian' || $this->config->get('module_sms_plant') == 'c123') {
          $this->load->language('notify/sms');
          $msg = $this->language->get('text_pre_sign') . html_entity_decode($this->config->get('module_sms_sign')[(int)$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8') . $this->language->get('text_post_sign') . $msg;
        }
        $this->log->write('message: ' . $msg);
        if ($this->config->get('module_sms_plant') == 'yunpian') {
            $this->params = array(
                'text' => $msg,
                'mobile' => $mobile,
            );
        } else {
            $this->plant->setParams($msg, $mobile);
        }
        return $this;
    }

    /*
    * 发送短信
    */
    public function send()
    {
        if (!$this->plant) {
            return true;
        }
        $this->log->write('send message');
        if ($this->config->get('module_sms_plant') == 'yunpian') {
            $param = [\Yunpian\Sdk\YunpianClient::MOBILE => $this->params['mobile'], \Yunpian\Sdk\YunpianClient::TEXT => $this->params['text']];
            $r = $this->plant->sms()->single_send($param);
            if($r->isSucc()){
                $ret = true;
            } else {
                $ret = $r->code() . ': ' . $r->msg();
            }
        } else {
            $ret = $this->plant->send();
        }
        $this->log->write($ret);

        return $ret;
    }
}
