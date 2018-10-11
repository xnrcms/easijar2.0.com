<?php

/**
 * social.php
 *
 * @copyright  2016 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    11/21/16 15:41
 * @modified   11/21/16 15:41
 */
use Abraham\TwitterOAuth\TwitterOAuth;

class ModelExtensionModuleSocial extends Model
{
    public function initSocialite()
    {
        $socialData = $this->getAllSocialData();
        return new \Overtrue\Socialite\SocialiteManager($socialData);
    }

    public function redirectAuthUrl($provider)
    {
        // for twitter
        if (strtolower($provider) == 'twitter') {
            $social = $this->getSocialByProvider($provider);
            $social_id = $social['client_id'];
            $social_secret = $social['client_secret'];
            $social_redirect = $social['redirect'];
            $twitteroauth = new TwitterOAuth($social_id, $social_secret);
            $request_token = $twitteroauth->oauth('oauth/request_token', ['oauth_callback' => $social_redirect]);
            if($twitteroauth->getLastHttpCode() != 200) {
                throw new \Exception('There was a problem performing this request');
            }
            $this->session->data['twitter_oauth_token'] = $request_token['oauth_token'];
            $this->session->data['twitter_oauth_token_secret'] = $request_token['oauth_token_secret'];
            $url = $twitteroauth->url(
                'oauth/authorize', [
                    'oauth_token' => $request_token['oauth_token']
                ]
            );
            header('location:' . $url);
        }

        $socialite = $this->initSocialite();
        $response = $socialite->driver($provider)->redirect();
        return $response->send();
    }

    public function getSocialByProvider($provider = '')
    {
        return array_get($this->getAllSocialData(), $provider, []);
    }

    public function getAllSocialData()
    {
        $socials = $this->config->get('module_omni_auth_items');
        $allSocialConfigs = array();
        foreach ($socials as $social) {
            $provider = $social['provider'];
            $allSocialConfigs[$provider] = array(
                'client_id' => $social['key'],
                'client_secret' => $social['secret'],
                'redirect' => $social['callback']
            );
        }
        return $allSocialConfigs;
    }

    public function getCustomerByUid($uid, $type)
    {
        $sql = "SELECT auth.id, auth.access_token, c.* FROM " . DB_PREFIX . "customer_authentication AS auth INNER JOIN " . DB_PREFIX . "customer AS c ON auth.customer_id = c.customer_id WHERE auth.provider = '" . $type . "' AND auth.uid = '" . $uid . "'";
        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getCustomerByUnionId($unionId, $type)
    {
        $providerFilter = "auth.provider = '" . $type . "'";
        $sql = "SELECT auth.id, auth.access_token, c.* FROM " . DB_PREFIX . "customer_authentication AS auth INNER JOIN " . DB_PREFIX . "customer AS c ON auth.customer_id = c.customer_id WHERE " . $providerFilter . " AND auth.unionid = '" . $unionId . "'";
        $query = $this->db->query($sql);
        return $query->row;
    }

    public function createAuthentication($data)
    {
        if ($data['uid']) {
            $selectSql = "SELECT * FROM " . DB_PREFIX . "customer_authentication WHERE uid = '{$data['uid']}' AND provider = '{$data['provider']}'";
        } elseif ($data['unionid']) {
            $selectSql = "SELECT * FROM " . DB_PREFIX . "customer_authentication WHERE unionid = '{$data['unionid']}' AND provider = '{$data['provider']}'";
        } else {
            throw new Exception('Invalid uid or unionid when create authentication');
        }
        $query = $this->db->query($selectSql);
        if ($query->row) {
            $updateSql = "UPDATE " . DB_PREFIX . "customer_authentication SET `customer_id` = '{$data['customer_id']}' WHERE `id` = '{$query->row['id']}'";
            return $this->db->query($updateSql);
        }

        $insertSql = "INSERT INTO " . DB_PREFIX . "customer_authentication (`customer_id`, `uid`, `unionid`, `provider`, `access_token`, `avatar`, `date_added`, `date_modified`) VALUES ('{$data['customer_id']}', '{$data['uid']}', '{$data['unionid']}', '{$data['provider']}', '{$data['access_token']}', '{$data['avatar']}', '{$data['date_added']}', '{$data['date_modified']}')";
        $this->db->query($insertSql);
    }

    public function updateAuthentication($data)
    {
        $selectSql = "SELECT * FROM " . DB_PREFIX . "customer_authentication WHERE (uid = '{$data['uid']}' and unionid = '{$data['unionid']}') AND provider = '{$data['provider']}'";
        $query = $this->db->query($selectSql);
        if ($query->row) {
            $updateSql = "UPDATE " . DB_PREFIX . "customer_authentication SET uid = '{$data['uid']}', unionid = '{$data['unionid']}', access_token = '{$data['access_token']}', date_modified = '{$data['date_modified']}' WHERE id = '{$query->row['id']}'";
            $this->db->query($updateSql);
        }
    }

    public function log($data)
    {
        $backtrace = debug_backtrace();
        $this->log->write('Log In with QQ debug (' . $backtrace[1]['class'] . '::' . $backtrace[1]['function'] . ') - ' . $data);
    }
}