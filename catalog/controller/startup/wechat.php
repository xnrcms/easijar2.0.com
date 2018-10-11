<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-12-13 17:04:00
 * @modified         2016-12-13 17:04:00
 */

class ControllerStartupWechat extends Controller
{
    public function index()
    {

        // null标识模块没安装，针对安装了没开启的，微信支付不可用，但微信绑定登陆正常，没安装的微信绑定登陆也不可用
        if (null === $this->config->get('payment_wechat_pay_status')) {
            return;
        }

        $route = 'common/home';
        if (isset($this->request->get['route'])) {
            $route = $this->request->get['route'];
        }

        // 小程序和 app 不需要登录
        $excludes = ['app/', 'miniapp/'];
        foreach ($excludes as $routePrefix) {
            if (starts_with($route, $routePrefix)) {
                return;
            }
        }

        if ($this->config->get('is_weixin') && strpos($route, 'wechat/login') !== 0 && $route != 'account/logout') {
            $log = new Log('wechat_login.log');
            if (!$this->customer->isLogged()) {
                if (!isset($this->session->data['wxredirect'])) {
                    $au = parse_url(HTTP_SERVER);
                    $url = $au['scheme'] . '://' . $au['host'] . $_SERVER["REQUEST_URI"];
                    $this->session->data['wxredirect'] = $url;
                }

                $userinfo = $this->getWechatOauthUserinfo();
                if (!$userinfo) {
                    return;
                }
                $this->session->data['userinfo'] = $userinfo;

                $this->load->model('account/weixin_login');

                $log->write('start/wechat weixin_gz userinfo: ' . print_r($userinfo, true));
                $customer_info = $this->model_account_weixin_login->getCustomerByOpenid($userinfo['openid']);

                if ($customer_info && $customer_info['status']) { //当前openid已经有账号
                    $this->customer->login($customer_info['customer_id'], '', true);
                } else {
                    if ($customer_info && !$customer_info['status']) {
                        $this->session->data['error'] = $this->language->get('error_login_approved');
                        $this->response->redirect($this->url->link('wechat/login'));
                    } else if ($userinfo['unionid'] && $customer_info = $this->model_account_weixin_login->getCustomerByUnionid($userinfo['unionid'])) { //当前unionid已经有账号，即微信扫码登陆账号
                        $this->model_account_weixin_login->bindCustomer($customer_info['customer_id'], $userinfo);
                        $this->customer->login($customer_info['customer_id'], '', true);
                    } else {
                        //弹出微信登陆选择页面，让用户选择“PC端已注册账号”还是“首次访问本站”。
                        $this->response->redirect($this->url->link('wechat/login'));
                    }
                }
            }
        }
    }

    private function getWechatOauthUserinfo()
    {
        $options = array(
            'appid' => $this->config->get('payment_wechat_pay_app_id'),
            'appsecret' => $this->config->get('payment_wechat_pay_app_secret'),
            'cachepath' => DIR_LOGS . 'wechat/'
        );
        \Wechat\Loader::config($options);
        $oauth = new \Wechat\WechatOauth();

        if (!isset($_GET['code'])) {
            $redirect = $oauth->getOauthRedirect($this->session->data['wxredirect'], 'guangda', 'snsapi_userinfo');

            if ($redirect === false) {
                throw new \Exception('Error: getOauthRedirect!');
            } else {
                Header("Location: $redirect");
            }
        } else {
            $result = $oauth->getOauthAccessToken();
            if ($result === false) {
                throw new \Exception('Error: getOauthAccessToken!');
            } else {
                $userinfo = $oauth->getOauthUserinfo($result['access_token'], $result['openid']);
            }

            if (!isset($userinfo['unionid'])) {
                $userinfo['unionid'] = '';
            }

            $userinfo['access_token'] = $result['access_token'];
            return $userinfo;
        }
    }
}
