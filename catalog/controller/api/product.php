<?php
class ControllerApiProduct extends Controller {

	//商品列表
	public function index() 
	{
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('product/category');

		$allowKey		= ['api_token','page','path','sorts','variant','price','in_stock'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }
		
		if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/product_pro');
		$this->load->model('tool/image');


		//0:默认排序，1：名称 A - Z 2:名称 Z - A 3:价格 低 - 高 4：价格 高 - 低 5：评级 低 - 高 6：评级 高 - 低 7：型号 A - Z 8：型号 Z - A
		$sortsArr 			= ['p.sort_order','pd.name-ASC','pd.name-DESC','p.price-ASC','p.price-DESC','rating-ASC','rating-DESC'];

		$filter 			= isset($req_data['filter']) ? (string)$req_data['filter'] : '';
		$filter 			= urlencode(html_entity_decode($filter, ENT_QUOTES, 'UTF-8'));

		$attr 				= isset($req_data['attr']) ? (string)$req_data['attr'] : '';
		$attr 				= urlencode(html_entity_decode($attr, ENT_QUOTES, 'UTF-8'));

		$options 			= isset($req_data['option']) ? parse_filters($req_data['option']) : '';
		$variant 			= isset($req_data['variant']) ? parse_filters($req_data['variant']) : '';
		$filterPrices 		= isset($req_data['price']) ? parse_filters($req_data['price']) : '';

		$sort 				= isset($req_data['sort']) ? (int)$req_data['sort'] : 0;
		$sort 				= isset($sortsArr[$sort]) ? $sortsArr[$sort] : $sortsArr[0];

		$order 				= isset($req_data['order']) ? (string)$req_data['order'] : 'ASC';

		$page 				= isset($req_data['page']) ? (int)$req_data['page'] : 1;
		$limit 				= isset($req_data['limit']) ? (int)$req_data['limit'] : $this->config->get('theme_'.$this->config->get('config_theme').'_product_limit');

		if (isset($req_data['in_stock']) && (int)$req_data['in_stock'] > 0) {
			$inStock = (int)array_get($req_data, 'in_stock');
		}

		$category_id 		= 0;

		if (isset($req_data['path']) && !empty($req_data['path'])) {
			$parts 			= explode('_', (string)$req_data['path']);
			$category_id 	= (int)array_pop($parts);
		}

		$category_info 		= $this->model_catalog_category->getCategory($category_id);

		if ($category_info)
		{
			$data['categories'] 	= [];

			$results 				= $this->model_catalog_category->getCategories($category_id);

            $categoryIds = array();
            foreach ($results as $result) {
                $categoryIds[] = $result['category_id'];
            }
            $totals 				= $this->model_catalog_product_pro->getTotalProductsFromAllCategories();

			foreach ($results as $result) {
				$total = array_get($totals, $result['category_id'], 0);
				$data['categories'][] = array(
					'name' 	=> $result['name'] . ($this->config->get('config_product_count') ? ' (' . $total . ')' : ''),
					'thumb' => $this->model_tool_image->resize($result['image']),
					'path' 	=> $req_data['path'] . '_' . $result['category_id']
				);
			}

			//$data['products'] = array();
			$filter_data = array(
				'filter_category_id'  		=> $category_id,
				'filter_sub_category' 		=> $this->config->get('config_product_category') ? true : false,
				'filter_filter'       		=> $filter,
				'filter_attributes'   		=> $attr,
				'parent_id' 				=> 0,
				'filter_option_value_ids'  	=> $options,
				'filter_variant_value_ids' 	=> $variant,
				'filter_price'        		=> $filterPrices,
				'sort'                		=> $sort,
				'order'               		=> $order,
				'start'               		=> ($page - 1) * $limit,
				'limit'               		=> $limit
			);

			if (isset($inStock)) {
				$filter_data['filter_in_stock'] = $inStock;
			}else{
				unset($this->request->post['in_stock']);
			}

			$product_total 					= $this->model_catalog_product_pro->getTotalProducts($filter_data);
			$results 						= $this->model_catalog_product_pro->getProducts($filter_data);

			foreach ($results as $result) {
				$product 	= $this->model_catalog_product->handleSingleProduct($result, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));

				$products[] 		= [
					'product_id'=> $product['product_id'],
					'name'		=> $product['name'],
					'price'		=> $product['price'],
					'special'	=> !empty($product['special']) ? $product['special'] : '',
					'discount' 	=> $product['discount'],
					'image'	 	=> $product['thumb'],
					'rating' 	=> 5,
					'rating_num'=> 10,
				];
			}

			$remainder 					= intval($product_total - $limit * $page);
			$data  						= [];
			$data['sorts'] 				= $this->get_sorts();
			$data['filter'] 			= $this->get_filter();
			$data['total_page'] 		= ceil($product_total/$limit);
			$data['remainder'] 			= $remainder >= 0 ? $remainder : 0;
 			$data['products'] 			= $products;

			return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
		}

