<?php
/**
 * sendcloudapi.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-01-04 17:12
 * @modified   2018-01-04 17:12
 */

class SendCloudApi
{
    const BASE_URL = 'http://api.sendcloud.net/apiv2/';
    private $apiUser = '';
    private $apiKey = '';
    private $from = 'support@opencart.cn';
    private $fromName = 'OpenCart.Cn';

    public function __construct($apiUser = '', $apiKey = '')
    {
        if ($apiUser) {
            $this->apiUser = $apiUser;
        } else {
            $this->apiUser = config('config_sendcloud_user');
        }

        if ($apiKey) {
            $this->apiKey = $apiKey;
        } else {
            $this->apiKey = config('config_sendcloud_key');
        }
    }

    public function send($data)
    {
        if (is_array($data['to'])) {
            $to = implode(';', $data['to']);
        } else {
            $to = $data['to'];
        }
        $useAddressList = array_get($data, 'useAddressList') ? 'true' : 'false';
        $param = array(
            'apiUser' => $this->apiUser,
            'apiKey' => $this->apiKey,
            'from' => array_get($data, 'from') ?: $this->from,
            'fromName' => array_get($data, 'sender') ?: $this->fromName,
            'replyTo' => array_get($data, 'reply_to', ''),
            'to' => $to,
            'subject' => array_get($data, 'subject'),
            'html' => array_get($data, 'html'),
            'plain' => array_get($data, 'plain'),
            'respEmailId' => 'true',
            'useAddressList' => $useAddressList
        );

        $data = http_build_query($param);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $data
            ));
        $context = stream_context_create($options);
        $url = self::BASE_URL . 'mail/send';
        $result = file_get_contents($url, FILE_TEXT, $context);
        return $result;
    }

    public function getUserInfo()
    {
        $url = self::BASE_URL . 'userinfo/get';
        $param = array(
            'apiUser' => $this->apiUser,
            'apiKey' => $this->apiKey,
        );
        $data = http_build_query($param);
        $result = file_get_contents($url . '?' . $data);
        return json_decode($result, true);
    }
}