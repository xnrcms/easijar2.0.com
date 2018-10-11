<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-12-13 17:04:00
 * @modified         2016-12-13 17:04:00
 */

class ControllerWechatLogin extends Controller {
    private $error = array();

    public function index() {
        if (!array_get($this->session->data, 'userinfo.openid')) {
            $this->response->redirect($this->url->link('common/home'));
        }
        $this->load->model('account/weixin_login');

        $this->load->language('wechat/login');

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->get['operate'])) {
            if ($this->request->get['operate'] == "new") {
                //下面进行登陆
                $this->load->model('account/weixin_login');
                $userinfo = $this->session->data['userinfo'];
                $_log = new \Log('wechat_login');
                $_log->write('WEIXIN_GZ login userinfo: ' . print_r($this->session->data['userinfo'], true));
                unset($this->session->data['userinfo']);
                $customer_id = $this->model_account_weixin_login->addWeixinCustomer($userinfo);
                $this->customer->login($customer_id, '', true);

                $url = $this->session->data['wxredirect'];
                unset($this->session->data['wxredirect']);
                //header('Location: ' . $url);
                $this->response->redirect(str_replace('&amp;', '&', $url));
            } else {  //$this->request->get['operate'] == "old" //跳转到账号绑定页面（功能相当于登陆页面）
                $this->response->redirect($this->url->link('wechat/login/bind'));
            }
        }

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

        $data['action_old'] = $this->url->link('wechat/login', 'operate=old');
        $data['action_new'] = $this->url->link('wechat/login', 'operate=new');

        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('wechat/login_select', $data));
    }

    public function bind()
    {
        if (!array_get($this->session->data, 'userinfo.openid')) {
            $this->response->redirect($this->url->link('common/home'));
        }
        $this->load->model('account/weixin_login');

        $this->load->language('wechat/login');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            unset($this->session->data['guest']);

            // Default Shipping Address
            $this->load->model('account/address');

            if ($this->config->get('config_tax_customer') == 'payment') {
                $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
            }

            if ($this->config->get('config_tax_customer') == 'shipping') {
                $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
            }

            // Add to activity log
            $this->load->model('account/activity');

            $activity_data = array(
                'customer_id' => $this->customer->getId(),
                'name'        => $this->customer->getFullName()
            );

            $this->model_account_activity->addActivity('login', $activity_data);

            $url = $this->session->data['wxredirect'];
            unset($this->session->data['wxredirect']);
            //header('Location: ' . $url);
            $this->response->redirect(str_replace('&amp;', '&', $url));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['action'] = $this->url->link('wechat/login/bind');
        $data['forgotten'] = $this->url->link('weixin/forgotten');

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->request->post['email'])) {
            $data['email'] = $this->request->post['email'];
        } else {
            $data['email'] = '';
        }

        if (isset($this->request->post['password'])) {
            $data['password'] = $this->request->post['password'];
        } else {
            $data['password'] = '';
        }

        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('wechat/login_bind', $data));
    }

    protected function validate()
    {
        // Check how many login attempts have been made.
        $this->load->model('account/customer');
        // Check if customer has been approved.
        if (preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $this->request->post['email'])) {  //邮箱
            $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);
        } else {  //手机
            $customer_info = $this->model_account_customer->getCustomerByTelephone($this->request->post['email']);
        }

        if (!$customer_info) {
            $this->error['warning'] = $this->language->get('error_login');
        }

        if (!$this->error) {
            // Check how many login attempts have been made.
            $login_info = $this->model_account_customer->getLoginAttempts($customer_info['customer_id']);

            if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
                $this->error['warning'] = $this->language->get('error_attempts');
            }

            if (!$this->customer->login($customer_info['customer_id'], $this->request->post['password'])) {
                $this->error['warning'] = $this->language->get('error_login');

                $this->model_account_customer->addLoginAttempt($customer_info['customer_id']);
            } else {
                $this->model_account_customer->deleteLoginAttempts($customer_info['customer_id']);
                $this->load->model('account/weixin_login');
                $this->model_account_weixin_login->bindCustomer($customer_info['customer_id'],$this->session->data['userinfo']);
                unset($this->session->data['userinfo']);
            }
        }

        return !$this->error;
    }
}
