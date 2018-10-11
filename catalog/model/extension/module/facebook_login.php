<?php
/**
 * facebook_login.php
 *
 * @copyright 2018 OpenCart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-07-19 09:43
 * @modified 2018-07-19 09:43
 */

class ModelExtensionModuleFacebookLogin extends ModelExtensionModuleSocial
{
    public function getTokens($code)
    {
        return $this->getUser($code);
    }

    public function getSocialData($response)
    {
        return array(
            'user' => $response,
            'access_token' => $response->token['access_token'],
            'fullname' => $response->name,
            'email' => $response->email,
            'uid' => $response->id,
            'unionid' => ''
        );
    }

    protected function getUser($code)
    {
        $socialite = parent::initSocialite();
        $token = $socialite->driver('facebook')->getAccessToken($code);
        $user = $socialite->driver('facebook')->user($token);
        return $user;
    }
}