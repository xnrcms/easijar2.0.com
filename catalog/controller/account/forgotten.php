<?php
class ControllerAccountForgotten extends Controller {
	private $error = array();

	public function index() {
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account'));
		}

		$this->load->language('account/forgotten');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('account/customer');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
		    if ($this->request->post['type'] == 'telephone') {
                $customer_info = $this->model_account_customer->getCustomerByTelephone($this->request->post['telephone']);
                $this->model_account_customer->editPassword($customer_info['customer_id'], $this->request->post['password']);
                unset($this->session->data['smscode']);

                $this->session->data['success'] = $this->language->get('text_sms_success');

                $this->response->redirect($this->url->link('account/login'));
            }
			$customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);
			$this->model_account_customer->editCode($customer_info['customer_id'], token(40));

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('account/login'));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_forgotten'),
			'href' => $this->url->link('account/forgotten')
		);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

        if (isset($this->error['telephone'])) {
            $data['error_telephone'] = $this->error['telephone'];
        } else {
            $data['error_telephone'] = '';
        }

        if (isset($this->error['smscode'])) {
            $data['error_smscode'] = $this->error['smscode'];
        } else {
            $data['error_smscode'] = '';
        }

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		if (isset($this->error['confirm'])) {
			$data['error_confirm'] = $this->error['confirm'];
		} else {
			$data['error_confirm'] = '';
		}

		$data['action'] = $this->url->link('account/forgotten');

		$data['back'] = $this->url->link('account/login');

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} else {
			$data['telephone'] = '';
		}

		if (isset($this->request->post['smscode'])) {
			$data['smscode'] = $this->request->post['smscode'];
		} else {
			$data['smscode'] = '';
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->post['confirm'])) {
			$data['confirm'] = $this->request->post['confirm'];
		} else {
			$data['confirm'] = '';
		}

        $data['module_sms_status'] = $this->config->get('module_sms_status') && $this->config->get('module_sms_customer_register_verify_message');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/forgotten', $data));
	}

	protected function validate() {
	    if ($this->request->post['type'] == 'email') {
            if (!isset($this->request->post['email'])) {
                $this->error['warning'] = $this->language->get('error_email');
            } elseif (!$this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
                $this->error['warning'] = $this->language->get('error_email');
            }

		    // Check if customer has been approved.
            $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);
            if ($customer_info && !$customer_info['status']) {
                $this->error['warning'] = $this->language->get('error_approved');
            }

        }

        if ($this->request->post['type'] == 'telephone') {
            if (!isset($this->request->post['telephone'])) {
                $this->error['warning'] = $this->language->get('error_telephone');
            } elseif (!$this->model_account_customer->getTotalCustomersByTelephone($this->request->post['telephone'])) {
                $this->error['warning'] = $this->language->get('error_telephone');
            }

		    // Check if customer has been approved.
            $customer_info = $this->model_account_customer->getCustomerByTelephone($this->request->post['telephone']);

            if ($customer_info && !$customer_info['status']) {
                $this->error['warning'] = $this->language->get('error_approved');
            }

            if ($this->session->data['smscode']['code'] != $this->request->post['smscode'] || $this->session->data['smscode']['time'] + 600 < time()) {
                $this->error['smscode'] = $this->language->get('error_code');
            }

            if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
                $this->error['password'] = $this->language->get('error_password');
            }

            if ($this->request->post['confirm'] !== $this->request->post['password']) {
                $this->error['confirm'] = $this->language->get('error_confirm');
            }
        }

		return !$this->error;
	}

    public function verify() {
	    $this->load->language('account/forgotten');
        $json = array();

        $telephone = $this->request->post['telephone'];

        if (array_get($this->request->post, 'telephone') && ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32))) {
            $json = array(
                'error' => $this->language->get('error_telephone'),
            );
        }
        $this->load->model('account/customer');
        $customer_info = $this->model_account_customer->getCustomerByTelephone($telephone);
        if (!$customer_info) {
            $json = array(
                'error' => $this->language->get('error_telephone_not_exists'),
            );
        }

        if (!$json) {
            $code = mt_rand(100000, 999999); //生成校验码

			$this->session->data['smscode'] = array(
			    'code' => $code,
                'time' => time(),
            );
            $this->load->model('notify/notify');
            $ret = $this->model_notify_notify->findBackPassword($telephone, $code);
            if ($ret === true) {
                $json = array(
                    'status' => 'success',
                );
            } else {
                $json = array(
                    'status' => 'fail',
                    'msg' => 'Fail: '.$ret,
                );
            }
        }

        $this->response->setOutput(json_encode($json));
    }
}
