<?php
class ControllerMultisellerHome extends Controller {
    public function index()
    {
    	$this->response->addHeader('Content-Type: application/json');
		$this->load->language('multiseller/home');

        $allowKey       = ['api_token','seller_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        $seller_id = 0;

		if (isset($req_data['seller_id'])) {
			$seller_id = (int)$this->request->get['seller_id'];
		}

		if ($seller_id <= 0) {
			return $this->response->setOutput($this->returnData(['msg'=>'fail:seller_id error']));
		}

        $this->load->model('multiseller/seller');
        $seller_info 		= $this->model_multiseller_seller->getSeller($seller_id);
        if (!$seller_info) {
        	return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('seller_info_error')]));
        }

        $this->load->model('multiseller/seller');

		$filter_data = array(
			'sort'                => 'p.date_added',
			'order'               => 'DESC',
			'start'               => 0,
			'limit'               => 20
		);

        $results = $this->model_multiseller_seller->getSellerProducts($seller_id, $filter_data);

        $data['products'] 		= [];
		foreach ($results as $result) {
			$href 				= $this->url->link('product/product', array_merge(['product_id' => $result['product_id']]));
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
       
    }
}
