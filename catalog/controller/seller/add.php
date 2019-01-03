<?php
class ControllerSellerAdd extends Controller {
	private $error = array();

    public function index()
    {
		$this->load->language('seller/add');
        $this->load->language('seller/layout');
    	$this->load->model('multiseller/seller');

    	$data['show_add'] = 1;

		if (!$this->customer->isLogged()) {
			$this->response->redirect($this->url->link('seller/register'));
		} else if ($this->customer->isSeller()) {
			$this->response->redirect($this->url->link('seller/account'));
        }else{
        	$seller_info = $this->model_multiseller_seller->getSeller($this->customer->getId());

			if (!empty($seller_info) && $seller_info['status'] == '0') {
				$data['show_add'] = 0;
			    $this->error['warning'] = $this->language->get('error_not_pass');
			}else if (!empty($seller_info) && $seller_info['status'] == '1') {
				
				$this->response->redirect($this->url->link('seller/account'));
			}
        }


		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_multiseller_seller->addSeller($this->customer->getId(), $this->request->post);

			$this->response->redirect($this->url->link('seller/success'));
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
			'text' => $this->language->get('text_add'),
			'href' => $this->url->link('seller/add')
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

		$data['action'] = $this->url->link('seller/add');

		if (isset($this->request->post['store_name'])) {
			$data['store_name'] = $this->request->post['store_name'];
		} else {
			$data['store_name'] = '';
		}

		if (isset($this->request->post['company'])) {
			$data['company'] = $this->request->post['company'];
		} else {
			$data['company'] = '';
		}

		if (isset($this->request->post['alipay'])) {
			$data['alipay'] = $this->request->post['alipay'];
		} else {
			$data['alipay'] = '';
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		if (isset($this->request->post['country_id'])) {
			$data['country_id'] = $this->request->post['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}

		if (isset($this->request->post['zone_id'])) {
			$data['zone_id'] = $this->request->post['zone_id'];
		} else {
			$data['zone_id'] = $this->config->get('config_zone_id');
		}

		if (isset($this->request->post['city_id'])) {
			$data['city_id'] = $this->request->post['city_id'];
		} else {
			$data['city_id'] = '';
		}

		if (isset($this->request->post['county_id'])) {
			$data['county_id'] = $this->request->post['county_id'];
		} else {
			$data['county_id'] = '';
		}

		if ($this->config->get('module_multiseller_seller_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('module_multiseller_seller_id'));

			if ($information_info) {
				$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('module_multiseller_seller_id')), $information_info['title'], $information_info['title']);
			} else {
				$data['text_agree'] = '';
			}
		} else {
			$data['text_agree'] = '';
		}

		if (isset($this->request->post['agree'])) {
			$data['agree'] = $this->request->post['agree'];
		} else {
			$data['agree'] = false;
		}
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('seller/add', $data));
	}

	protected function validate() {
		if ((utf8_strlen($this->request->post['store_name']) < 3) || (utf8_strlen($this->request->post['store_name']) > 32)) {
			$this->error['store_name'] = $this->language->get('error_store_name');
		}

		if ((utf8_strlen($this->request->post['company']) < 3) || (utf8_strlen($this->request->post['company']) > 32)) {
			$this->error['company'] = $this->language->get('error_company');
		}

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

		$seller_info = $this->model_multiseller_seller->getSeller($this->customer->getId());
		if (!empty($seller_info) && $seller_info['status'] == '0') {
		    $this->error['warning'] = $this->language->get('error_exists_disable');
		}

		if ($this->config->get('module_multiseller_seller_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('module_multiseller_seller_id'));

			if ($information_info && !isset($this->request->post['agree'])) {
				$this->error['warning'] = sprintf($this->language->get('error_agree'), $information_info['title']);
			}
		}

		return !$this->error;
	}
}
