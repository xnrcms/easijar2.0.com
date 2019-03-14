<?php

/**
 * ControllerModuleSocial Controller
 *
 * @copyright  2016 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2016-11-15 15:01
 * @modified   2016-11-15 15:01
 */

use Abraham\TwitterOAuth\TwitterOAuth;

class ControllerExtensionModuleSocial extends Controller
{
    private $oldSocialProviders = ['qq', 'weibo', 'wechat'];
    private $socialProviders = ['qq', 'weibo', 'wechat', 'facebook', 'twitter', 'google'];
    private $socialite;
    private $error = array();
    private $provider = '';
    private $loginKey = '';
    private $logger;

    public function getLoginKey()
    {
        if (empty($this->provider)) {
            throw new Exception('Empty social provider');
        } elseif (empty($this->loginKey)) {
            throw new Exception('Empty login key');
        }
        return $this->loginKey;
    }

    public function redirect()
    {
        $provider = strtolower(array_get($this->request->get, 'provider'));
        return model('extension/module/social')->redirectAuthUrl($provider);
    }

    public function api_login()
    {
        $provider = strtolower(array_get($this->request->get, 'provider'));

        $this->setProvider($provider);
        $this->load->model('extension/module/social');
        $this->load->model('account/customer');
        $this->load->model('account/customer_group');
        $this->load->language('account/login');

        if ($this->customer->isLogged()) {
            $this->session->data['associate'] = true;
        }

        if ($this->provider == 'twitter') {
            $social     = model('extension/module/social')->getSocialByProvider($this->provider);
            $connection = new TwitterOAuth($social['client_id'], $social['client_secret'], $this->session->data['twitter_oauth_token'], $this->session->data['twitter_oauth_token_secret']);
            $response   = $connection->oauth(
                'oauth/access_token', [
                    'oauth_verifier' => $this->request->get['oauth_verifier']
                ]
            );
        } else {
            if (!isset($this->request->get['code'])) {
                return 'empty_code';
            }
            
            $response = $this->getModel()->getTokens($this->request->get['code']);
            if (!$response) {
                return 'invalid_code';
            }
        }

        $socialData = $this->getSocialData($response);
        $uid        = array_get($socialData, 'uid');
        $unionId    = array_get($socialData, 'union_id');

        if (!$uid && !$unionId) {
            return 'invalid_access_token';
        }
        if ($this->getProvider() != 'twitter' && !is_object(array_get($socialData, 'user'))) {
            return 'invalid_code';
        }

        // User exist in database
        $customerInfo = $this->getCustomerFromAuth($socialData);
        if ($customerInfo) {
            $customerId = $customerInfo['customer_id'];
            $this->saveRemoteAvatar($customerId, $socialData);
            if ($this->validate($customerId)) {
                $authData = $this->getAuthData($socialData);

                unset($this->session->data['guest']);

                // Add to activity log
                $this->load->model('account/activity');

                $activity_data = array(
                    'customer_id' => $this->customer->getId(),
                    'name' => $this->customer->getFullName()
                );
                $this->model_account_activity->addActivity('login', $activity_data);
                $this->getModel()->updateAuthentication($authData);

                if (isset($this->session->data["{$this->getLoginKey()}"]['seamless'])) {
                    unset($this->session->data["{$this->getLoginKey()}"]['seamless']);
                }

                return 'login success';
            } else {
                return 'login fail';
            }
        }

        if ($this->customer->isLogged()) {
            $customerId = $this->customer->getId();
            $this->createAuth($customerId, $socialData);
        } else {
            $customerId = $this->createCustomer($socialData);
        }

        $this->saveRemoteAvatar($customerId, $socialData);

        $customerInfo = $this->getCustomerFromAuth($socialData);

        if ($customerInfo && $this->validate($customerInfo['customer_id'])) {
            $authData = $this->getAuthData($socialData);
            $this->completeLogin($customerId, $customerInfo['email'], $authData);
        } else {
            return 'login fail';
        }
    }

