<?php
class ControllerSellerLogin extends Controller {
	private $error = array();

	public function index() {
		if ($this->customer->isLogged()) {
		    if ($this->customer->isSeller()) {
			    $this->response->redirect($this->url->link('seller/account'));
            } else {
			    $this->response->redirect($this->url->link('seller/account/add'));
            }
		}

		$this->load->language('seller/login');
        $this->load->language('seller/layout');

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

			// Log the IP info
			$this->model_account_customer->addLogin($this->customer->getId(), $this->request->server['REMOTE_ADDR']);

			// Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
			if (isset($this->request->post['redirect']) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false)) {
				$this->response->redirect(str_replace('&amp;', '&', $this->request->post['redirect']));
			} else {
				$this->response->redirect($this->url->link('seller/account'));
			}
		}

		$this->load->language('common/footer', 'footer');
		if ($powered = config('theme_' . config('config_theme') . '_copyright.' . current_language_id())) {
		    $data['powered'] = html_entity_decode($powered, ENT_QUOTES, 'UTF-8');
		} else {
		    $data['powered'] = sprintf($this->language->get('footer')->get('text_powered'), $this->config->get('config_name'), date('Y', time()));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('seller/account')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_login'),
			'href' => $this->url->link('seller/login')
		);

		$data['text_description'] = sprintf($this->language->get('text_description'), $this->config->get('config_name'), $this->config->get('config_name'), $this->config->get('config_seller_commission') . '%');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['action'] = $this->url->link('seller/login');
		$data['register'] = $this->url->link('seller/register');
		$data['forgotten'] = $this->url->link('account/forgotten');

		if (isset($this->request->post['redirect'])) {
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

		if (isset($this->request->post['account'])) {
			$data['account'] = $this->request->post['account'];
		} else {
			$data['account'] = '';
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

		$data['ucode'] = '';
		if (isset($this->request->get['isattract']) && (int)$this->request->get['isattract'] === 1 && isset($this->request->get['code']) && !empty($this->request->get['code'])) {
			$data['ucode'] = $this->request->get['code'];
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('seller/login', $data));
	}

	protected function validate() {
		// Check if customer has been approved.
        if (is_ft()) {
            if ($this->request->post['type'] == 'email') {
                $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['account']);
            } else {
                $customer_info = $this->model_account_customer->getCustomerByTelephone($this->request->post['telephone']);
            }
        } else {
            $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['account']);
            if (!$customer_info) {
                $customer_info = $this->model_account_customer->getCustomerByTelephone($this->request->post['account']);
            }
        }

		if ($customer_info && !$customer_info['status']) {
			$this->error['warning'] = $this->language->get('error_approved');
		}

		if ($customer_info) {
		    $this->load->model('multiseller/seller');
            $seller_info = $this->model_multiseller_seller->getSeller($customer_info['customer_id']);

            //是否商家入驻
            if (!$seller_info) {
                //$this->error['warning'] = $this->language->get('error_not_seller');

                if (isset($this->request->post['ucode']) && !empty($this->request->post['ucode']) )
                {
                	$this->load->model('account/api');

                	$api_id 			= (int)$this->model_account_api->getApiIdByToken($this->request->post['ucode']);

                	if ($api_id > 0) {
	                	$this->session->start($this->request->post['ucode']);
	                	$this->session->data['customer_id'] 	= $customer_info['customer_id'];
                	}
                }
                
                $this->response->redirect('http://attract.easijar.com?from=sellerlogin');
            } else if (!$seller_info['status']) {
                $this->error['warning'] = $this->language->get('error_approved');
            }
            // Check how many login attempts have been made.
            $login_info = $this->model_account_customer->getLoginAttempts($customer_info['customer_id']);

            if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
                $this->error['warning'] = $this->language->get('error_attempts');
            }
        } else {
		    $this->error['warning'] = $this->language->get('error_login');
        }

		if (!$this->error) {
			if (!$this->customer->login($customer_info['customer_id'], $this->request->post['password'])) {
				$this->error['warning'] = $this->language->get('error_login');

				$this->model_account_customer->addLoginAttempt($customer_info['customer_id']);
			} else {
				$this->model_account_customer->deleteLoginAttempts($customer_info['customer_id']);
			}
		}

		return !$this->error;
	}
}