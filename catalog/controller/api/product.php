<?php
class ControllerApiProduct extends Controller {
	public function detail()
	{
		$this->response->addHeader('Content-Type: application/json');

		$allowKey		= ['api_token','product_id'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }
		
		if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }
        
		$this->load->model('catalog/product');

		$product_info 			= $this->model_catalog_product->getProduct($req_data['product_id']);

		if (!empty($product_info))
		{

			$pinfo 					= [];
			$pinfo['name'] 			= $product_info['name'];

			$price 					= $product_info['price'];
			$pinfo['price'] 		= $this->currency->format($this->tax->calculate($price, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

			$oprice 				= !empty($product_info['special']) ? $product_info['special'] :  $product_info['price'];
			$pinfo['oprice'] 		= $this->currency->format($this->tax->calculate($oprice, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

			$pinfo['freight'] 		= '10';
			$pinfo['description'] 	= $product_info['description'];

			//折扣率计算
			$pinfo['discount'] 		= ($price >= $oprice) ? round(($price - $oprice)/$price, 4)*100 : 0;
			$pinfo['free_shipping'] = '包邮';

			//产品属性
			$opt 								= [];
			$productModel 						= \Models\Product::find($product_info['product_id']);
			$variants 							= $productModel->getProductVariantsDetail();

			if ($variants) {
				$opt['variants'] 				= $variants['variants'];
				$opt['sku'] 					= $productModel->getVariantKeys();
				/*$opt['product_variants'] 		= $variants['product_variants'];
				$opt['skus'] 					= $variants['skus'];*/
			}

			//所有商品ID 子产品和和主产品
			$ppid 								= $product_info['parent_id'] > 0 ? $product_info['parent_id'] : $product_info['product_id'];
			$product_ids 						= $this->model_catalog_product->getProductAllIdByPidOrProductId($ppid);

			//商品评论
			$this->load->model('account/oreview');
			$oreview 							= [];
			$review['total'] 					= $this->model_account_oreview->getTotalOreviewsByProductIds($product_ids);
			$review_data 						= $this->model_account_oreview->getOreviewsByProductIds($product_ids, 0, 1);

			$this->load->model('account/customer');
	        foreach ($review_data as $result) 
	        {
	            $result_images 		= $this->model_account_oreview->getOreviewImages($result['order_product_review_id']);
	            $images 			= [];
	            foreach ($result_images as $result_image) {
	                if (image_exists($result_image['filename'])) {
	                    $images[] = $this->url->imageLink($result_image['filename']);
	                }
	            }

	            //购买的商品属性
	            $option_data 	 = [];
	            $options 	     = '';
	            if ($variantData = Models\Order\Product::find((int) $result['order_product_id'])->getVariantLabels()) {
	                    $option_data = array_merge($option_data, $variantData);
	                    foreach ($option_data as $key => $value) {
	                    	$options 	.= $value['name'] . ':' . $value['value'] . ',';
	                    }
	            }

	            $customer_info = $this->model_account_customer->getCustomer($result['customer_id']);
	            $customer_name = $customer_info ? $customer_info['fullname'] : '';
	            $review['reviews'][] = array(
	                'author'     => $result['author'] ? $result['author'] : $customer_name,
	                'text'       => nl2br($result['text']),
	                'images'     => $images,
	                'rating'     => (int) $result['rating'],
	                'avatar'	 =>image_resize($this->customer->getAvatar($result['customer_id'])),
	                'option_data' =>trim($options,','),
	                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
	            );
	        }

	        //商家信息
	        $this->load->model('multiseller/seller');

	        $seller 				= $this->model_multiseller_seller->getSellerByProductIdForOne($product_info['product_id']);
	        $seller_info 			= [];
	        if (!empty($seller)) {
	        	$seller_info['seller_id'] 		= $seller['seller_id'];
	        	$seller_info['avatar'] 			= $seller['avatar'];
	        	$seller_info['store_name'] 		= $seller['store_name'];
	        	$seller_info['product_total'] 	= $seller['total'];
	        	$seller_info['rating'] 			= sprintf("%.1f", $seller['rating']);
	        }

			//商品图片
			$product_image 			= $this->model_catalog_product->getProductImages($product_info['product_id']);
			$images 				= [];
			if (!empty($product_image)) {

				$this->load->model('tool/image');

				foreach ($product_image as $result) {
					$images[] = [
						'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'))/*,
						'preview' => $this->model_tool_image->resize($result['image'], $preview_width, $preview_height),
						'popup' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'))*/
					];
				}
			}

			//商家店铺优惠券
			$this->load->model('marketing/coupon');
			$coupons 		= [];

			$filter_data 	= [
				'sort'  => 'discount',
				'order' => 'DESC',
				'start' => 0,
				'limit' => 4,
				'date' => 1
			];

			$seller_id 		= isset($seller_info['seller_id']) ? (int)$seller_info['seller_id'] : 0;
			$results 		= $this->model_marketing_coupon->getCoupons($filter_data,$seller_id);

			foreach ($results as $result) {
				$coupons[] 		 = [
					'discount'   => $this->currency->format($this->tax->calculate($result['discount'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
				];
			}

			$data['images'] 					= $images;
			$data['pinfo'] 						= $pinfo;//购物车数量
        	$data['cart_nums'] 					= $this->cart->countProducts();
			$data['reviews'] 					= $review;
	        $data['seller_info'] 				= $seller_info;
	        $data['variants'] 					= $opt;
	        $data['free_shipping'] 				= '10';
	        $data['coupons'] 					= $coupons;

			$json 								= $this->returnData(['code'=>'200','msg'=>'success','data'=>$data]);
		}

		return $this->response->setOutput($json);
	}
}