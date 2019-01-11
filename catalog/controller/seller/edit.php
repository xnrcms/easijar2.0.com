<?php
class ControllerSellerEdit extends Controller {
	private $error = array();
    private $ms_seller = null;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->ms_seller = \Seller\MsSeller::getInstance($registry);
        $_SESSION['seller_upload_permission'] = $this->ms_seller->sellerId();
    }

    public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('seller/edit');

			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
		}

        if (!session_id()) {
            session_start();
        }
        $_SESSION['seller_upload_permission'] = $this->ms_seller->sellerId();

		$this->load->language('seller/edit');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_multiseller_seller->editSeller($this->customer->getId(), $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('seller/account'));
		}

        $editor_data = $this->editorData();
        $data['placeholder'] = $editor_data['placeholder'];
        $data['editor_language'] = $editor_data['editor_language'];

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
			'text' => $this->language->get('text_edit'),
			'href' => $this->url->link('seller/edit')
		);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['company'])) {
			$data['error_company'] = $this->error['company'];
		} else {
			$data['error_company'] = '';
		}

		if (isset($this->error['description'])) {
			$data['error_description'] = $this->error['description'];
		} else {
			$data['error_description'] = '';
		}

		if (isset($this->error['store_name'])) {
			$data['error_store_name'] = $this->error['store_name'];
		} else {
			$data['error_store_name'] = '';
		}

		if (isset($this->error['alipay'])) {
			$data['error_alipay'] = $this->error['alipay'];
		} else {
			$data['error_alipay'] = '';
		}

		if (isset($this->error['country'])) {
			$data['error_country'] = $this->error['country'];
		} else {
			$data['error_country'] = '';
		}

		if (isset($this->error['zone'])) {
			$data['error_zone'] = $this->error['zone'];
		} else {
			$data['error_zone'] = '';
		}

		if (isset($this->error['city'])) {
			$data['error_city'] = $this->error['city'];
		} else {
			$data['error_city'] = '';
		}

		if (isset($this->error['county'])) {
			$data['error_county'] = $this->error['county'];
		} else {
			$data['error_county'] = '';
		}

		if (isset($this->error['return_shipping_name'])) {
			$data['error_return_shipping_name'] = $this->error['return_shipping_name'];
		} else {
			$data['error_return_shipping_name'] = '';
		}
		if (isset($this->error['return_shipping_mobile'])) {
			$data['error_return_shipping_mobile'] = $this->error['return_shipping_mobile'];
		} else {
			$data['error_return_shipping_mobile'] = '';
		}
		if (isset($this->error['return_shipping_address1'])) {
			$data['error_return_shipping_address1'] = $this->error['return_shipping_address1'];
		} else {
			$data['error_return_shipping_address1'] = '';
		}
		if (isset($this->error['return_shipping_address2'])) {
			$data['error_return_shipping_address2'] = $this->error['return_shipping_address2'];
		} else {
			$data['error_return_shipping_address2'] = '';
		}
		if (isset($this->error['return_shipping_zip_code'])) {
			$data['error_return_shipping_zip_code'] = $this->error['return_shipping_zip_code'];
		} else {
			$data['error_return_shipping_zip_code'] = '';
		}

		$data['action'] = $this->url->link('seller/edit');

		$this->load->model('multiseller/seller');
		$seller_info = $this->model_multiseller_seller->getSeller($this->customer->getId());
		$return_info = $this->model_multiseller_seller->getSellerReturnAddress($this->customer->getId());

		$seller_info = array_merge($seller_info,$return_info);

		if (isset($this->request->post['store_name'])) {
            $data['store_name'] = $this->request->post['store_name'];
        } else if (!empty($seller_info)) {
			$data['store_name'] = $seller_info['store_name'];
		} else {
			$data['store_name'] = '';
		}

		if (isset($this->request->post['company'])) {
			$data['company'] = $this->request->post['company'];
        } else if (!empty($seller_info)) {
			$data['company'] = $seller_info['company'];
		} else {
			$data['company'] = '';
		}

		if (isset($this->request->post['description'])) {
			$data['description'] = $this->request->post['description'];
        } else if (!empty($seller_info)) {
			$data['description'] = $seller_info['description'];
		} else {
			$data['description'] = '';
		}

		if (isset($this->request->post['avatar'])) {
			$data['avatar'] = $this->request->post['avatar'];
        } else if (!empty($seller_info)) {
			$data['avatar'] = $seller_info['avatar'];
		} else {
			$data['avatar'] = $this->config->get('avatar');
		}

		$this->load->model('tool/image');
		if (isset($this->request->post['avatar']) && is_file(DIR_IMAGE . $this->request->post['avatar'])) {
			$data['icon_avatar'] = $this->model_tool_image->resize($this->request->post['avatar'], 100, 100);
        } else if (!empty($seller_info) && is_file(DIR_IMAGE . $seller_info['avatar'])) {
			$data['icon_avatar'] = $this->model_tool_image->resize($seller_info['avatar'], 100, 100);
		} else {
			$data['icon_avatar'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		if (isset($this->request->post['banner'])) {
			$data['banner'] = $this->request->post['banner'];
        } else if (!empty($seller_info)) {
			$data['banner'] = $seller_info['banner'];
		} else {
			$data['banner'] = $this->config->get('banner');
		}

		if (isset($this->request->post['banner']) && is_file(DIR_IMAGE . $this->request->post['banner'])) {
			$data['icon_banner'] = $this->model_tool_image->resize($this->request->post['banner'], 100, 100);
        } else if (!empty($seller_info) && is_file(DIR_IMAGE . $seller_info['banner'])) {
			$data['icon_banner'] = $this->model_tool_image->resize($seller_info['banner'], 100, 100);
		} else {
			$data['icon_banner'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		if (isset($this->request->post['alipay'])) {
			$data['alipay'] = $this->request->post['alipay'];
        } else if (!empty($seller_info)) {
			$data['alipay'] = $seller_info['alipay'];
		} else {
			$data['alipay'] = '';
		}

		if (isset($this->request->post['return_shipping_name'])) {
			$data['return_shipping_name'] = $this->request->post['return_shipping_name'];
        } else if (!empty($seller_info)) {
			$data['return_shipping_name'] = $seller_info['return_shipping_name'];
		} else {
			$data['return_shipping_name'] = '';
		}

		if (isset($this->request->post['return_shipping_mobile'])) {
			$data['return_shipping_mobile'] = $this->request->post['return_shipping_mobile'];
        } else if (!empty($seller_info)) {
			$data['return_shipping_mobile'] = $seller_info['return_shipping_mobile'];
		} else {
			$data['return_shipping_mobile'] = '';
		}

		if (isset($this->request->post['return_shipping_address1'])) {
			$data['return_shipping_address1'] = $this->request->post['return_shipping_address1'];
        } else if (!empty($seller_info)) {
			$data['return_shipping_address1'] = $seller_info['return_shipping_address1'];
		} else {
			$data['return_shipping_address1'] = '';
		}

		if (isset($this->request->post['return_shipping_address2'])) {
			$data['return_shipping_address2'] = $this->request->post['return_shipping_address2'];
        } else if (!empty($seller_info)) {
			$data['return_shipping_address2'] = $seller_info['return_shipping_address2'];
		} else {
			$data['return_shipping_address2'] = '';
		}

		if (isset($this->request->post['return_shipping_zip_code'])) {
			$data['return_shipping_zip_code'] = $this->request->post['return_shipping_zip_code'];
        } else if (!empty($seller_info)) {
			$data['return_shipping_zip_code'] = $seller_info['return_shipping_zip_code'];
		} else {
			$data['return_shipping_zip_code'] = '';
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		if (isset($this->request->post['country_id'])) {
			$data['country_id'] = $this->request->post['country_id'];
        } else if (!empty($seller_info)) {
			$data['country_id'] = $seller_info['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}

		if (isset($this->request->post['zone_id'])) {
			$data['zone_id'] = $this->request->post['zone_id'];
        } else if (!empty($seller_info)) {
			$data['zone_id'] = $seller_info['zone_id'];
		} else {
			$data['zone_id'] = $this->config->get('config_zone_id');
		}

		if (isset($this->request->post['city_id'])) {
			$data['city_id'] = $this->request->post['city_id'];
        } else if (!empty($seller_info)) {
			$data['city_id'] = $seller_info['city_id'];
		} else {
			$data['city_id'] = '';
		}

		if (isset($this->request->post['county_id'])) {
			$data['county_id'] = $this->request->post['county_id'];
        } else if (!empty($seller_info)) {
			$data['county_id'] = $seller_info['county_id'];
		} else {
			$data['county_id'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('seller/edit', $data));
	}

	protected function validate() {
		if ((utf8_strlen($this->request->post['store_name']) < 3) || (utf8_strlen($this->request->post['store_name']) > 32)) {
			$this->error['store_name'] = $this->language->get('error_store_name');
		}

		if ((utf8_strlen($this->request->post['company']) < 3) || (utf8_strlen($this->request->post['company']) > 32)) {
			$this->error['company'] = $this->language->get('error_company');
		}

//		if ((utf8_strlen($this->request->post['description']) < 30) || (utf8_strlen($this->request->post['description']) > 500)) {
//			$this->error['description'] = $this->language->get('error_description');
//		}

		if ((utf8_strlen($this->request->post['alipay']) < 10) || (utf8_strlen($this->request->post['alipay']) > 64)) {
			$this->error['alipay'] = $this->language->get('error_alipay');
		}

		if ($this->request->post['country_id'] == '' || !is_numeric($this->request->post['country_id'])) {
			$this->error['country'] = $this->language->get('error_country');
		}

		if (!isset($this->request->post['zone_id']) || $this->request->post['zone_id'] == '' || !is_numeric($this->request->post['zone_id'])) {
			$this->error['zone'] = $this->language->get('error_zone');
		}

		if (!isset($this->request->post['city_id']) || $this->request->post['city_id'] == '' || !is_numeric($this->request->post['city_id'])) {
			$this->error['city'] = $this->language->get('error_city');
		}

		if (!isset($this->request->post['county_id']) || $this->request->post['county_id'] == '' || !is_numeric($this->request->post['county_id'])) {
			$this->error['county'] = $this->language->get('error_county');
		}

		if ((utf8_strlen($this->request->post['return_shipping_name']) < 2) || (utf8_strlen($this->request->post['return_shipping_name']) > 10)) {
			$this->error['return_shipping_name'] = $this->language->get('error_return_shipping_name');
		}
		if ((utf8_strlen($this->request->post['return_shipping_mobile']) < 10) || (utf8_strlen($this->request->post['return_shipping_mobile']) > 20)) {
			$this->error['return_shipping_mobile'] = $this->language->get('error_return_shipping_mobile');
		}
		if ((utf8_strlen($this->request->post['return_shipping_address1']) < 5) || (utf8_strlen($this->request->post['return_shipping_address1']) > 100)) {
			$this->error['return_shipping_address1'] = $this->language->get('error_return_shipping_address1');
		}
		if ((utf8_strlen($this->request->post['return_shipping_address2']) < 5) || (utf8_strlen($this->request->post['return_shipping_address2']) > 100)) {
			$this->error['return_shipping_address2'] = $this->language->get('error_return_shipping_address2');
		}
		if ((utf8_strlen($this->request->post['return_shipping_zip_code']) < 5) || (utf8_strlen($this->request->post['return_shipping_zip_code']) > 10)) {
			$this->error['return_shipping_zip_code'] = $this->language->get('error_return_shipping_zip_code');
		}

		$seller_info = $this->model_multiseller_seller->getSeller($this->customer->getId());
		if (!empty($seller_info) && $seller_info['status'] == '0') {
		    $this->error['warning'] = $this->language->get('error_exists_disable');
		}

		return !$this->error;
	}

    protected function editorData()
    {
        return \Seller\Editor::getInstance($this->registry)->getEditorData();
    }
}