		$this->response->setOutput($this->returnData());
	}

	//商品详情
	public function detail()
	{
		$this->response->addHeader('Content-Type: application/json');

		$allowKey		= ['api_token','product_id'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
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

			$pinfo['stock'] 		= isset($product_info['quantity']) ? (int)$product_info['quantity'] : 0;
			$pinfo['description'] 	= htmlspecialchars_decode($product_info['description']);
			$pinfo['sku'] 			= '';

			//折扣率计算
			$pinfo['discount'] 		= ($price >= $oprice) ? round(($price - $oprice)/$price, 4)*100 : 0;

			//产品属性
			$opt 								= [];
			$productModel 						= \Models\Product::find($product_info['product_id']);
			$variants 							= $productModel->getProductVariantsDetail();

			if ($variants) {
				$variants_list 					= [];
				$product_variants 				= isset($variants['product_variants']) &&!empty($variants['product_variants']) ? $variants['product_variants'] : [];
				
				if (isset($variants['variants']) &&!empty($variants['variants'])) {
					foreach ($variants['variants'] as $kvar=>$vari) {
						if (isset($vari['values']) &&!empty($vari['values'])) {
							foreach ($vari['values'] as $kv => $vv) {
								if (isset($product_variants[$kvar]) && (int)$product_variants[$kvar] === (int)$vv['variant_value_id'] ) {
									$vari['values'][$kv]['selected'] 	= 1;
								}else{
									$vari['values'][$kv]['selected'] 	= 0;
								}
							}
						}
						$opt[] 		= $vari;
					}
				}

				$pinfo['sku'] 					= $productModel->getVariantKeys();
				
				/*$opt['variants'] 				= $variants['variants'];
				$opt['sku'] 					= $productModel->getVariantKeys();*/
				/*$opt['product_variants'] 		= $variants['product_variants'];
				$opt['skus'] 					= $variants['skus'];*/
			}

			//所有商品ID 子产品和和主产品
			$ppid 								= $product_info['parent_id'] > 0 ? $product_info['parent_id'] : $product_info['product_id'];
			$product_ids 						= $this->model_catalog_product->getProductAllIdByPidOrProductId($ppid);

			//商品评论
			$this->load->model('account/oreview');
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
	        	$seller_info['chats'] 			= '80%';
	        }

			//商品图片
			$product_image 			= $this->model_catalog_product->getProductImages($product_info['product_id']);
			$images 				= [];
			if (!empty($product_image)) {

				$this->load->model('tool/image');

				foreach ($product_image as $result) {
					$images[] = [
						'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'))
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

			//是否被收藏
			$wish_total     = 0;
			if (!$this->customer->isLogged()){
				if (isset($this->session->data['wishlist']) && in_array($product_info['product_id'], $this->session->data['wishlist'])){
                	$wish_total     = 1;
				}
	        }else{
				$this->load->model('account/wishlist');
                $wish_total     = $this->model_account_wishlist->getIsWishFByProductId((int)$product_info['product_id']);
			}

			//获取商品运费
			$this->load->model('extension/total/multiseller_shipping');

			$address 							= [];
	        $address['country_id']          	= isset($this->session->data['country_code']) ? get_country_code($this->session->data['country_code']) : 0;
	        $address['weight']              	= isset($product_info['weight']) ? $product_info['weight'] : 0;
	        $address['weight_class_id']     	= isset($product_info['weight_class_id']) ? $product_info['weight_class_id'] : 0;
	        $address['length']              	= isset($product_info['length']) ? $product_info['length'] : 0;
	        $address['width']               	= isset($product_info['width']) ? $product_info['width'] : 0;
	        $address['height']              	= isset($product_info['height']) ? $product_info['height'] : 0;
	        $address['length_class_id']     	= isset($product_info['length_class_id']) ? $product_info['length_class_id'] : 0;
			$cost 								= $this->model_extension_total_multiseller_shipping->getShippingCostByAddress($seller_id,$address);
			$pinfo['freight'] 					= !empty($cost) ? $this->currency->format($cost, $this->session->data['currency']) : '包邮';

			$data['images'] 					= $images;
			$data['pinfo'] 						= $pinfo;//购物车数量
        	$data['cart_nums'] 					= $this->cart->countProducts();
			$data['reviews'] 					= $review;
	        $data['seller_info'] 				= $seller_info;
	        $data['variants'] 					= $opt;
	        $data['is_wish'] 					= (int)$wish_total;
	        $data['coupons'] 					= $coupons;
			
			//添加商品详情浏览记录
			$this->load->controller('api/browse_records/addProductBrowseRecords',(int)$product_info['product_id']);
			
			$json 								= $this->returnData(['code'=>'200','msg'=>'success','data'=>$data]);
		}

		return $this->response->setOutput($json);
	}

	public function search()
	{
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('product/search');

		$allowKey		= ['api_token','page','search','sorts','variant','price','in_stock'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/product_pro');
		$this->load->model('tool/image');

		//0:默认排序，1：名称 A - Z 2:名称 Z - A 3:价格 低 - 高 4：价格 高 - 低 5：评级 低 - 高 6：评级 高 - 低 7：型号 A - Z 8：型号 Z - A
		$sortsArr 			= ['p.sort_order','pd.name-ASC','pd.name-DESC','p.price-ASC','p.price-DESC','rating-ASC','rating-DESC'];

		$search 			= isset($req_data['search']) ? (string)$req_data['search'] : '';
		$search 			= urlencode(html_entity_decode($search, ENT_QUOTES, 'UTF-8'));

		$tag 				= isset($req_data['tag']) ? (string)$req_data['tag'] : (isset($req_data['search']) ? (string)$req_data['search'] : '');
		$tag 				= urlencode(html_entity_decode($tag, ENT_QUOTES, 'UTF-8'));

		$description 		= isset($req_data['description']) ? (string)$req_data['description'] : '';
		$description 		= urlencode(html_entity_decode($description, ENT_QUOTES, 'UTF-8'));

		$category_id 		= isset($req_data['category_id']) ? (int)$req_data['category_id'] : 0;
		$sub_category 		= isset($req_data['sub_category']) ? (int)$req_data['sub_category'] : 0;

		$brandIds 			= isset($req_data['brand']) ? parse_filters($req_data['brand']) : '';

		$attr 				= isset($req_data['attr']) ? (string)$req_data['attr'] : '';
		$attr 				= urlencode(html_entity_decode($attr, ENT_QUOTES, 'UTF-8'));

		$options 			= isset($req_data['option']) ? parse_filters($req_data['option']) : '';
		$stockStatusIds 	= isset($req_data['status']) ? parse_filters($req_data['status']) : '';
		$filterPrices 		= isset($req_data['price']) ? parse_filters($req_data['price']) : '';

		$sort 				= isset($req_data['sort']) ? (int)$req_data['sort'] : 0;
		$sort 				= isset($sortsArr[$sort]) ? $sortsArr[$sort] : $sortsArr[0];

		$order 				= isset($req_data['order']) ? (string)$req_data['order'] : 'ASC';

		$page 				= isset($req_data['page']) ? (int)$req_data['page'] : 1;
		$limit 				= isset($req_data['limit']) ? (int)$req_data['limit'] : $this->config->get('theme_'.$this->config->get('config_theme').'_product_limit');


		unset($this->request->post['in_stock']);
			
		$this->load->model('catalog/category');

		// 3 Level Category Search
		//$data['categories'] = $this->model_catalog_category->getThreeLevelCategories();
		
		$products 				= [];
		$product_total 			= 0;

		if (isset($req_data['search']) || isset($req_data['tag']))
		{
			$filter_data = array(
				'filter_name'         		=> $search,
				'filter_tag'          		=> $tag,
				'filter_description'  		=> $description,
				'filter_category_id'  		=> $category_id,
				'filter_sub_category' 		=> $sub_category,
                'filter_brand_ids'    		=> $brandIds,
				'filter_attributes'   		=> $attr,
				'parent_id'   				=> 0,
				'filter_option_value_ids'  	=> $options,
				'filter_stock_status_ids'  	=> $stockStatusIds,
				'filter_price'        		=> $filterPrices,
				'sort'                		=> $sort,
				'order'               		=> $order,
				'start'               		=> ($page - 1) * $limit,
				'limit'               		=> $limit
			);

			$product_total 					= $this->model_catalog_product_pro->getTotalProducts($filter_data);
			$results 						= $this->model_catalog_product_pro->getProducts($filter_data);

			foreach ($results as $result) {
				$product 	= $this->model_catalog_product->handleSingleProduct($result, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));

				$products[] 		= [
					'product_id'=> $product['product_id'],
					'name'		=> $product['name'],
					'price'		=> $product['price'],
					'special'	=> !empty($product['special']) ? $product['special'] : '',
					'discount' 	=> $product['discount'],
					'image'	 	=> $product['thumb'],
					'rating' 	=> 5,
					'rating_num'=> 10,
				];
			}
		}

		$remainder 					= intval($product_total - $limit * $page);
		$data['sorts'] 				= $this->get_sorts();
		$data['filter'] 			= $this->get_filter();
		$data['total_page'] 		= ceil($product_total/$limit);
		$data['remainder'] 			= $remainder >= 0 ? $remainder : 0;
		$data['products'] 			= $products;

		return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
	}

	private function get_sorts()
	{
		//排序定义
		$sorts 			 = [
			['keys'=>0,'name'=>$this->language->get('text_default')],
			['keys'=>1,'name'=>$this->language->get('text_name_asc')],
			['keys'=>2,'name'=>$this->language->get('text_name_desc')],
			['keys'=>3,'name'=>$this->language->get('text_price_asc')],
			['keys'=>4,'name'=>$this->language->get('text_price_desc')],
			['keys'=>5,'name'=>$this->language->get('text_rating_asc')],
			['keys'=>6,'name'=>$this->language->get('text_rating_desc')],
		];

		return $sorts;
	}

	private function get_filter()
	{
		$filter_data 	= $this->load->controller('extension/module/multi_filter/getFilterForApi');
		$filter 		= [];
		$variants 		= isset($filter_data['variants']) ? $filter_data['variants'] : [];

		if (!empty($variants)) {
			foreach ($variants as $key => $value) {
				$filter['variants'][] 	= $value;
			}
		}

		$filter['price_range'] 	= isset($filter_data['price_range']) ? $filter_data['price_range'] : [];

		return $filter;
	}
}