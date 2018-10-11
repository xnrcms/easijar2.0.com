<?php

/**
 * ModelModuleWeiboLogin
 *
 * @copyright  2016 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2016-11-18 15:55
 * @modified   2016-11-18 15:55
 */
class ModelExtensionModuleWeiboLogin extends ModelExtensionModuleSocial
{
    const GET_USER_INFO_URL = 'https://api.weibo.com/2/users/show.json';
    const GET_ACCESS_TOKEN_URL = "https://api.weibo.com/oauth2/access_token";

    public function getUserInfo($access_token, $uid)
    {
        $keysArr = array(
            "access_token" => $access_token,
            "uid" => $uid
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
        if (0) {//(ini_get("allow_url_fopen") == "1") {
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
        $social = $this->getSocialByProvider('weibo');
        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $social['key'],
            "redirect_uri" => urlencode($social['callback']),
            "client_secret" => $social['secret'],
            "code" => $code
        );

        //------构造请求access_token的url
        $token_url = $this->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->post($token_url, $keysArr);
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
        $uid = $response->uid;
        $unionid = '';
        $user = $this->getUserInfo($access_token, $uid);

        return array(
            'user' => $user,
            'access_token' => $access_token,
            'uid' => $uid,
            'union_id' => $unionid
        );
    }

    //简单实现json到php数组转换功能

    /**
     * post
     * post方式请求资源
     *
     * @param string $url 基于的baseUrl
     * @param array  $keysArr 请求的参数列表
     * @param int    $flag 标志位
     * @return string           返回的资源内容
     */
    private function post($url, $keysArr, $flag = 0)
    {

        $ch = curl_init();
        if (!$flag) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $keysArr);
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);

        curl_close($ch);
        return $ret;
    }

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
