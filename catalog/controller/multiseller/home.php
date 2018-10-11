<?php
class ControllerMultisellerHome extends Controller {
    public function index() {
		if (isset($this->request->get['seller_id'])) {
			$seller_id = (int)$this->request->get['seller_id'];
		} else {
			$seller_id = 0;
		}

		$this->load->language('multiseller/home');

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

        $this->load->model('multiseller/seller');
        $seller_info = $this->model_multiseller_seller->getSeller($seller_id);

        if ($seller_info) {
            $this->document->setTitle($seller_info['store_name']);
            $this->document->setDescription($seller_info['description']);
            $this->document->setKeywords($this->config->get('config_meta_keyword'));

            $this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
            $this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.min.js');

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_mutltiseller'),
                'href' => $this->url->link('multiseller/home')
            );
            $this->load->model('multiseller/seller');

			$filter_data = array(
				'sort'                => 'p.date_added',
				'order'               => 'DESC',
				'start'               => 0,
				'limit'               => 20
			);

            $results = $this->model_multiseller_seller->getSellerProducts($seller_id, $filter_data);

            $data['products'] = array();
			foreach ($results as $result) {
				$href = $this->url->link('product/product', array_merge(['product_id' => $result['product_id']]));
				$data['products'][] = $this->model_catalog_product->handleSingleProduct($result, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'), $href);
			}

            $this->load->model('tool/image');

            $data['banner'] = HTTP_SERVER . 'image/' . $seller_info['banner'];
            $data['store_name'] = $seller_info['store_name'];
            $data['description'] = $seller_info['description'];
            $data['company'] = t('company_name') . ': ' . $seller_info['company'];
            if ($seller_info['avatar']) {
                $data['store_avatar'] = $seller_info['avatar'];
            } else {
                $data['store_avatar'] = 'no_image.png';
            }

            $data['href_all'] = $this->url->link('multiseller/products', 'seller_id=' . $seller_id);

            $this->load->model('localisation/country');
            $this->load->model('localisation/zone');

            $country = $this->model_localisation_country->getCountry($seller_info['country_id']);
            $zone = $this->model_localisation_zone->getZone($seller_info['zone_id']);

            $data['store_loc'] = $country['name'] . ' ' . $zone['name'];

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('multiseller/home', $data));
        } else {
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('multiseller/home', $url . '&seller_id=' . $seller_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }
}
