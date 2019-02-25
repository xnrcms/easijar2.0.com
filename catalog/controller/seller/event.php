<?php
class ControllerSellerEvent extends Controller {
	public function viewCommonHeaderBefore(&$route, &$data, &$template) {
	    if(!$this->config->get('module_multiseller_status')) {
	        return;
        }

        $data['seller_account'] = $this->url->link('seller/account');
        $data['seller_product'] = $this->url->link('seller/product');
        $data['seller_order'] = $this->url->link('seller/order');
        $data['seller_register'] = $this->url->link('seller/register');
        $data['seller_login'] = $this->url->link('seller/login');
        $this->load->language('seller/account', 'seller');
        $data['text_seller_register'] = $this->language->get('seller')->get('text_seller_register');
        $data['text_seller_login'] = $this->language->get('seller')->get('text_seller_login');
        $data['text_seller_account'] = $this->language->get('seller')->get('heading_title');
        $data['text_seller_product'] = $this->language->get('seller')->get('text_product');
        $data['text_seller_order'] = $this->language->get('seller')->get('text_order');
	}

	public function viewCheckoutCartBefore(&$route, &$data, &$template) {
	    if(!$this->config->get('module_multiseller_status')) {
	        return;
        }

        $this->load->model('multiseller/seller');

	    $products = array();

	    $cart_products = $this->cart->getCartProducts();
        foreach ($data['products'] as $key => $product) {
            foreach ($cart_products as $cart) {
                if ($cart['cart_id'] == $product['cart_id']) {
                    $seller_info        = $this->model_multiseller_seller->getSellerByProductId($cart['product_id']);
                    $seller_id          = $seller_info ? $seller_info['seller_id'] : 0;

                    if (isset($products[$seller_id])) {
                        $products[$seller_id]['checked']        = ($products[$seller_id]['checked']) ? true : (bool)(isset($product['checked']) ? $product['checked'] : 0);
                        $products[$seller_id]['products'][]     = $product;
                    } else {
                        $store_name             = $seller_info ? $seller_info['store_name'] : $this->config->get('config_name');
                        $products[$seller_id]   = [
                            'store_id'      => $seller_info ? $seller_id : 0,
                            'store_name'    => htmlspecialchars_decode($store_name),
                            'shipping'      => 0,
                            'checked'       => (bool)(isset($product['checked']) ? $product['checked'] : 0),
                            'products'      => array($product),
                        ];
                    }
                }
            }
	    }
	    $data['products'] = $products;
	}

