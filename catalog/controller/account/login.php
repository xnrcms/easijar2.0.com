<?php
class ControllerAccountLogin extends Controller {
	private $error = array();

	public function index() {
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account'));
		}

		$this->load->language('account/login');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('account/customer');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			// Unset guest
			unset($this->session->data['guest']);

			// Default Shipping Address
			$this->load->model('account/address');

			if ($this->config->get('config_tax_customer') == 'payment') {
				$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			if ($this->config->get('config_tax_customer') == 'shipping') {
				$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			// Wishlist
			if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
				$this->load->model('account/wishlist');

				foreach ($this->session->data['wishlist'] as $key => $product_id) {
					$this->model_account_wishlist->addWishlist($product_id);

					unset($this->session->data['wishlist'][$key]);
				}
			}
			
			// getCouponList
			/*if (isset($this->session->data['getCouponList']) && is_array($this->session->data['getCouponList'])) {
				$this->load->model('marketing/coupon');

				foreach ($this->session->data['getCouponList'] as $key => $coupon_id)
				{
					if ( $this->model_marketing_coupon->isGetCoupon($coupon_id,$this->customer->getId()) <= 0 ) {
			        	$this->model_marketing_coupon->insertCoupon($coupon_id,$this->customer->getId());
			        }

					unset($this->session->data['wishlist'][$key]);
				}
			}*/

			// Log the IP info
			$this->model_account_customer->addLogin($this->customer->getId(), $this->request->server['REMOTE_ADDR']);

			// Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
			if (isset($this->request->post['redirect']) && $this->request->post['redirect'] != $this->url->link('account/logout') && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false)) {
				$this->response->redirect(str_replace('&amp;', '&', $this->request->post['redirect']));
			} else {
				$this->response->redirect($this->url->link('account/account'));
			}
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
			'text' => $this->language->get('text_login'),
			'href' => $this->url->link('account/login')
		);

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} elseif (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['action'] = $this->url->link('account/login');
		$data['register'] = $this->url->link('account/register');
		$data['forgotten'] = $this->url->link('account/forgotten');

		// Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
		if (isset($this->request->post['redirect']) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false)) {
			$data['redirect'] = $this->request->post['redirect'];
		} elseif (isset($this->session->data['redirect'])) {
			$data['redirect'] = $this->session->data['redirect'];

			unset($this->session->data['redirect']);
		} else {
			$data['redirect'] = '';
		}

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

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} else {
			$data['telephone'] = '';
		}

		if (isset($this->request->post['type'])) {
			$data['type'] = $this->request->post['type'];
		} else {
			$data['type'] = 'email';
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

        $data['omni_auth_status'] = $this->config->get('module_omni_auth_status');
        $socials = $this->config->get('module_omni_auth_items');
        $data['omni_auth_socials'] = array();
        if ($socials) {
            foreach ($socials as $key => $social) {
                if ($social['enabled']) {
                    $data['omni_auth_socials'][$key] = $social;
                    $data['omni_auth_socials'][$key]['provider'] = strtolower($social['provider']);
                }
            }
        }

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/login', $data));
	}

	protected function validate() {
		// Check if customer has been approved.
        if (is_ft()) {
            if ($this->request->post['type'] == 'email') {
                $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);
            } else {
                $customer_info = $this->model_account_customer->getCustomerByTelephone($this->request->post['telephone']);
            }
        } else {
            $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);

            if (!$customer_info) {
                $customer_info = $this->model_account_customer->getCustomerByTelephone($this->request->post['email']);
            }
        }

		if ($customer_info) {
            // Check how many login attempts have been made.
            $login_info = $this->model_account_customer->getLoginAttempts($customer_info['customer_id']);

            if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
                $this->error['warning'] = $this->language->get('error_attempts');
            }
        }

		if ($customer_info && !$customer_info['status']) {
			$this->error['warning'] = $this->language->get('error_approved');
		}

		if (!$this->error) {
			if (!$customer_info || !$this->customer->login($customer_info['customer_id'], $this->request->post['password'])) {
				$this->error['warning'] = $this->language->get('error_login');

			    if ($customer_info) {
				    $this->model_account_customer->addLoginAttempt($customer_info['customer_id']);
                }
			} else {
			    if ($customer_info) {
				    $this->model_account_customer->deleteLoginAttempts($customer_info['customer_id']);
                }
			}
		}

		return !$this->error;
	}

	public function token() {
		$this->load->language('account/login');

		if (isset($this->request->get['email'])) {
			$email = $this->request->get['email'];
		} else {
			$email = '';
		}

		if (isset($this->request->get['login_token'])) {
			$token = $this->request->get['login_token'];
		} else {
			$token = '';
		}

		// Login override for admin users
		$this->customer->logout();
		$this->cart->clear();

		unset($this->session->data['order_id']);
		unset($this->session->data['payment_address']);
		unset($this->session->data['payment_method']);
		unset($this->session->data['payment_methods']);
		unset($this->session->data['shipping_address']);
		unset($this->session->data['shipping_method']);
		unset($this->session->data['shipping_methods']);
		unset($this->session->data['comment']);
		unset($this->session->data['coupon']);
		unset($this->session->data['reward']);
		unset($this->session->data['voucher']);
		unset($this->session->data['vouchers']);
        unset($this->session->data['credit']);

		$this->load->model('account/customer');

		$customer_info = $this->model_account_customer->getCustomerByEmail($email);

		if ($customer_info && $customer_info['token'] && $customer_info['token'] == $token && $this->customer->login($customer_info['customer_id'], '', true)) {
			// Default Addresses
			$this->load->model('account/address');

			if ($this->config->get('config_tax_customer') == 'payment') {
				$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			if ($this->config->get('config_tax_customer') == 'shipping') {
				$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			$this->model_account_customer->editToken($customer_info['customer_id'], '');

			$this->response->redirect($this->url->link('account/account'));
		} else {
			$this->session->data['error'] = $this->language->get('error_login');

			$this->model_account_customer->editToken($customer_info['customer_id'], '');

			$this->response->redirect($this->url->link('account/login'));
		}
	}
}
