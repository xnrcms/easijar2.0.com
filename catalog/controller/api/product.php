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
		
		if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/product_pro');
		$this->load->model('tool/image');

		$page 			= (int)$req_data['page'] > 0 ? (int)$req_data['page'] : 1;
		$limit 			= $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		$category_id 	= 0;
		$sortnum 		= (int)$req_data['sorts'];
		
		switch ($sortnum) {
			case 0:
				$sort 	= 'p.sort_order';
				$order 	= 'ASC';
				break;
			case 1:
				$sort 	= 'p.price';
				$order 	= 'DESC';
				break;
			case 2:
				$sort 	= 'p.price';
				$order 	= 'ASC';
				break;
			default:return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:sorts is error']));break;
		}

		if (isset($req_data['filter'])) {
			$filter = $req_data['filter'];
		} else {
			$filter = '';
		}

		if (isset($req_data['attr'])) {
			$attr = parse_attributes($req_data['attr']);
		} else {
			$attr = '';
		}

		if (isset($req_data['option'])) {
			$options = parse_ilters($req_data['option']);
		} else {
			$options = '';
		}

		if (isset($req_data['variant'])) {
			$variants = parse_filters($req_data['variant']);
		} else {
			$variants = '';
		}

		if (isset($req_data['in_stock']) && (int)$req_data['in_stock'] > 0) {
			$inStock = array_get($req_data, 'in_stock');
		}

		if (isset($req_data['price'])) {
			$filterPrices = parse_filters($req_data['price']);
		} else {
			$filterPrices = '';
		}


		if (isset($req_data['limit'])) {
			$limit 			= (int)$this->request->get['limit'];
		}

		if (isset($req_data['path']) && !empty($req_data['path'])) {
			$parts 			= explode('_', (string)$req_data['path']);
			$category_id 	= (int)array_pop($parts);
		}

		$category_info = $this->model_catalog_category->getCategory($category_id);

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
				'filter_variant_value_ids' 	=> $variants,
				'filter_price'        		=> $filterPrices,
				'sort'                		=> $sort,
				'order'               		=> $order,
				'start'               		=> ($page - 1) * $limit,
				'limit'               		=> $limit
			);

			if (isset($inStock)) {
				$filter_data['filter_in_stock'] = $inStock;
			}

			$pdata 			= [];
			$results 		= $this->model_catalog_product_pro->getProductsForApi($filter_data);

			foreach ($results as $value) {

				$price_c 		= 0;
				$special_c 		= 0;

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price') && !empty($value['price'])) {
                    $price = $this->currency->format($this->tax->calculate($value['price'], $value['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $price_c 		= (float)$value['price'];
					$special_c 		= $price_c;
                } else {
                    $price = '';
                }

                if ((float)$value['special'] && !empty($value['special'])) {
                    $special = $this->currency->format($this->tax->calculate($value['special'], $value['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $special_c 	= (float)$value['special'];
                } else {
                    $special = '';
                }

                $discount 		= ($price_c >= $special_c) ? round(($price_c - $special_c)/$price_c, 4)*100 : 0;

                $image = $this->model_tool_image->resize($value['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_height'));

				$pdata[] 		= [
					'product_id'=> $value['product_id'],
					'name'		=> $value['name'],
					'price'		=> $price,
					'special'	=> $special,
					'discount' 	=> $discount,
					'image'	 	=> $image,
					'rating' 	=> 5,
					'rating_num'=> 10,

				];
			}

			$data  			= [];
			$data['pdata'] 	= $pdata;


			$search_data 	= [];
			$filter 		= $this->load->controller('extension/module/multi_filter/getFilterForApi');

			$variants 		= isset($filter['variants']) ? $filter['variants'] : [];
			if (!empty($variants)) {
				foreach ($variants as $key => $value) {
					$search_data['variants'][] 	= $value;
				}
			}

			$search_data['price_range'] 	= isset($filter['price_range']) ? $filter['price_range'] : [];


			$data['filter'] = $search_data;

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

			$pinfo['stock'] 		= '10';
			$pinfo['freight'] 		= '10';
			$pinfo['description'] 	= htmlspecialchars_decode($product_info['description']);

			//折扣率计算
			$pinfo['discount'] 		= ($price >= $oprice) ? round(($price - $oprice)/$price, 4)*100 : 0;
			$pinfo['free_shipping'] = '包邮';

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

			$data['images'] 					= $images;
			$data['pinfo'] 						= $pinfo;//购物车数量
        	$data['cart_nums'] 					= $this->cart->countProducts();
			$data['reviews'] 					= $review;
	        $data['seller_info'] 				= $seller_info;
	        $data['variants'] 					= $opt;
	        //$data['free_shipping'] 				= '10';
	        $data['coupons'] 					= $coupons;
			
			//添加商品详情浏览记录
			$this->load->controller('api/browse_records/addProductBrowseRecords',(int)$product_info['product_id']);
			
			$json 								= $this->returnData(['code'=>'200','msg'=>'success','data'=>$data]);
		}

		return $this->response->setOutput($json);
	}
}