    public function login()
    {
        $provider = strtolower(array_get($this->request->get, 'provider'));

        $this->setProvider($provider);
        $this->load->model('extension/module/social');
        $this->load->model('account/customer');
        $this->load->model('account/customer_group');
        $this->load->language('account/login');

        if ($this->customer->isLogged()) {
            $this->session->data['associate'] = true;
            //return $this->jsRedirect('logged');
        }

        if ($this->provider == 'twitter') {
            $social = model('extension/module/social')->getSocialByProvider($this->provider);
            $connection = new TwitterOAuth($social['client_id'], $social['client_secret'], $this->session->data['twitter_oauth_token'], $this->session->data['twitter_oauth_token_secret']);
            $response = $connection->oauth(
                'oauth/access_token', [
                    'oauth_verifier' => $this->request->get['oauth_verifier']
                ]
            );
        } else {
            if (!isset($this->request->get['code'])) {
                if (isset($this->request->get['error']) && isset($this->request->get['error_description'])) {
                    $this->logInfo('No code returned. Error: ' . $this->request->get['error'] . ', Error Description: ' . $this->request->get['error_description']);
                }
                return $this->jsRedirect('empty_code');
            }

            $this->logInfo('Code:', $this->request->get['code']);
            $response = $this->getModel()->getTokens($this->request->get['code']);

            $this->logInfo('Token:', $response);
            if (!$response) {
                $this->logInfo('Invalid code.');
                return $this->jsRedirect('invalid_code');
            }
        }

        $socialData = $this->getSocialData($response);
        $uid = array_get($socialData, 'uid');
        $unionId = array_get($socialData, 'union_id');
        if (!$uid && !$unionId) {
            return $this->jsRedirect('invalid_access_token');
        }
        if ($this->getProvider() != 'twitter' && !is_object(array_get($socialData, 'user'))) {
            $this->logInfo('Invalid code.');
            return $this->jsRedirect('invalid_code');
        }

        // User exist in database
        $customerInfo = $this->getCustomerFromAuth($socialData);
        $this->logInfo('CustomerInfo:', $customerInfo);
        if ($customerInfo) {
            $customerId = $customerInfo['customer_id'];
            $this->saveRemoteAvatar($customerId, $socialData);
            if ($this->validate($customerId)) {
                $authData = $this->getAuthData($socialData);
                $this->logInfo('AuthData:', $authData);
                $this->completeLogin($customerInfo['customer_id'], $customerInfo['email'], $authData);
                return true;
            } else {
                $this->logInfo('Could not login to - ID: ' . $customerInfo['customer_id'] . ', Email: ' . $customerInfo['email']);
                return $this->jsRedirect();
            }
        }

        // User not exist in database, then create it.
        $this->logInfo('User not exist');
        if ($this->customer->isLogged()) {
            $customerId = $this->customer->getId();
            $this->createAuth($customerId, $socialData);
        } else {
            $customerId = $this->createCustomer($socialData);
        }
        $this->saveRemoteAvatar($customerId, $socialData);
        $this->logInfo('Customer ID date_added: ' . $customerId);
        $customerInfo = $this->getCustomerFromAuth($socialData);
        if ($customerInfo && $this->validate($customerInfo['customer_id'])) {
            $authData = $this->getAuthData($socialData);
            $this->completeLogin($customerId, $customerInfo['email'], $authData);
        } else {
            $user = array_get($socialData, 'user');
            $this->logInfo('Could not login to - ID: ' . $customerId);
            $this->logInfo($user);
            return $this->jsRedirect();
        }
    }

    private function logInfo(...$messages)
    {
        if (!$this->config->get('module_omni_auth_debug')) {
            return false;
        }
        foreach ($messages as $message) {
            $this->logger->write($message);
        }
    }

    private function jsRedirect($message = '')
    {
        if ($isAssociating = array_get($this->session->data, 'associate')) {
            $url = $this->url->link('account/edit');
            unset($this->session->data['associate']);
        } else {
            $url = $this->url->link('account/account');
        }
        echo '<!--' . $message . '-->';
        echo '<script type="text/javascript">window.opener.location = "' . $url . '"; window.close();</script>';
        return false;
    }

