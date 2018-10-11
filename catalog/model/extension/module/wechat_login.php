<?php

/**
 * ModelModuleWeiboLogin
 *
 * @copyright  2016 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2016-11-18 16:00
 * @modified   2016-11-18 16:00
 */
class ModelExtensionModuleWechatLogin extends ModelExtensionModuleSocial
{
    const GET_USER_INFO_URL = 'https://api.weixin.qq.com/sns/userinfo';
    const GET_ACCESS_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/access_token";

    public function handleWeChat($unionId, $uid, $accessToken)
    {
        if ($unionId) {
            $customer = $this->getCustomerByUnionId($unionId, 'wechat');
            if ($customer) {
                return $customer;
            }
            $customer = $this->getCustomerByUnionId($unionId, 'weixin_gz');
            if ($customer) {
                $authData = array(
                    'customer_id' => $customer['customer_id'],
                    'uid' => $uid,
                    'unionid' => $unionId,
                    'provider' => 'wechat',
                    'access_token' => $accessToken,
                    'date_added' => date('Y-m-d H:i:s'),
                    'date_modified' => date('Y-m-d H:i:s')
                );
                $this->createAuthentication($authData);
                return $customer;
            }
        } elseif ($uid) {
            $customer = $this->getCustomerByUid($uid, 'wechat');
            return $customer;
        }
        return array();
    }

    public function getUserInfo($access_token, $openid)
    {
        $keysArr = array(
            "access_token" => $access_token,
            "openid" => $openid
        );

        $token_url = $this->combineURL(self::GET_USER_INFO_URL, $keysArr);
        $response = $this->get_contents($token_url);

        return json_decode($response);
    }

    private function combineURL($baseURL, $keysArr)
    {
        $combined = $baseURL . "?";
        $valueArr = array();

        foreach ($keysArr as $key => $val) {
            $valueArr[] = "$key=$val";
        }

        $keyStr = implode("&", $valueArr);
        $combined .= ($keyStr);

        return $combined;
    }

    /**
     * get_contents
     * 服务器通过get请求获得内容
     *
     * @param string $url 请求的url,拼接后的
     * @return string           请求返回的内容
     */
    private function get_contents($url)
    {
        if (ini_get("allow_url_fopen") == "1") {
            $response = file_get_contents($url);
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            curl_close($ch);
        }
        //-------请求为空
        if (empty($response)) {
            $this->error->showError("50001");
        }

        return $response;
    }

    public function getTokens($code)
    {
        $social = $this->getSocialByProvider('wechat');
        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "appid" => $social['key'],
            "secret" => $social['secret'],
            "code" => $code
        );

        //------构造请求access_token的url
        $token_url = $this->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->get_contents($token_url);

        $response = json_decode($response);

        if (isset($response->error)) {
            $this->log('Response msg: ' . $response->error);
            echo($response->error . ';  ');
            return false;
        }

        return $response;
    }

    public function getSocialData($response)
    {
        $access_token = $response->access_token;
        $uid = $response->openid;
        $unionid = $response->unionid;
        $user = $this->getUserInfo($access_token, $uid);

        return array(
            'user' => $user,
            'access_token' => $access_token,
            'uid' => $uid,
            'union_id' => $unionid
        );
    }

    //简单实现json到php数组转换功能

    private function simple_json_parser($json)
    {
        $json = str_replace("{", "", str_replace("}", "", $json));
        $jsonValue = explode(",", $json);
        $arr = array();
        foreach ($jsonValue as $v) {
            $jValue = explode(":", $v);
            $arr[str_replace('"', "", $jValue[0])] = (str_replace('"', "", $jValue[1]));
        }
        return $arr;
    }
}
