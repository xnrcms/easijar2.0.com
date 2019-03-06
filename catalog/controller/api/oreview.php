<?php
class ControllerApiOreview extends Controller {
    //可评论商品列表
    public function product()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');

        $allowKey       = ['api_token','order_sn'];
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

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/order');
        $this->load->model('tool/image');
        $this->load->model('account/oreview');

        $order_info                     = $this->model_account_order->getOrderForMs($req_data['order_sn']);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        //商品信息
        $order_id                       = isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0;
        $seller_id                      = isset($order_info['seller_id']) ? (int)$order_info['seller_id'] : 0;
        $product_info                   = $this->model_account_order->getOrderProductsForMs($order_id,$seller_id);
        $pro_data                       = [];

        foreach ($product_info as $pkey => $pval) {
            if (!$this->model_account_oreview->isReviewed($pval['order_product_id'])) {
                $pro_data[]                 = [
                    'order_product_id'  => (int)$pval['order_product_id'],
                    'image'             =>$this->model_tool_image->resize($pval['image'], 100, 100),
                ];
            }
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$pro_data]));
    }

    //订单商品评价
    public function add()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/oreview');

        $allowKey       = ['api_token','order_sn','oreview_data'];
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

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/order');

        $order_info                         = $this->model_account_order->getOrderForMs($req_data['order_sn']);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        //商品信息
        $order_id                       = isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0;
        $seller_id                      = isset($order_info['seller_id']) ? (int)$order_info['seller_id'] : 0;
        $product_info                   = $this->model_account_order->getOrderProductsForMs($order_id,$seller_id);
        $pro_data                       = [];

        foreach ($product_info as $pkey => $pval)
        {
            $this->load->model('account/oreview');

            $is_reviewed                    = $this->model_account_oreview->isReviewed($pval['order_product_id']);
            $complated                      = in_array((int)$order_info['order_status_id'], $this->config->get('config_complete_status'));
            if (!$complated) {
                return $this->response->setOutput($this->returnData(['msg'=>'fail:order_status is error']));
            }

            if ($is_reviewed) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_alredy_reviewed')]));
            }

            $pro_data[$pval['order_product_id']]    = $pval['order_product_id'];
        }

        $pcount                             = count($pro_data);

        //解析评论数据
        if (!is_json($req_data['oreview_data'])) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:oreview_data is error']));
        }

        $oreview_data                       = json_decode($req_data['oreview_data'],true);
        if ($pcount !== count($oreview_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:oreview_data is error']));
        }

        $oreview                            = [];
        foreach ($oreview_data as $key => $value) {
            if (!in_array($value['order_product_id'], $pro_data))  continue;

            $code                                   = !empty($value['images']) ? explode(',', $value['images']) : [];
            $text                                   = $value['content'];
            $rating                                 = (int)$value['rating'];

            if ((utf8_strlen($text) < 5) || (utf8_strlen($text) > 1000)) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_text')]));
            }

            if ($rating < 0 || $rating > 5) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_rating')]));
            }

            $oreview[$value['order_product_id']]    = ['rating'=>$rating,'text'=>$text,'code'=>$code];
        }

        if (!empty($oreview) && $pcount == count($oreview)) {
            foreach ($oreview as $key => $value) {
                $this->model_account_oreview->addOreview($key, $value);
            }
        }else{
            return $this->response->setOutput($this->returnData(['msg'=>'fail:oreview_data is error']));
        }

        $notice                         = $this->config->get('config_review_approve') ? t('text_success_unapproved') : t('text_success_approved');
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$notice]));
    }

    //已评价列表
    public function have_oreview()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/oreview');

        $allowKey       = ['api_token','page','limit'];
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

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/oreview');

        $page                       = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit                      = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;

        $filter_data 	            = [
            'filter_customer_id' 	=> $this->customer->getId(),
            'filter_reviewed' 		=> 1,
            'start' 				=> ($page - 1) * $limit,
            'limit' 				=> $limit,
        ];

        $totals                     = $this->model_account_oreview->getOreviewsTotalForApi($filter_data);
        $results                	= $this->model_account_oreview->getOreviewsForApi($filter_data);
        $oreview_list 				= [];

        if (!empty($results))
        {
            $this->load->model('tool/image');
            foreach ($results as $result)
            {
            	$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_height'));

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = '';
                }

                $opt                            = [];
                $productModel                   = \Models\Product::find($product_id);

                if (!empty($productModel)) {
                    $option_data                    = $productModel->getVariantLabels();
                    foreach ($option_data as $okey => $ovalue) {
                        $opt[]      = $ovalue['name'] . ':' . $ovalue['value'];
                    }
                }

                $images 						= [];
                $oreview_img  					= $this->model_account_oreview->getOreviewImages($result['reviewed']);
                foreach ($oreview_img as $value) {
                	if (image_exists($value['filename'])) {
	                    $images[] = $this->url->imageLink($value['filename']);
	                }
                }

                $oreview_list[]     			= [
                    'avatar'                	=> $this->model_tool_image->resize($this->customer->getAvatar(), 100, 100),
                    'author'                	=> $result['author'],
                    'oreview_date_added' 		=> $result['date_added'],
                    'oreview_rating'            => $result['rating'],
                    'oreview_text'              => $result['text'],
                    'oreview_img' 				=> $images,
                    'product_name'              => $result['name'],
                    'product_image' 			=> $image,
                    'product_price'             => $price,
                    'product_option' 			=> implode(',', $opt)
                ];
            }
        }

        $remainder                  = intval($totals - $limit * $page);
        $json['total_page']         = ceil($totals/$limit);
        $json['remainder']          = $remainder >= 0 ? $remainder : 0;
        $json['oreview_list']       = $oreview_list; 
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    //商品评价列表
    public function plist()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/oreview');

        $allowKey       = ['api_token','product_id','page','dtype','rating','limit'];
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

        $this->load->model('account/oreview');
        $this->load->model('catalog/product');

        $product_info           = $this->model_catalog_product->getProduct($req_data['product_id']);
        if (empty($product_info)){
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:product_info is empty']));
        }

        //所有商品ID 子产品和和主产品
        $ppid                               = $product_info['parent_id'] > 0 ? $product_info['parent_id'] : $product_info['product_id'];
        $product_ids                        = $this->model_catalog_product->getProductAllIdByPidOrProductId($ppid);
        $page                               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit                              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;
        $is_image                           = isset($req_data['dtype']) ? (int)$req_data['dtype'] : 1;
        $rating                             = (isset($req_data['rating']) && in_array((int)$req_data['rating'], [1,2,3,4,5])) ? (int)$req_data['rating'] : 0;

        //商品评论
        $this->load->model('account/oreview');
        $this->load->model('tool/image');

        $json['total_all']                  = (int)$this->model_account_oreview->getTotalOreviewsByProductIds($product_ids,0,0);
        $json['total_img']                  = (int)$this->model_account_oreview->getTotalOreviewsByProductIds($product_ids,1,0);
        $json['total_rat1']                 = (int)$this->model_account_oreview->getTotalOreviewsByProductIds($product_ids,0,1);
        $json['total_rat2']                 = (int)$this->model_account_oreview->getTotalOreviewsByProductIds($product_ids,0,2);
        $json['total_rat3']                 = (int)$this->model_account_oreview->getTotalOreviewsByProductIds($product_ids,0,3);
        $json['total_rat4']                 = (int)$this->model_account_oreview->getTotalOreviewsByProductIds($product_ids,0,4);
        $json['total_rat5']                 = (int)$this->model_account_oreview->getTotalOreviewsByProductIds($product_ids,0,5);

        $review_data                        = $this->model_account_oreview->getOreviewsByProductIds($product_ids, $page-1,$limit,$is_image,$rating);

        foreach ($review_data as $key => $value) {
            $result_images      = $this->model_account_oreview->getOreviewImages($value['order_product_review_id']);
            $images             = [];
            foreach ($result_images as $result_image) {
                if (image_exists($result_image['filename'])) {
                    $images[] = $this->url->imageLink($result_image['filename']);
                }
            }

            $review_data[$key]['image']     = $images;
            $review_data[$key]['avatar']    = $this->model_tool_image->resize($value['customer_id'], 100, 100);
        }

        $remainder                  = intval($json['total_all'] - $limit * $page);
        $json['total_page']         = ceil($json['total_all']/$limit);
        $json['remainder']          = $remainder >= 0 ? $remainder : 0;
        $json['review_data']        = $review_data;
        
        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }
}