    public function getModel()
    {
        $modelName = "model_extension_module_{$this->getLoginKey()}";
        if (class_exists($modelName)) {
            return $this->$modelName;
        } else {
            $this->load->model("extension/module/{$this->getLoginKey()}");
            return $this->$modelName;
        }
    }

    private function getSocialData($response)
    {
        return $this->getModel()->getSocialData($response);
    }

    public function getProvider()
    {
        if (empty($this->provider)) {
            throw new Exception('Empty social provider');
        }
        return $this->provider;
    }

    public function setProvider($provider)
    {
        $provider = strtolower($provider);
        if (!in_array($provider, $this->socialProviders)) {
            throw new Exception('Invalid social provider');
        }
        $this->provider = $provider;
        $this->loginKey = "{$provider}_login";
        $this->socialite = model('extension/module/social')->initSocialite();
        $this->logger = new \Log("{$provider}.log");
    }

    private function getCustomerFromAuth($socialData)
    {
        $customerInfo = array();
        $uid = array_get($socialData, 'uid');
        if ($uid && in_array($this->provider, ['qq', 'weibo', 'facebook', 'twitter', 'google'])) {
            $customerInfo = $this->getModel()->getCustomerByUid($uid, $this->getProvider());
        }
        $unionId = array_get($socialData, 'union_id');
        $accessToken = array_get($socialData, 'access_token');
        if (!$customerInfo && $unionId && $this->provider == 'wechat') {
            $customerInfo = $this->getModel()->handleWeChat($unionId, $uid, $accessToken);
        }
        return $customerInfo;
    }

    private function saveRemoteAvatar($customerId, $socialData)
    {
        $imageUrl = $this->getAvatar(array_get($socialData, 'user'));
        $this->logInfo('saveRemoteAvatar: ', $imageUrl);
        if (empty($imageUrl)) {
            return false;
        }

        $existAvatar = DIR_IMAGE . 'avatar/' . $customerId . '.jpg';
        $this->logInfo('existAvatar: ', $existAvatar);
        if (file_exists($existAvatar)) {
            return false;
        }
        $this->load->model('tool/image');
        $this->model_tool_image->getImage($imageUrl, DIR_IMAGE . 'avatar/', $customerId . '.jpg');
    }

    private function getAvatar($user)
    {
        if ($this->provider == 'qq') {
            return $user->figureurl_qq_2;
        } elseif ($this->provider == 'wechat') {
            return $user->headimgurl;
        } elseif ($this->provider == 'weibo') {
            return $user->avatar_hd;
        } elseif ($this->provider == 'google' || $this->provider == 'facebook') {
            return $user->avatar;
        }
    }

    protected function validate($customer_id)
    {
        // Check how many login attempts have been made.
        $login_info = $this->model_account_customer->getLoginAttempts($customer_id);

        if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
            $this->error['warning'] = $this->language->get('error_attempts');
        }

        // Check if customer has been approved.
        $customerInfo = $this->model_account_customer->getCustomer($customer_id);

        $this->logInfo("Validation Customer:", $customerInfo);
        if ($customerInfo && !$customerInfo['status']) {
            $this->error['warning'] = $this->language->get('error_approved');
        }