	public function viewCheckoutCheckoutConfirmBefore(&$route, &$data, &$template) {
	    if(!$this->config->get('module_multiseller_status')) {
	        return;
        }

        $this->load->model('multiseller/seller');

		$this->load->model('tool/image');
		$products = array();
        
		foreach ($this->cart->getProducts() as $product) {
			$image = $this->model_tool_image->resize($product['image'] ?: 'placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));

			$option_data = array();
			foreach ($product['option'] as $option) {
				if ($option['type'] != 'file') {
					$value = $option['value'];
				} else {
					$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
					if ($upload_info) {
						$value = $upload_info['name'];
					} else {
						$value = '';
					}
				}

				$option_data[] = array(
					'name'  => $option['name'],
					'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
				);
			}

            $seller_info        = $this->model_multiseller_seller->getSellerByProductId($product['product_id']);
            $seller_id          = $seller_info ? $seller_info['seller_id'] : 0;
            $tax_class_id       = $product['tax_class_id'];

			$product = array(
				'cart_id'    => $product['cart_id'],
				'product_id' => $product['product_id'],
				'image'      => $image,
				'name'       => $product['name'],
				'model'      => $product['model'],
                'sku'        => $product['sku'],
				'option'     => $option_data,
				'quantity'   => $product['quantity'],
				'subtract'   => $product['subtract'],
				'price'      => $this->currency->format($this->tax->calculate($product['price'], $tax_class_id, $this->config->get('config_tax')), $this->session->data['currency']),
				'total'      => $this->currency->format($this->tax->calculate($product['price'], $tax_class_id, $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']),
                'oprice'=>$product['price'],
                'ototal'=>$product['total'],
				'href'       => $this->url->link('product/product', 'product_id=' . $product['product_id'])
			);
			if (!isset($products[$seller_id])) {
                /*$coupons                 = $this->load->controller('extension/total/coupon/getCouponForApi',['seller_id'=>$seller_id]);
                $coupon                  = [];

                foreach ($coupons as $key => $value) {
                    $coupon[]            = [
                        'coupon_id' =>$value['coupon_id'],
                        'name'      =>$value['name'],
                        'discount'  =>$this->currency->format($this->tax->calculate($value['discount'], $tax_class_id, $this->config->get('config_tax')), $this->session->data['currency']),
                    ];
                }*/

                $store_name             = $seller_info ? $seller_info['store_name'] : $this->config->get('config_name');
			    $products[$seller_id]   = array(
                    'seller_id'         =>$seller_info['seller_id'],
			        'store_name'        => htmlspecialchars_decode($store_name),
                    //'coupons'           => $coupon,
                    'products'          => array($product)
                );
            } else {
			    $products[$seller_id]['products'][] = $product;
            }
		}

	    $data['products'] = $products;
	}

	public function modelCheckoutOrderAddOrderAfter(&$route, &$args, &$output) {
        $data = $args[0];
        $order_id = $output;
	    $this->load->model('multiseller/checkout');
	    $this->model_multiseller_checkout->addOrderSeller($order_id);
    }

    // oc原来的AddOrderHistory执行时必然同步更新所有子订单历史
	public function modelCheckoutOrderAddOrderHistoryBefore(&$route, &$args) {
	    // 如果不需要更新子订单的时候会传递该参数$args[5]为true
        $not_update_suborder = isset($args[5]) ? $args[5] : false;
        if ($not_update_suborder) {
            return;
        }

        $order_id = $args[0];
        $order_status_id = $args[1];
        $comment = isset($args[2]) ? $args[2] : '';
        $notify = isset($args[3]) ? $args[3] : false;
	    $this->load->model('multiseller/checkout');
	    $sellers = $this->model_multiseller_checkout->getOrderSellers($order_id);

	    foreach ($sellers as $seller) {
	        // 如果有平台商品则seller_id为NULL，所以强制转换为int值0，平台商品对应子订单的seller_id为0
            // 最后一个参数代表是否不更新主订单，如为true表示不更新主订单，因更新主订单历史而更新子订单的时候不应再更新主订单，否则会导致死循环。
	        $this->model_multiseller_checkout->addSubOrderHistory($order_id, (int)$seller['seller_id'], $order_status_id, $comment, $notify, true);
        }
    }

	public function viewProductProductBefore(&$route, &$data, &$template) {
	    if(!$this->config->get('module_multiseller_status')) {
	        return;
        }

        $this->load->language('multiseller/seller');

	    $this->load->model('multiseller/seller');

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

	    $seller_info = $this->model_multiseller_seller->getSellerByProductId($product_id);

		if($seller_info && (int)$seller_info['seller_id']) {
            $this->load->model('tool/image');
            $this->load->model('localisation/country');
            $this->load->model('localisation/zone');

            $this->load->language('seller/common', 'seller');
            $data['text_store_name'] = $this->language->get('seller')->get('text_store_name');
            $data['text_store_address'] = $this->language->get('seller')->get('text_store_address');

            $country = $this->model_localisation_country->getCountry($seller_info['country_id']);
            $zone = $this->model_localisation_zone->getZone($seller_info['zone_id']);

            $data['store_name']     = htmlspecialchars_decode($seller_info['store_name']);
            $data['store_url']      = $this->url->link('multiseller/home', 'seller_id=' . $seller_info['seller_id']);
            $data['store_address']  = $country ? $country['name'] : '' . ' ' . $zone ? $zone['name'] : '';
            $data['store_avatar']   = $this->model_tool_image->resize($seller_info['avatar'], 100, 100);
        }
	}

	public function viewAccountOrderInfoBefore(&$route, &$data, &$template) {
	    if(!$this->config->get('module_multiseller_status')) {
	        return;
        }

        $this->load->model('multiseller/seller');
        $this->load->model('multiseller/order');
        $this->load->model('localisation/order_status');

        $this->load->language('seller/common', 'seller');

	    $products = array();

        foreach ($data['products'] as $key => $product) {
            $seller_info = $this->model_multiseller_seller->getSellerByProductId($product['product_id']);
            $seller_id = $seller_info ? $seller_info['seller_id'] : 0;
            if (isset($products[$seller_id])) {
                $products[$seller_id]['products'][] = $product;
            } else {
                $suborder_info = $this->model_multiseller_order->getSuborder((int)$data['order_id'], $seller_id);

                $order_status   = $this->model_localisation_order_status->getOrderStatus($suborder_info['order_status_id']);
                $store_name     = $seller_info ? $seller_info['store_name'] : $this->config->get('config_name');
                $products[$seller_id] = array(
                    'store_name'   => htmlspecialchars_decode($store_name),
                    'products'     => array($product),
                    'order_stauts' => $order_status['name']
                );
            }
	    }
	    $data['products'] = $products;

        $data['sellers'] = array();
        $data['sellers'][] = array(
            'seller_id'   => -1,
            'name'        => $this->language->get('seller')->get('text_whole_order')
        );

        $sellers            = $this->model_multiseller_order->getOrderSellers((int)$this->request->get['order_id']);
        $store_name         = $seller_info ? $seller_info['store_name'] : $this->config->get('config_name');

        foreach ($sellers as $seller) {
            $data['sellers'][] = array(
            'seller_id'   => $seller['seller_id'] ? (int)$seller['seller_id'] : 0,
            'name'        => htmlspecialchars_decode($store_name)
            );
        }
        $data['entry_seller'] = $this->language->get('seller')->get('entry_seller');
    }

	public function viewAccountReturnFormBefore(&$route, &$data, &$template) {
	    if(!$this->config->get('module_multiseller_status')) {
	        return;
        }

        $this->load->model('multiseller/order');
        $this->load->model('checkout/order');
        $this->load->language('seller/common', 'seller');

        if (isset($this->request->get['order_id']) && isset($this->request->get['product_id']))
        {
            $order_products = $this->model_checkout_order->getOrderProducts($this->request->get['order_id']);
            $seller_id      = 0;
            foreach ($order_products as $order_product) {
                if ($order_product['product_id'] == $this->request->get['product_id']) {
                    $seller_id = $this->model_multiseller_order->getSellerIdByOrderProductId($order_product['order_product_id']) ?: 0;
                }
            }

            $data['action'] = ''; // 为空时action自动取url，这是的action会自动携带所有url里的参数，就不会出现product_id参数不存在的问题了
            $data['seller_id'] = $seller_id;
        }
    }

	public function modelAccountReturnAddReturnAfter(&$route, &$args, &$output) {
        $data = $args[0];
        $return_id = $output;
		$this->db->query("UPDATE `" . DB_PREFIX . "return` SET seller_id = '" . (int)$data['seller_id'] . "' WHERE return_id = '" . (int)$return_id . "'");
    }
}
