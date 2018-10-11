<?php
/**
 * google_login.php
 *
 * @copyright 2018 OpenCart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-08-02 16:46
 * @modified 2018-08-02 16:46
 */

class ModelExtensionModuleGoogleLogin extends ModelExtensionModuleSocial
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
        $token = $socialite->driver('google')->getAccessToken($code);
        $user = $socialite->driver('google')->user($token);
        return $user;
    }
}