        if (!$this->error) {
            if (!$this->customer->login($customer_id, '', true)) {
                $this->error['warning'] = $this->language->get('error_login');

                $this->model_account_customer->addLoginAttempt($customer_id);
            } else {
                $this->model_account_customer->deleteLoginAttempts($customer_id);
            }
        }
        $this->logInfo("Validation Error:", $this->error);
        if (array_get($this->error, 'warning')) {
            $this->session->data['error'] = $this->error['warning'];
        }
        return !$this->error;
    }

    private function getAuthData($socialData)
    {
        return array(
            'uid' => array_get($socialData, 'uid'),
            'unionid' => array_get($socialData, 'union_id'),
            'access_token' => array_get($socialData, 'access_token'),
            'token_secret' => array_key_exists('token_secret', $socialData) ? array_get($socialData, 'token_secret') : '',
            'provider' => $this->provider,
            'date_modified' => date('Y-m-d H:i:s')
        );
    }

    protected function completeLogin($customer_id, $email, $authData)
    {
        unset($this->session->data['guest']);

        // Add to activity log
        $this->load->model('account/activity');

        $activity_data = array(
            'customer_id' => $this->customer->getId(),
            'name' => $this->customer->getFullName()
        );
        $this->model_account_activity->addActivity('login', $activity_data);
        $this->getModel()->updateAuthentication($authData);

        if (isset($this->session->data["{$this->getLoginKey()}"]['seamless'])) {
            unset($this->session->data["{$this->getLoginKey()}"]['seamless']);
        }

        $this->logInfo('Customer logged in - ID: ' . $customer_id . ', Email: ' . $email);
        $this->jsRedirect();
    }

    private function createAuth($customerId, $socialData)
    {
        $authData = array(
            'customer_id' => $customerId,
            'uid' => array_get($socialData, 'uid'),
            'unionid' => array_get($socialData, 'union_id'),
            'provider' => $this->provider,
            'access_token' => array_get($socialData, 'access_token'),
            'token_secret' => array_key_exists('token_secret', $socialData) ? array_get($socialData, 'token_secret') : '',
            'avatar' => $this->getAvatar(array_get($socialData, 'user')),
            'date_added' => date('Y-m-d H:i:s'),
            'date_modified' => date('Y-m-d H:i:s')
        );
        return $this->getModel()->createAuthentication($authData);
    }

    private function createCustomer($socialData)
    {
        $user = $socialData['user'];
        $customer_group_id = $this->config->get('config_customer_group_id');
        $data = array(
            'customer_group_id' => (int)$customer_group_id,
            'fullname' => array_key_exists('fullname', $socialData) && $socialData['fullname'] ? $socialData['fullname']  : $this->getUserName($user),
            'email' => array_key_exists('email', $socialData) && $socialData['email'] ? $socialData['email'] : '',
            'telephone' => '',
            'fax' => '',
            'password' => '',
            'company' => '',
            'from' => $this->provider
        );
        $customerId = $this->model_account_customer->addCustomer($data);
        if (!array_key_exists('fullname', $socialData)) {
            $this->updateFirstName($customerId, $user);
        }
        $this->createAuth($customerId, $socialData);
        return $customerId;
    }

    private function getUserName($user)
    {
        $userName = '';
        if (isset($user->nickname)) {
            $userName = $user->nickname;
        } elseif (isset($user->screen_name)) {
            $userName = $user->screen_name;
        } elseif (isset($user->name)) {
            $userName = $user->name;
        }
        $this->logInfo('getUserName: ', $userName);
        $userName = (new \Kozz\Components\Emoji\EmojiParser())->replace($userName, '');
        $this->logInfo('handled Username: ', $userName);
        return $userName;
    }

    private function updateFirstName($customerId, $user)
    {
        $firstName = $this->getUserName($user);
        if (!$firstName) {
            $firstName = $this->getProvider() . '_' . $customerId;
        }
        $updateSql = "UPDATE " . DB_PREFIX . "customer SET `fullname` = '{$firstName}' WHERE `customer_id` = '{$customerId}'";
        return $this->db->query($updateSql);
    }

    public function disconnect()
    {
        $customerId = $this->customer->getId();
        $provider = array_get($this->request->post, 'provider');
        $error = '';
        if (!in_array($provider, ['wechat', 'qq', 'weibo'])) {
            $error = 'Invalid provider';
        } elseif (empty($customerId)) {
            $error = 'Not logged in';
        }
        if ($error) {
            $this->jsonOutput(array('error' => $error));
        }
        $customer = Models\Customer::find($customerId);
        $customer->authentications()->byProvider($provider)->delete();
        $this->jsonOutput(array('success' => 'Success'));
    }

    public function logout()
    {
        $this->setProvider($this->request->get['provider']);
        if (isset($this->session->data["{$this->getLoginKey()}"])) {
            unset($this->session->data["{$this->getLoginKey()}"]);
        }
    }
}
