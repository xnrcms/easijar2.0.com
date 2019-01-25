<?php
class ControllerApiProduct extends Controller {

	//商品列表
	public function index() 
	{
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('product/category');

		$allowKey		= ['api_token','page','limit','path','sorts','variant','price','in_stock'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
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
		$sortsArr 			= ['p.sort_order-DESC','pd.name-ASC','pd.name-DESC','p.price-ASC','p.price-DESC','rating-ASC','rating-DESC'];

		$filter 			= isset($req_data['filter']) ? (string)$req_data['filter'] : '';
		$attr 				= isset($req_data['attr']) ? (string)$req_data['attr'] : '';

		$options 			= isset($req_data['option']) ? parse_filters($req_data['option']) : '';
		$variant 			= isset($req_data['variant']) ? parse_filters($req_data['variant']) : '';
		$filterPrices 		= isset($req_data['price']) ? parse_filters($req_data['price']) : '';

		$sorts 				= isset($req_data['sorts']) ? (int)$req_data['sorts'] : 0;
		$sorts 				= isset($sortsArr[$sorts]) ? $sortsArr[$sorts] : $sortsArr[0];
		$sorts 				= explode('-', $sorts);
		$sort 				= $sorts[0];
		$order 				= $sorts[1];

        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
		$limit 				= (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;

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

			if (!empty($filterPrices)) {
				$minPrice  = isset($filterPrices[0]) ? (float)$filterPrices[0] : 0;
				$maxPrice  = isset($filterPrices[1]) ? (float)$filterPrices[1] : 1000000;
				$minPrice  = $this->currency->convert($minPrice,$this->session->data['currency'],'CNY');
				$maxPrice  = $this->currency->convert($maxPrice,$this->session->data['currency'],'CNY');
				
				$filterPrices 			= [$minPrice,$maxPrice];

				sort($filterPrices);
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
					'rating' 	=> $product['rating'],
					'rating_num'=> $product['reviews'],
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
		$this->load->language('product/product');

		$allowKey		= ['api_token','product_id'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }
		
		if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }
        
		$this->load->model('catalog/product');
		$this->load->model('catalog/product_pro');

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

			$shuoming = '<p><br><img style="display: block; margin-left: auto; margin-right: auto;" src="../image/catalog/shuoming.png" alt="undefined" data-mce-src="../image/catalog/shuoming.png" data-mce-style="display: block; margin-left: auto; margin-right: auto;" data-mce-selected="1"></p>';

			$searchStr 				= ['../image/catalog/'];
			$replaceStr 			= [trim(base_url(),'/') . '/image/catalog/'];
			$subjectStr 			= htmlspecialchars_decode($product_info['description'] . $shuoming);

			$pinfo['description'] 	= str_replace($searchStr, $replaceStr,$subjectStr);
			$pinfo['sku'] 			= '';

			//折扣率计算
			$pinfo['discount'] 		= ($price >= $oprice) ? round(($price - $oprice)/$price)*100 : 0;

			//产品属性
			$opt 								= [];
			$productModel 						= \Models\Product::find($product_info['product_id']);
			$variants 							= $productModel->getProductVariantsDetail();

			if ($variants) {
				$variants_list 					= [];
				$product_variants 				= isset($variants['product_variants']) &&!empty($variants['product_variants']) ? $variants['product_variants'] : [];
				
				$skus 							= isset($variants['skus']) ? $variants['skus'] : [];
				$skusArr 						= [];
				$skus_prodoct_ids 				= [];

				foreach ($skus as $skey => $svalue)
				{
					$skusArr[$skey] 			= $skey;

					if (strpos($svalue, '&product_id=') === false) {
		                $purl             		= explode('-', $svalue);
		                $spid             		= count($purl) > 0 ? (int)($purl[count($purl) - 1]) : 0;
		            }else{
		                $spid             		= (int)substr($svalue, (strpos($svalue, '&product_id=')+12 - strlen($svalue) ) );
		            }

					$skus_prodoct_ids[$skey] 	= $spid;
				}

				sort($skusArr);

				$sku_products 					= $this->model_catalog_product_pro->getProductsStockByIds($skus_prodoct_ids);
				$sku_stocks 					= [];
				if (!empty($sku_products)) {
					foreach ($sku_products as $skey => $svlaue) {
						$sku_stocks[$svlaue['product_id']] 			= $svlaue['quantity'];
					}
				}

				$skusProductStock 		= [];
				foreach ($skus_prodoct_ids as $spkey => $spvalue) {
					$skusProductStock[] = ['sku'=>$spkey,'stock'=>(isset($sku_stocks[$spvalue])) ? (int)$sku_stocks[$spvalue] : 0];
				}

				$skusString 					= !empty($skusArr) ? str_replace('|||', '|', implode('|', $skusArr)) : '';

				if (isset($variants['variants']) &&!empty($variants['variants'])) {
					foreach ($variants['variants'] as $kvar=>$vari) {
						if (isset($vari['values']) &&!empty($vari['values'])) {
							foreach ($vari['values'] as $kv => $vv) {
								if (isset($product_variants[$kvar]) && (int)$product_variants[$kvar] === (int)$vv['variant_value_id'] ) {
									$vari['values'][$kv]['selected'] 	= 0;//前端处理 修改为了0
								}else{
									$vari['values'][$kv]['selected'] 	= 0;
								}

								//对应的sku是否已经下架
								$ssk 									= '|' . (int)$vari['variant_id'] . ':' . (int)$vv['variant_value_id'] . '|';
								$vari['values'][$kv]['sku_status'] 		= strpos($skusString, $ssk) === false ? 0 : 1;
							}
						}
						$opt[] 		= $vari;
					}
				}

				$pinfo['sku'] 					= '';//$productModel->getVariantKeys();
				$pinfo['skus'] 					= $skusProductStock;
				
				/*$opt['variants'] 				= $variants['variants'];
				$opt['sku'] 					= $productModel->getVariantKeys();*/
				/*$opt['product_variants'] 		= $variants['product_variants'];*/
			}

			//所有商品ID 子产品和和主产品
			$ppid 								= $product_info['parent_id'] > 0 ? $product_info['parent_id'] : $product_info['product_id'];
			$product_ids 						= $this->model_catalog_product->getProductAllIdByPidOrProductId($ppid);

			$this->load->model('tool/image');

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
	        	$seller_info['avatar'] 			= $this->model_tool_image->resize($seller['avatar'], 100, 100);
	        	$seller_info['store_name'] 		= htmlspecialchars_decode($seller['store_name']);
	        	$seller_info['product_total'] 	= $this->model_multiseller_seller->getTotalSellerProducts($seller['seller_id']);
	        	$seller_info['rating'] 			= sprintf("%.1f", $seller['rating']);
	        	$seller_info['chats'] 			= '98.5%';
	        }
	        
			//商品图片
			$product_image 			= $this->model_catalog_product->getProductImages($product_info['product_id'],5);
			$images 				= [];
			if (!empty($product_image)) {

				foreach ($product_image as $result) {
					$images[] = [
						'thumb' => $this->model_tool_image->resize($result['image'], 600, 600)
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
	        $address['zone_id']          		= 0;
			$cost 								= $this->model_extension_total_multiseller_shipping->getProductsCost($seller_id,$address);
			$pinfo['freight'] 					= !empty($cost) ? $this->currency->format($cost, $this->session->data['currency']) : t('text_freight');

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
		}else{
            return $this->response->setOutput($this->returnData(['msg'=>'fail:product info is error']));
		}

		return $this->response->setOutput($json);
	}

	public function search()
	{
		$time 	= time();
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('product/search');

		$allowKey		= ['api_token','page','limit','search','path','sorts','variant','price','in_stock'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/product_pro');
		$this->load->model('tool/image');

		//0:默认排序，1：名称 A - Z 2:名称 Z - A 3:价格 低 - 高 4：价格 高 - 低 5：评级 低 - 高 6：评级 高 - 低 7：型号 A - Z 8：型号 Z - A
		$sortsArr 			= ['p.sort_order-DESC','pd.name-ASC','pd.name-DESC','p.price-ASC','p.price-DESC','rating-ASC','rating-DESC'];

		$search 			= isset($req_data['search']) ? (string)$req_data['search'] : '';
		$variant 			= isset($req_data['variant']) ? parse_filters($req_data['variant']) : '';
		$tag 				= isset($req_data['tag']) ? (string)$req_data['tag'] : '';

		$description 		= isset($req_data['description']) ? (string)$req_data['description'] : '';
		$description 		= urlencode(html_entity_decode($description, ENT_QUOTES, 'UTF-8'));

		$category_id 		= 0;
		$sub_category 		= false;

		if (isset($req_data['path']) && !empty($req_data['path'])) {
			$parts 				= explode('_', (string)$req_data['path']);
			$category_id 		= (int)array_pop($parts);
			$category_info 		= $this->model_catalog_category->getCategory($category_id);
			$sub_category 		= $this->config->get('config_product_category') ? true : false;
		}

		$brandIds 			= isset($req_data['brand']) ? parse_filters($req_data['brand']) : '';

		$attr 				= isset($req_data['attr']) ? (string)$req_data['attr'] : '';
		$attr 				= urlencode(html_entity_decode($attr, ENT_QUOTES, 'UTF-8'));

		$options 			= isset($req_data['option']) ? parse_filters($req_data['option']) : '';
		$stockStatusIds 	= isset($req_data['status']) ? parse_filters($req_data['status']) : '';
		$filterPrices 		= isset($req_data['price']) ? parse_filters($req_data['price']) : '';

		$sorts 				= isset($req_data['sorts']) ? (int)$req_data['sorts'] : 0;
		$sorts 				= isset($sortsArr[$sorts]) ? $sortsArr[$sorts] : $sortsArr[0];
		$sorts 				= explode('-', $sorts);
		$sort 				= $sorts[0];
		$order 				= $sorts[1];

		$page 				= (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
		$limit 				= (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;

		unset($this->request->post['in_stock']);
			
		$this->load->model('catalog/category');

		// 3 Level Category Search
		//$data['categories'] = $this->model_catalog_category->getThreeLevelCategories();
		
		$products 				= [];
		$product_total 			= 0;

		if (!empty($filterPrices)) {
			$minPrice  = isset($filterPrices[0]) ? (float)$filterPrices[0] : 0;
			$maxPrice  = isset($filterPrices[1]) ? (float)$filterPrices[1] : 1000000;
			$minPrice  = $this->currency->convert($minPrice,$this->session->data['currency'],'CNY');
			$maxPrice  = $this->currency->convert($maxPrice,$this->session->data['currency'],'CNY');
			
			$filterPrices 			= [$minPrice,$maxPrice];

			sort($filterPrices);
		}

		$filter_data = array(
			'filter_name'         		=> $search,
			'filter_tag'          		=> $tag,
			'filter_description'  		=> $description,
			'filter_category_id'  		=> $category_id,
			'filter_sub_category' 		=> $sub_category,
            'filter_brand_ids'    		=> $brandIds,
			'filter_attributes'   		=> $attr,
			'parent_id'   				=> 0,
			'filter_stock_status_ids'  	=> $stockStatusIds,
			'filter_variant_value_ids' 	=> $variant,
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
				'rating' 	=> $product['rating'],
				'rating_num'=> $product['reviews'],
			];
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
				if ((int)$value['variant_id'] == 1 && isset($value['variants']) && !empty($value['variants'])) {
					foreach ($value['variants'] as $kk => $vv) {
						if ($kk >= 100){
							unset($value['variants'][$kk]);continue;
						}
					}
				}

				$filter['variants'][] 	= $value;
			}
		}

		$filter['price_range'] 	= isset($filter_data['price_range']) ? $filter_data['price_range'] : [];

		return $filter;
	}

	public function recommend()
	{
		$this->response->addHeader('Content-Type: application/json');

		$allowKey		= ['api_token','page','limit'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        $page 						= (isset($req_data['page']) && (int)$req_data['page'] > 0) ? (int)$req_data['page'] : 1;
		$limit 						= (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;
		$start 						= $limit * ($page-1);

	    $this->load->model('setting/module');
	    $this->load->model('tool/image');

		//推荐商品
		$module_id 					= 37;
	    $setting_info 				= $this->model_setting_module->getModule($module_id);
		$setting_info['module_id'] 	= $module_id;
		$setting_info['position'] 	= 'content_top';
		$setting_info['api'] 		= true;
		$setting_info['limit'] 		= $limit;
		$setting_info['start'] 		= $start;
	    $results 					= $this->load->controller('extension/module/featured', $setting_info,true);

	    $product_total 				= isset($results['product_total']) ? (int)$results['product_total'] : 0;
	    $remainder 					= intval($product_total - $limit * $page);
	    $recommend 					= [];

	    if (isset($results['products']) && !empty($results['products'])) {
	    	foreach ($results['products'] as $rval) {
	    		$recommend[] 		= [
	    			'product_id' 	=> $rval['product_id'],
	    			'name' 			=> $rval['name'],
	    			'thumb' 		=> $rval['thumb'],
	    			'price' 		=> $rval['price'],
	    			'rating' 		=> $rval['rating'],
	    			'reviews' 		=> $rval['reviews'],
	    		];
	    	}
	    }

	    $data 					= [];
		$data['total_page'] 	= (int)(ceil($product_total/$limit));
		$data['remainder'] 		= $remainder >= 0 ? $remainder : 0;
		$data['lists'] 			= $recommend;

		return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
	}

	public function discount()
	{
		$this->response->addHeader('Content-Type: application/json');

		$allowKey		= ['api_token','page','limit'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        $page 						= (isset($req_data['page']) && (int)$req_data['page'] > 0) ? (int)$req_data['page'] : 1;
		$limit 						= (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 1;
		$start 						= $limit * ($page-1);

	    $this->load->model('setting/module');
	    $this->load->model('tool/image');

		$module_id 					= 36;
	    $setting_info 				= $this->model_setting_module->getModule($module_id);
		$setting_info['module_id'] 	= $module_id;
		$setting_info['position'] 	= 'content_top';
		$setting_info['api'] 		= true;
		$setting_info['limit'] 		= $limit;
		$setting_info['start'] 		= $start;
	    $results 					= $this->load->controller('extension/module/special', $setting_info);

	    $product_total 				= isset($results['product_total']) ? (int)$results['product_total'] : 0;
	    $remainder 					= intval($product_total - $limit * $page);
	    $discount 					= [];

	    if (isset($results['products']) && !empty($results['products'])) {
	    	foreach ($results['products'] as $rval) {
	    		$discount[] 		= [
	    			'product_id' 	=> $rval['product_id'],
	    			'name' 			=> $rval['name'],
	    			'thumb' 		=> $rval['thumb'],
	    			'price' 		=> $rval['price'],
	    			'special' 		=> !empty($rval['special']) ? $rval['special'] : $rval['price'],
	    			'discount' 		=> $rval['discount'],
	    		];
	    	}
	    }

	    $data 					= [];
		$data['total_page'] 	= ceil($product_total/$limit);
		$data['remainder'] 		= $remainder >= 0 ? $remainder : 0;
		$data['lists'] 			= $discount;

		return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
	}
}