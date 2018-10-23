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
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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

        $sinfo['store_name']    = $seller_info['store_name'];
        $sinfo['is_follow']     = 1;
        $sinfo['follow_num']    = 1;

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
                        'thumb'      => $this->model_tool_image->resize($thumb, 100, 100)
                    );
                }
            }
        }

        $this->load->model('multiseller/seller');
		$filter_data              = [
			'sort'                => 'p.date_added',
			'order'               => 'DESC',
			'start'               => 0,
			'limit'               => 6
		];

        $results                  = $this->model_multiseller_seller->getSellerProducts($seller_id, $filter_data);
		foreach ($results as $result) {
			$href 				= $this->url->link('product/product', array_merge(['product_id' => $result['product_id']]));
            $image              = is_file(DIR_IMAGE . $result['image']) ? $result['image'] : 'no_image.png';

            $json['seller_product'][] = [
                'product_id'    => $result['product_id'],
                'image'         => $this->model_tool_image->resize($image, 100, 100),
                'name'          => $result['name'],
                'price'         => !empty($result['price']) ? $result['price'] : '',
                'special'       => !empty($result['special']) ? $result['special'] : '',
            ];
		}

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    public function products()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','seller_id','page'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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

        $json['seller_product'] = [];//商家商品

        $page                     = isset($req_data['page']) ? (int)$req_data['page']: 0;
        $limit                    = 10;

        $this->load->model('multiseller/seller');
        
        $filter_data              = [
            'sort'                => 'p.date_added',
            'order'               => 'DESC',
            'start'               => $page * $limit,
            'limit'               => $limit
        ];

        $results                  = $this->model_multiseller_seller->getSellerProducts($seller_id, $filter_data);
        foreach ($results as $result) {
            $href               = $this->url->link('product/product', array_merge(['product_id' => $result['product_id']]));
            $image              = is_file(DIR_IMAGE . $result['image']) ? $result['image'] : 'no_image.png';

            $json['seller_product'][] = [
                'product_id'    => $result['product_id'],
                'image'         => $this->model_tool_image->resize($image, 100, 100),
                'name'          => $result['name'],
                'price'         => !empty($result['price']) ? $result['price'] : '',
                'special'       => !empty($result['special']) ? $result['special'] : '',
            ];
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }
}
