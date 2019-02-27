<?php
class ControllerApiCart extends Controller {

	//购物车列表
	public function index() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        $this->load->language('checkout/cart');

		$allowKey       = ['api_token'];
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

        $this->session->data['buy_type'] 		= 0;
    	$this->cart->setCartBuyType($this->session->data['buy_type']);

    	if ($this->cart->hasCartProducts() || !empty($this->session->data['vouchers']) || !empty($this->session->data['recharges'])) 
        {
            /*if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
            	return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_stock')]));
            }*/

            if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
                return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('text_login')]));
            } 

            $this->load->model('tool/image');
            $this->load->model('tool/upload');

            $data['cart_nums'] 		= $this->cart->countProducts();
            $data['currency'] 		= preg_replace('|[0-9\.]+|','',$this->currency->format(100, $this->session->data['currency']));
            $data['products'] 		= [];

            $products 				= $this->cart->getCartProducts();

            foreach ($products as $product) {
                $product_total = 0;

                foreach ($products as $product_2) {
                    if ($product_2['product_id'] == $product['product_id']) {
                        $product_total += $product_2['quantity'];
                    }
                }

                if ($product['minimum'] > $product_total) {
                    return $this->response->setOutput($this->returnData(['msg'=>sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum'])]));
                }

                $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));

                // Display prices
                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

                    $price 	= $this->currency->format($unit_price, $this->session->data['currency']);
                    $oprice = $this->currency->format($this->tax->calculate($product['oprice'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $total 	= $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
                    $nprice = str_replace($data['currency'], '', $total);
                } else {
                    $price 		= false;
                    $oprice 	= false;
                    $total 		= false;
                    $nprice 	= false;
                }

                //处理属性
                $variants       = '';
                if (isset($product['option']) && $product['option']) {
                    foreach ($product['option'] as $okey => $ovalue) {
                        $variants .= $ovalue['name'] . ':' . $ovalue['value'] . ',';
                    }

                    $variants   = trim($variants,',');
                }

                $data['products'][] = array(
                    'product_id'    => (int)$product['product_id'],
                    'cart_id'       => $product['cart_id'],
                    'checked'       => (bool)$product['selected'],
                    'thumb'         => $image,
                    'name'          => $product['name'],
                    'quantity'      => $product['quantity'],
                    'stock'         => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                    'price'         => $price,
                    'nprice'        => $nprice,
                    'oprice'        => $oprice,
                    'variants'      => $variants
                );
            }

            $this->load->view('checkout/cart', $data);

            //格式化一下数组
            $products 			= $this->load->getViewData('products');
            $products           = isset($products['products']) ? $products['products'] : [];
            /*foreach ($products as $key => $value) {
                sort($value);
            	$products[] 	= $value;
            }*/

            sort($products);

            $data['products'] 	= $products;

            $json 		= $this->returnData(['code'=>'200','msg'=>'success','data'=>$data]);
        }
        else{
        	$json       = $this->returnData(['code'=>'200','msg'=>$this->language->get('text_empty')]);
        }

        return $this->response->setOutput($json);
    }

    //添加购物车
	public function add()
	{
		$this->response->addHeader('Content-Type: application/json');
		$this->load->language('checkout/cart');

		$allowKey       = ['api_token','product_id','quantity','sku','buy_type'];
        $req_data       = $this->dataFilter($allowKey);
        $data           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $product_id = isset($req_data['product_id']) ? $req_data['product_id'] : 0;
    	$buy_type 	= isset($req_data['buy_type']) ? (int)$req_data['buy_type'] : 0;

        $this->load->model('catalog/product');

        //检测商品是否隶属于店铺下
        if ($this->model_catalog_product->isSellerProduct($product_id) <= 0 ) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_seller_info')]));
        }

        if (!$this->customer->isLogged() && $buy_type == 1){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }
    	
    	if ( !in_array($buy_type, [0,1]) ) return $this->response->setOutput($this->returnData());

        $sku                    = trim($req_data['sku']);
        if ( substr_count($sku,':') <= 0 || substr_count($sku,'|') != (substr_count($sku,':') + 1)) {
            return $this->response->setOutput($this->returnData(['msg'=>'sku is error']));
        }

        if (isset($this->session->data['coupon'])) {
            unset($this->session->data['coupon']);
        }

        $this->session->data['buy_type']        = $buy_type;
    	$product_info                           = $this->model_catalog_product->getProduct($product_id);

    	if ($product_info) {
    		$quantity      = (isset($req_data['quantity']) && (int)$req_data['quantity'] > 0) ? (int)$req_data['quantity'] : 1;
            $stock         = (isset($product_info['quantity']) && (int)$product_info['quantity'] > 0) ? (int)$req_data['quantity'] : 0;
            if ($stock <= 0 || $quantity > $stock) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('text_no_stock')]));
            }

    		//确定产品SKU 获取真是产品ID
    		$productModel 						= \Models\Product::find($product_id);
			$variants 							= $productModel->getProductVariantsDetail();
			$skus 								= isset($variants['skus']) ? $variants['skus'] : [];
            $sku                                = !empty($req_data['sku']) ? trim($req_data['sku']) : '';

            $is_sku                             = $this->is_sku($sku,$skus);
			if ( empty($sku) || !$is_sku ) {
				return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_product')]));
			}

            if (strpos($is_sku, '&product_id=') === false) {
                $purl                           = explode('-', $is_sku);
                $product_id                     = count($purl) > 0 ? (int)($purl[count($purl) - 1]) : 0;
            }else{
                $product_id                     = (int)substr($is_sku, (strpos($is_sku, '&product_id=')+12 - strlen($is_sku) ) );
            }

            $option 							= isset($req_data['option']) ? array_filter($req_data['option']) : [];

            /*$product_options 					= $this->model_catalog_product->getProductOptions($product_id);
            
            foreach ($product_options as $product_option) {
                if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                	return $this->response->setOutput($this->returnData(['msg'=>sprintf($this->language->get('error_required'), $product_option['name'])]));
                }
            }*/

            $cart_product_count 		= $this->cart->getCartProductCount($product_id);
            $flash_data 				= Flash::getSingleton()->getFlashPriceAndCount($product_id);
            if ($flash_data) {
                if (!$flash_data['checkout']) {
                	return $this->response->setOutput($this->returnData(['msg'=>sprintf($this->language->get('error_flash_out'), $flash_data['count'])]));
                } else {
                    if ($flash_data && $flash_data['count'] && ($quantity + $cart_product_count) > $flash_data['count']) {
                		return $this->response->setOutput($this->returnData(['msg'=>sprintf($this->language->get('error_flash_count'), $flash_data['count'])]));
                    }
                }
            }

            $this->cart->setCartBuyType($buy_type);

            if ($buy_type == 1) $this->cart->clear();

            $cart_id        = $this->cart->add($product_id, $quantity, $option);

            $json['info']   = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $product_id), $product_info['name'], $this->url->link('checkout/cart'));

            // Unset all shipping and payment methods
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);

            $json['cart_id']        = $cart_id;
            $json['total'] 			= $this->formatted_total_text();
            $json['cart_nums'] 		= $this->cart->countProducts();

            $data   = $this->returnData(['code'=>'200','msg'=>'success','data'=>$json]);
        }

        return $this->response->setOutput($data);
	}

    private function is_sku($sku,$skus)
    {
        if (empty($sku) || empty($skus)) return false;

        $sku    = explode('|', trim($sku,'|'));
        
        sort($sku);

        foreach ($skus as $key => $value)
        {
            $psku   = !empty($key) ? explode('|', trim($key,'|')) : [];
            
            sort($psku);

            if (!empty($psku) && $sku === $psku) return $value;
        }

        return false;
    }

	public function update_quantity()
	{
        $this->response->addHeader('Content-Type: application/json');
		$this->load->language('checkout/cart');

		$allowKey       = ['api_token','cart_id','quantity'];
        $req_data       = $this->dataFilter($allowKey);

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }
        
        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!(isset($req_data['cart_id']) && intval($req_data['cart_id']) >=1)) {
        	return $this->response->setOutput($this->returnData(['msg'=>'cart_id is error']));
        }

        if (!(isset($req_data['quantity']) && intval($req_data['quantity']) >=1)) {
        	return $this->response->setOutput($this->returnData(['msg'=>'quantity is error']));
        }

        $this->cart->setCartBuyType((isset($this->session->data['buy_type']) ? $this->session->data['buy_type'] : 0));

        $this->cart->update($req_data['cart_id'], $req_data['quantity']);

        if (!$this->cart->hasStock() /*&& (!config('config_stock_checkout') || config('config_stock_warning'))*/) {
            return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_stock')]));
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'cart update success']));
    }

    public function update_usecoupon()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('checkout/cart');

        $allowKey       = ['api_token','coupon_id','seller_id'];
        $req_data       = $this->dataFilter($allowKey);

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }
        
        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if ($req_data['coupon_id']  == '-1') {
            if (isset($this->session->data['coupon'][$req_data['seller_id']])) {
                $this->session->data['coupon'][$req_data['seller_id']] = [];
            }

            return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'coupon cancle success']));
        }

        if (!(isset($req_data['coupon_id']) && intval($req_data['coupon_id']) >=1)) {
            return $this->response->setOutput($this->returnData(['msg'=>'coupon_id is error']));
        }

        /*if (!(isset($req_data['seller_id']) && intval($req_data['seller_id']) >=0)) {
            return $this->response->setOutput($this->returnData(['msg'=>'seller_id is error']));
        }*/

        $this->cart->setCartBuyType((isset($this->session->data['buy_type']) ? $this->session->data['buy_type'] : 0));

        //校验优惠券是否存在 存在获取使用码
        $this->load->model('extension/total/coupon');
        
        $coupon_id = $this->model_extension_total_coupon->getCouponCodeByIdAndSellerId($req_data['coupon_id'],$req_data['seller_id']);
        if ($coupon_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'no coupon']));
        }

        if (isset($this->session->data['coupon'][$req_data['seller_id']])) {
            unset($this->session->data['coupon'][$req_data['seller_id']]);
        }

        $coupon_info = $this->load->controller('extension/total/coupon/useCouponForApi',$coupon_id);
        if (isset($coupon_info['success']) && $coupon_info['success'] === 'success') {
            return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'coupon use success']));
        }else{
            return $this->response->setOutput($this->returnData(['msg'=>$coupon_info['error']]));
        }
    }

	public function edit()
	{
		$this->load->language('api/cart');

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->cart->update($this->request->post['key'], $this->request->post['quantity']);

			$json['success'] = $this->language->get('text_success');

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('api/cart');

        $allowKey       = ['api_token','cart_id'];
        $req_data       = $this->dataFilter($allowKey);

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }
        
        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!(isset($req_data['cart_id']) && intval($req_data['cart_id']) >=1)) {
            return $this->response->setOutput($this->returnData(['msg'=>'cart_id is error']));
        }
		
        $this->cart->remove($req_data['cart_id']);

        unset($this->session->data['vouchers'][$req_data['cart_id']]);
        unset($this->session->data['recharges'][$req_data['cart_id']]);
        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_methods']);
        unset($this->session->data['reward']);
        unset($this->session->data['credit']);

        if (!$this->cart->hasStock() && (!config('config_stock_checkout') || config('config_stock_warning'))) {
            return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_stock')]));
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'cart update success']));
	}

	public function products() {
		$this->load->language('api/cart');

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
			// Stock
			if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
				$json['error']['stock'] = $this->language->get('error_stock');
			}

			// Products
			$json['products'] = array();

			$products = $this->cart->getProducts();

			foreach ($products as $product) {
				$product_total = 0;

				foreach ($products as $product_2) {
					if ($product_2['product_id'] == $product['product_id']) {
						$product_total += $product_2['quantity'];
					}
				}

				if ($product['minimum'] > $product_total) {
					$json['error']['minimum'][] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
				}

				$option_data = $variantData = array();

				foreach ($product['option'] as $option) {
                    if ($option['type'] == 'variant') {
                        $variantData[] = array(
                            'product_variant_id' => $option['product_variant_id'],
                            'variant_id' => $option['variant_id'],
                            'variant_value_id' => $option['variant_value_id'],
                            'name' => $option['name'],
                            'value' => $option['value'],
                            'type' => $option['type']
                        );
                        continue;
                    }
					$option_data[] = array(
						'product_option_id'       => $option['product_option_id'],
						'product_option_value_id' => $option['product_option_value_id'],
						'name'                    => $option['name'],
						'value'                   => $option['value'],
						'type'                    => $option['type']
					);
				}

				$json['products'][] = array(
					'cart_id'    => $product['cart_id'],
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'model'      => $product['model'],
					'option'     => $option_data,
					'variant'    => $variantData,
					'quantity'   => $product['quantity'],
					'stock'      => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
					'shipping'   => $product['shipping'],
					'price'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
					'total'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']),
					'reward'     => $product['reward']
				);
			}

			// Voucher
			$json['vouchers'] = array();

			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $key => $voucher) {
					$json['vouchers'][] = array(
						'code'             => $voucher['code'],
						'description'      => $voucher['description'],
						'from_name'        => $voucher['from_name'],
						'from_email'       => $voucher['from_email'],
						'to_name'          => $voucher['to_name'],
						'to_email'         => $voucher['to_email'],
						'voucher_theme_id' => $voucher['voucher_theme_id'],
						'message'          => $voucher['message'],
						'price'            => $this->currency->format($voucher['amount'], $this->session->data['currency']),			
						'amount'           => $voucher['amount']
					);
				}
			}

			// Totals
			$this->load->model('setting/extension');

			$totals = array();
			$taxes = $this->cart->getTaxes();
			$total = 0;

			// Because __call can not keep var references so we put them into an array. 
			$total_data = array(
				'totals' => &$totals,
				'taxes'  => &$taxes,
				'total'  => &$total
			);
			
			$sort_order = array();

			$results = $this->model_setting_extension->getExtensions('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get('total_' . $result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);
					
					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
				}
			}

			$sort_order = array();

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);

			$json['totals'] = array();

			foreach ($totals as $total) {
				$json['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
				);
			}
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


	private function formatted_total_text()
    {
        list($total, $totals) = $this->getTotalsValue();
        $vouchers 		= isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0;
        $recharges 		= isset($this->session->data['recharges']) ? count($this->session->data['recharges']) : 0;
        if (config('is_mobile')) {
            return $this->currency->format($total, $this->session->data['currency']);
        }

        return sprintf(t('text_items'), $this->cart->countProducts() + $vouchers + $recharges, $this->currency->format($total, $this->session->data['currency']));
    }

    private function getTotalsValue()
    {
        $this->load->model('setting/extension');

        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );

        // Display prices
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $sort_order = array();

            $results = $this->model_setting_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $sort_order = array();

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);
        }

        // $total = 总金额, $totals = 费用明细
        return array($total, $totals);
    }

    public function get_cart_count()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('checkout/cart');

        $allowKey       = ['api_token'];
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

        $this->cart->setCartBuyType(0);

        $data['cart_nums']      = $this->cart->countProducts();

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
    }
}
