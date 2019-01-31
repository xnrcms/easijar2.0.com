<?php
class ControllerApiMultiseller extends Controller {
    public function index()
    {
    	$this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','seller_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $seller_id      = isset($req_data['seller_id']) ? (int)$req_data['seller_id']: 0;
		if ($seller_id <= 0) {
			return $this->response->setOutput($this->returnData(['msg'=>'fail:seller_id error']));
		}

        $this->load->model('multiseller/seller');
        $seller_info 		= $this->model_multiseller_seller->getSeller($seller_id);
        if (!$seller_info) {
        	return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('seller_info_error')]));
        }
        
        $this->load->model('tool/image');
            //catalog/seller_products/15/1.jpg

        $json['seller_info']    = [];//商家信息
        $json['seller_banner']  = [];//商家Banner
        $json['seller_product'] = [];//商家商品

        $sinfo                  = [];
        $sinfo['seller_id']     = (int)$seller_info['seller_id'];
        $sinfo['store_name']    = $seller_info['store_name'];
        $sinfo['description']   = $seller_info['description'];

        $avatar                 = !empty($seller_info['avatar']) ? $seller_info['avatar'] : 'no_image.png';
        $banner                 = !empty($seller_info['banner']) ? $seller_info['banner'] : 'no_image.png';
        $sinfo['avatar']        = $this->model_tool_image->resize($avatar, 100, 100);
        $sinfo['banner']        = $this->model_tool_image->resize($avatar, 100, 100);

        $this->load->model('account/customer_follow_seller');

        //是否被收藏
        $follow_total           = 0;
        if (!$this->customer->isLogged()){
            if (isset($this->session->data['customer_follow_seller']) && in_array($sinfo['seller_id'], $this->session->data['customer_follow_seller'])){
                $follow_total     = 1;
            }
        }else{
            $follow_total     = $this->model_account_customer_follow_seller->getSellerFollowByCustomerId($sinfo['seller_id']);
        }

        $sinfo['store_name']    = htmlspecialchars_decode($seller_info['store_name']);
        $sinfo['is_follow']     = (int)$follow_total;
        $sinfo['follow_num']    = (int)$this->model_account_customer_follow_seller->getSellerFollowBySellerId($sinfo['seller_id']);

        $json['seller_info']    = $sinfo;

        $this->load->model('multiseller/seller_banner');

        $banner_info        = $this->model_multiseller_seller_banner->getSellerBanner($seller_id);
        if (!empty($banner_info)) {
            $banner_images      = $this->model_multiseller_seller_banner->getSellerBannerImages($seller_id,$this->config->get('config_language_id'));
            foreach ($banner_images as $key => $value) {
                foreach ($value as $banner_image) {
                    if (is_file(DIR_IMAGE . $banner_image['image'])) {
                        $thumb = $banner_image['image'];
                    } else {
                        $thumb = 'no_image.png';
                    }
                    
                    $json['seller_banner'][] = array(
                        'title'      => $banner_image['title'],
                        'link'       => $banner_image['link'],
                        'image'      => $this->model_tool_image->resize($thumb, 750, 340)
                    );
                }
            }
        }

        $this->load->model('multiseller/seller');
		$filter_data              = [
			'sort'                => 'p.date_modified',
			'order'               => 'DESC',
            'parent_id'           => 0,
			'start'               => 0,
			'limit'               => 20
		];

        $results                  = $this->model_multiseller_seller->getSellerProducts($seller_id, $filter_data);
		foreach ($results as $result) {
			$href 				= $this->url->link('product/product', array_merge(['product_id' => $result['product_id']]));
            $image              = is_file(DIR_IMAGE . $result['image']) ? $result['image'] : 'no_image.png';

            $json['seller_product'][] = [
                'product_id'    => $result['product_id'],
                'image'         => $this->model_tool_image->resize($image, 200, 200),
                'name'          => $result['name'],
                'price'         => !empty($result['price']) ? $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : '',
                'special'       => !empty($result['special']) ? $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : '',
            ];
		}

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    public function products()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','seller_id','page','limit'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        $seller_id      = isset($req_data['seller_id']) ? (int)$req_data['seller_id']: 0;
        if ($seller_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:seller_id error']));
        }

        $this->load->model('multiseller/seller');
        $seller_info        = $this->model_multiseller_seller->getSeller($seller_id);
        if (!$seller_info) {
            return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('seller_info_error')]));
        }

        $this->load->model('tool/image');
        $this->load->model('multiseller/seller');

        $json['seller_product']   = [];//商家商品

        $page                     = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit                    = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;

        $filter_data              = [
            'sort'                => 'p.date_modified',
            'order'               => 'DESC',
            'parent_id'           => 0,
            'start'               => $page * $limit,
            'limit'               => $limit
        ];

        $product_total            = $this->model_multiseller_seller->getTotalSellerProducts($seller_id);
        $results                  = $this->model_multiseller_seller->getSellerProducts($seller_id, $filter_data);
        $products                 = [];

        foreach ($results as $result) {
            $href               = $this->url->link('product/product', array_merge(['product_id' => $result['product_id']]));
            $image              = is_file(DIR_IMAGE . $result['image']) ? $result['image'] : 'no_image.png';

            $price_c        = 0;
            $special_c      = 0;

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $price_c    = $result['price'];
                $price      = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }

            if ((float)$result['special']) {
                $special_c  = $result['special'];
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $special = false;
            }

            $discount       = ($price_c > 0 && $price_c >= $special_c) ? round(($price_c - $special_c) / $price_c)*100 : 0;

            $products[] = [
                'product_id'    => $result['product_id'],
                'image'         => $this->model_tool_image->resize($image, 200, 200),
                'name'          => $result['name'],
                'price'         => !empty($result['price']) ? $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : '',
                'special'       => !empty($result['special']) ? $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : '',
                'discount'      => $discount,
                'rating'        => 5,
                'rating_num'    => 10,
            ];
        }

        $remainder                  = intval($product_total - $limit * $page);
        $data                       = [];
        $data['total_page']         = ceil($product_total/$limit);
        $data['remainder']          = $remainder >= 0 ? $remainder : 0;
        $data['products']           = $products;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
    }

    public function search()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('product/search');

        $allowKey       = ['api_token','page','limit','search','seller_id'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        $seller_id      = isset($req_data['seller_id']) ? (int)$req_data['seller_id']: 0;
        if ($seller_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:seller_id error']));
        }

        $this->load->model('multiseller/seller');
        $seller_info        = $this->model_multiseller_seller->getSeller($seller_id);
        if (!$seller_info) {
            return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('seller_info_error')]));
        }

        $this->load->model('tool/image');
        $this->load->model('multiseller/seller');

        //0:默认排序，1：名称 A - Z 2:名称 Z - A 3:价格 低 - 高 4：价格 高 - 低 5：评级 低 - 高 6：评级 高 - 低 7：型号 A - Z 8：型号 Z - A
        $sortsArr           = ['p.sort_order','pd.name-ASC','pd.name-DESC','p.price-ASC','p.price-DESC','rating-ASC','rating-DESC'];

        $search             = isset($req_data['search']) ? (string)$req_data['search'] : '';
        $search             = urlencode(html_entity_decode($search, ENT_QUOTES, 'UTF-8'));

        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;
        
        $filter_data              = [
            'sort'                => 'p.date_added',
            'order'               => 'DESC',
            'start'               => $page * $limit,
            'limit'               => $limit,
            'filter_name'         => $search
        ];

        $product_total          = $this->model_multiseller_seller->getTotalSellerProducts($seller_id, $filter_data);
        $results                = $this->model_multiseller_seller->getSellerProducts($seller_id, $filter_data);
        $products               = [];

        foreach ($results as $result) {
            $href               = $this->url->link('product/product', array_merge(['product_id' => $result['product_id']]));
            $image              = is_file(DIR_IMAGE . $result['image']) ? $result['image'] : 'no_image.png';

            $price_c        = 0;
            $special_c      = 0;

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $price_c    = $result['price'];
                $price      = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }

            if ((float)$result['special']) {
                $special_c  = $result['special'];
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $special = false;
            }

            $discount       = ($price_c > 0 && $price_c >= $special_c) ? round(($price_c - $special_c) / $price_c)*100 : 0;

            $products[] = [
                'product_id'    => $result['product_id'],
                'image'         => $this->model_tool_image->resize($image, 100, 100),
                'name'          => $result['name'],
                'price'         => !empty($price) ? $price : '',
                'special'       => !empty($special) ? $special : '',
                'discount'      => $discount,
                'rating'        => 5,
                'rating_num'    => 10,
            ];
        }

        $remainder                  = intval($product_total - $limit * $page);
        $data                       = [];
        $data['total_page']         = ceil($product_total/$limit);
        $data['remainder']          = $remainder >= 0 ? $remainder : 0;
        $data['products']           = $products;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
    }
}
