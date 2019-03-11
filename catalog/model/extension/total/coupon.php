<?php
class ModelExtensionTotalCoupon extends Model {
	public function getCoupon2($coupon_id,$total=0){
		$status = true;
		$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_customer2` WHERE coupon_id = '" . (int)$coupon_id . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND status < uses_limit AND (date_end = '0000-00-00' OR date_end > NOW()))");
		if ($coupon_query->num_rows) {
			/*if ($coupon_query->row['order_total'] > $this->cart->getSellerTotal($coupon_query->row['seller_id'])) {
				$status = false;
			}*/

			$coupon_total = $this->getTotalCouponHistoriesByCouponId($coupon_id);

			if ($coupon_query->row['uses_limit'] > 0 && ($coupon_total >= $coupon_query->row['uses_limit'])) {
				$status = false;
			}
		}else{
			$status = false;
		}

		if ($status) {
			return array(
				'coupon_id'     => $coupon_query->row['coupon_id'],
				'seller_id'     => isset($coupon_query->row['seller_id']) ? (int)$coupon_query->row['seller_id'] : 0,
				'code'          => $coupon_query->row['code'],
				'name'          => $coupon_query->row['name'],
				'type'          => $coupon_query->row['type'],
				'discount'      => $coupon_query->row['discount'],
				'date_start'    => $coupon_query->row['date_start'],
				'date_end'      => $coupon_query->row['date_end'],
				'status'        => $coupon_query->row['status'],
				'date_added'    => $coupon_query->row['date_added']
			);
		}
	}
	public function getCoupon($coupon_id,$total=0) {
		$status = true;

		$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_customer2` WHERE coupon_id = '" . (int)$coupon_id . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW()))");

		if ($coupon_query->num_rows) {
			if ($coupon_query->row['order_total'] > $this->cart->getSellerTotal($coupon_query->row['seller_id'])) {
				$status = false;
			}

			$coupon_total = $this->getTotalCouponHistoriesByCouponId($coupon_id);

			if ($coupon_query->row['uses_limit'] > 0 && ($coupon_total >= $coupon_query->row['uses_limit'])) {
				$status = false;
			}

			/*if ($coupon_query->row['logged'] && !$this->customer->getId()) {
				$status = false;
			}*/

			if ($this->customer->getId()) {
				$customer_total = $this->getTotalCouponHistoriesByCustomerId($code, $this->customer->getId());
				
				if ($coupon_query->row['uses_customer'] > 0 && ($customer_total >= $coupon_query->row['uses_customer'])) {
					$status = false;
				}
			}

			// Products
			$coupon_product_data = array();

			$coupon_product_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_product` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

			foreach ($coupon_product_query->rows as $product) {
				$coupon_product_data[] = $product['product_id'];
			}

			// Categories
			$coupon_category_data = array();

			$coupon_category_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_category` cc LEFT JOIN `" . DB_PREFIX . "category_path` cp ON (cc.category_id = cp.path_id) WHERE cc.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

			foreach ($coupon_category_query->rows as $category) {
				$coupon_category_data[] = $category['category_id'];
			}

			$product_data = array();

            // Customer
            $coupon_customer_data = array();
            // Customer Group
            $coupon_customer_group_data = array();

            if ($coupon_query->num_rows && isset($coupon_query->row['coupon_id'])) {
                // Customers
                $coupon_customer_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_customer` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

                foreach ($coupon_customer_query->rows as $customer) {
                    $coupon_customer_data[] = $customer['customer_id'];
                }

                $coupon_customer_group_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_customer_group` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

                foreach ($coupon_customer_group_query->rows as $customer_group) {
                    $coupon_customer_group_data[] = $customer_group['customer_group_id'];
                }
            }
            if ($coupon_product_data || $coupon_category_data || $coupon_customer_data || $coupon_customer_group_data) {
				foreach ($this->cart->getProducts() as $product) {
					if (in_array($product['product_id'], $coupon_product_data)) {
						$product_data[] = $product['product_id'];

						continue;
					}

					foreach ($coupon_category_data as $category_id) {
						$coupon_category_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . (int)$product['product_id'] . "' AND category_id = '" . (int)$category_id . "'");

						if ($coupon_category_query->row['total']) {
							$product_data[] = $product['product_id'];

							continue;
						}
					}
				}

                if ($coupon_customer_data || $coupon_customer_group_data) {

                    if ($coupon_query->row['logged'] && !$this->customer->getId()) {
                        $status = false;
                    }else{
                        $status = false;
                        $customer_group_id = $this->customer->getGroupId();
                        $customer_id = $this->customer->getId();

                        if(empty($customer_id) || in_array($customer_id, $coupon_customer_data)){
                            $status = true;
                        }
                        if(empty($customer_group_id) || in_array($customer_group_id, $coupon_customer_group_data)){
                            $status = true;
                        }

                        if ($coupon_query->row['total'] > $this->cart->getSubTotal()) {
                            $status = false;
                        }

                        $coupon_history_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch WHERE ch.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

                        if ($coupon_query->row['uses_total'] > 0 && ($coupon_history_query->row['total'] >= $coupon_query->row['uses_total'])) {
                            $status = false;
                        }

                        if ($this->customer->getId()) {
                            $coupon_history_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch WHERE ch.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "' AND ch.customer_id = '" . (int)$this->customer->getId() . "'");

                            if ($coupon_query->row['uses_customer'] > 0 && ($coupon_history_query->row['total'] >= $coupon_query->row['uses_customer'])) {
                                $status = false;
                            }
                        }
                    }
                    $ecchecked = $status;
                }

                if (!$product_data && ($coupon_product_data || $coupon_category_data) && ($coupon_customer_data || $coupon_customer_group_data)) {
                    $status = false;
                } elseif (!$product_data && !$coupon_customer_data && !$coupon_customer_group_data) {
					$status = false;
				}
			}
		} else {
			$status = false;
		}

        if($status && isset($ecchecked)){
            $status = $ecchecked;
        }

		if ($status) {
			return array(
                'eccoupon'     => isset($ecchecked)?true:false,
				'coupon_id'     => $coupon_query->row['coupon_id'],
				'seller_id'     => isset($coupon_query->row['seller_id']) ? (int)$coupon_query->row['seller_id'] : 0,
				'code'          => $coupon_query->row['code'],
				'name'          => $coupon_query->row['name'],
				'type'          => $coupon_query->row['type'],
				'discount'      => $coupon_query->row['discount'],
				'shipping'      => $coupon_query->row['shipping'],
				'total'         => $coupon_query->row['total'],
				'product'       => $product_data,
				'date_start'    => $coupon_query->row['date_start'],
				'date_end'      => $coupon_query->row['date_end'],
				'uses_total'    => $coupon_query->row['uses_total'],
				'uses_customer' => $coupon_query->row['uses_customer'],
				'status'        => $coupon_query->row['status'],
				'date_added'    => $coupon_query->row['date_added']
			);
		}
	}

	public function getTotal($total)
	{
		foreach ($this->cart->getProducts() as $product) {
			if ($this->checkProuctIsUseCoupon($product['product_id'])) return [];
		}
		//找出每个商家对应的运费
		$tts 			= isset($total['totals']) ? $total['totals'] : [];
		$seller_ship    = [];
        $ship_ototal    = [];

		foreach ($tts as $tk => $tv) {
            if (strpos('&#'.$tv['title'], '平台商品运费') >= 1 || strpos('&#'.$tv['title'], 'Platform shipping fee') >= 1) {
                unset($tts[$tk]);continue;
            }

            $tt                             = explode('&#', $tv['title']);
            if (count($tt) == 3 && (int)$tt[1] > 0 ) {
                $ship_ototal[(int)$tt[1]]      = $tv['value'];
            }
        }

        $this->load->model('multiseller/seller');

        //找出每个商家对应的商品总额
		$seller_price 	= [];
	    foreach ($this->cart->getProducts() as $product) {

            $seller_info 	= $this->model_multiseller_seller->getSeller($product['seller_id']);
            $seller_id 		= $seller_info ? $seller_info['seller_id'] : 0;
            if ($seller_id == 0) {
                continue;
            }
	        if (isset($seller_price[$seller_id])) {
	            $seller_price[$seller_id]['price'] 			+= $product['total'];
	            $seller_price[$seller_id]['product_id'][] 	= $product['product_id'];
            } else {
	            $seller_price[$seller_id]['price'] 			= $product['total'];
	            $seller_price[$seller_id]['product_id'][] 	= $product['product_id'];

	            $store_name 							= $seller_info ? $seller_info['store_name'] : $this->config->get('config_name');
                $seller_price[$seller_id]['seller_name']= htmlspecialchars_decode($store_name);
            }
        }

        //计算每个商家加上运费的总额
        foreach ($seller_price as $key => $value) {
        	$seller_price[$key]['price2'] 	= $value['price'] + (isset($ship_ototal[$key]) ? $ship_ototal[$key] : 0);
        }

        $select_coupons 	= $this->autoCoupon($total,$seller_price);

		if (isset($this->session->data['coupon']))
		{
			$title 			= '';  	//优惠券total标题
		    $amount_coupon 	= 0;  	//优惠券金额

            $this->load->model('multiseller/seller');
            $this->load->language('extension/total/coupon', 'coupon');

            foreach ($seller_price as $key => $value) {
            	if (isset($this->session->data['coupon'][$key]) && !empty($this->session->data['coupon'][$key])) {
            		$seller_coupon 		= $this->getShellerCoupon($this->session->data['coupon'][$key],$value,$total);
            		$title 				= sprintf($this->language->get('coupon')->get('text_coupon'), $this->session->data['coupon'][$key]['coupon_id']);
            		if ($seller_coupon) {
	            		$amount_coupon += $seller_coupon;
	            		$total['totals'][] = array(
		                    'seller_id'  => $key,
                    		'total_id' 	 => $this->session->data['coupon'][$key]['coupon_id'],
		                    'code'       => 'multiseller_coupon',
		                    'title'      => $value['seller_name'] . '&#'.$key.'multiseller_coupon&#' . $title,
		                    'value'      => $seller_coupon,
							'sort_order' => $this->config->get('total_coupon_sort_order')
		                );
            		}
            	}
            }

            if ($amount_coupon > 0) {
            	$total['total'] -= $amount_coupon;
            }

            $coupon_key 		= -1;

            if (isset($select_coupons[0]) && !empty($select_coupons[0])) {
            	foreach ($select_coupons[0] as $sckey => $scvalue) {
            		if ($scvalue['order_total'] >= $total['total']) {
            			unset($select_coupons[0][$sckey]);
            		}else{
            			if (isset($this->session->data['coupon'][0]) && !empty($this->session->data['coupon'][0])) {
            				$selected_coupon_id 	= $this->session->data['coupon'][0]['coupon_id'];
            				if ($scvalue['coupon_id'] === $selected_coupon_id) {
            					$coupon_key 	= $sckey;
            				}
            			}
            		}
            	}
            }

            //不适用平台优惠券
            if (isset($this->session->data['coupon'][0]) && empty($this->session->data['coupon'][0]))
            {
            	return $select_coupons;
            }

            if ($coupon_key < 0 && isset($select_coupons[0]) && !empty($select_coupons[0])) {
            	foreach ($select_coupons[0] as $sckey => $scvalue) {
            		$coupon_key 	= $sckey;break;
            	}
            }

            if (isset($select_coupons[0][$coupon_key]) && !empty($select_coupons[0][$coupon_key])) {
            	$platform_coupon 	= $select_coupons[0][$coupon_key];
            	$discount 			= $platform_coupon['discount'];

				if ($platform_coupon['type'] == 2) {
					$discount = $total['total'] / 100 * $platform_coupon['discount'];
				}

				$title 				= sprintf($this->language->get('coupon')->get('text_coupon'), $select_coupons[0][$coupon_key]['coupon_id']);

            	$total['total'] 					-= $discount;
            	$total['totals'][] = [
                    'seller_id'  => 0,
                    'total_id' 	 => $platform_coupon['coupon_id'],
                    'code'       => 'multiseller_coupon',
                    'title'      => 'Platform&#0multiseller_coupon&#' . $title,
                    'value'      => $discount,
					'sort_order' => $this->config->get('total_coupon_sort_order')
                ];

	            $this->session->data['coupon'][0] 	= $select_coupons[0][$coupon_key];
            }

            return $select_coupons;
		}
	}

    private function autoCoupon($totals,$seller_price)
    {
        //获取可用优惠券
        $filter_data        = [
            'customer_id'   => $this->customer->getId(),
            'dtype'         => 0,
            'sort'          => 'c.discount',
            'order'         => 'DESC',
            'start'         => 0,
            'limit'         => 200,
            'status'		=> 1,
        ];

        $this->load->model('customercoupon/coupon');

        $results            = $this->model_customercoupon_coupon->getCouponsByCustomerIdForApi($filter_data);
        $coupon             = [];

        foreach ($results as $key => $value)
        {
            if ($value['seller_id'] > 0 && $value['type'] == 1 && isset($seller_price[$value['seller_id']]) && ($seller_price[$value['seller_id']]['price2'] <= $value['order_total'] || $seller_price[$value['seller_id']]['price2'] <= $value['discount'])) {
                unset($results[$key]);
                continue;
            }

            $coupon[$value['seller_id']][]      = [
                'coupon_id'     => $value['coupon_id'],
                'name'          => $value['name'],
                'discount'      => $value['discount'],
                'type'          => $value['type'],
                'order_total'   => $value['order_total'],
                'launch_scene'  => $value['launch_scene'],
            ];
        }

        foreach ($seller_price as $spkey => $spvalue)
        {
        	if (isset($this->session->data['coupon'][$spkey]) /*&& !empty($this->session->data['coupon'][$spkey])*/) {
        		continue;
        	}
        	
            if (isset($coupon[$spkey][0])) {
                $this->session->data['coupon'][$spkey] = $coupon[$spkey][0];
            }else{
                $this->session->data['coupon'][$spkey] = [];
            }
        }

        return $coupon;
    }

	private function getShellerCoupon($coupon_info = [],$products = [],$total)
	{
		if ($coupon_info && isset($products['price'])) 
		{
			$discount_total = 0;
			$sub_total 		= isset($products['price']) ? $products['price'] : 0;

			if ($coupon_info['type'] == 1) {
				$coupon_info['discount'] = min($coupon_info['discount'], $sub_total);
			}

			foreach ($this->cart->getProducts() as $product) {

				if (!(isset($products['product_id']) && in_array($product['product_id'], $products['product_id']))) {
					continue;
				}

				$discount 	= 0;
				$status 	= true;

				/*if (!$coupon_info['product']) {
					$status = true;
				} else {
					$status = in_array($product['product_id'], $coupon_info['product']);
				}*/

				if ($status) {
					if ($coupon_info['type'] == 1) {
						$discount = $coupon_info['discount'] * ($product['total'] / $sub_total);
					} elseif ($coupon_info['type'] == 2) {
						$discount = $product['total'] / 100 * $coupon_info['discount'];
					}

					if ($product['tax_class_id']) {
						$tax_rates = $this->tax->getRates($product['total'] - ($product['total'] - $discount), $product['tax_class_id']);

						foreach ($tax_rates as $tax_rate) {
							if ($tax_rate['type'] == 2) {
								$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
							}
						}
					}
				}

				$discount_total += $discount;
			}

			/*if ($coupon_info['shipping'] && isset($this->session->data['shipping_method'])) {
				if (!empty($this->session->data['shipping_method']['tax_class_id'])) {
					$tax_rates = $this->tax->getRates($this->session->data['shipping_method']['cost'], $this->session->data['shipping_method']['tax_class_id']);

					foreach ($tax_rates as $tax_rate) {
						if ($tax_rate['type'] == 2) {
							$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
						}
					}
				}

				$discount_total += $this->session->data['shipping_method']['cost'];
			}*/

			// If discount greater than total
			if ($discount_total > $total['total']) {
				$discount_total = $total['total'];
			}

			return $discount_total;
			/*if ($discount_total > 0) {
				$total['totals'][] = array(
					'code'       => 'coupon',
					'title'      => sprintf($this->language->get('coupon')->get('text_coupon'), $this->session->data['coupon']),
					'value'      => -$discount_total,
					'sort_order' => $this->config->get('total_coupon_sort_order')
				);

				$total['total'] -= $discount_total;
			}*/
		}
	}

	public function confirm($order_info, $order_total) {
		$code = '';

		$start = strpos($order_total['title'], '(') + 1;
		$end = strrpos($order_total['title'], ')');

		if ($start && $end) {
			$code = substr($order_total['title'], $start, $end - $start);
		}

		if ($code) {
			$status = true;
			
			$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND status = '1'");

			if ($coupon_query->num_rows) {
				$coupon_total = $this->getTotalCouponHistoriesByCoupon($code);
	
				if ($coupon_query->row['uses_total'] > 0 && ($coupon_total >= $coupon_query->row['uses_total'])) {
					$status = false;
				}
				
				if ($order_info['customer_id']) {
					$customer_total = $this->getTotalCouponHistoriesByCustomerId($code, $order_info['customer_id']);
					
					if ($coupon_query->row['uses_customer'] > 0 && ($customer_total >= $coupon_query->row['uses_customer'])) {
						$status = false;
					}
				}
			} else {
				$status = false;	
			}

			if ($status) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon_history` SET coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "', order_id = '" . (int)$order_info['order_id'] . "', customer_id = '" . (int)$order_info['customer_id'] . "', amount = '" . (float)$order_total['value'] . "', date_added = NOW()");
			} else {
				return $this->config->get('config_fraud_status_id');
			}
		}
	}

	public function unconfirm($order_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon_history` WHERE order_id = '" . (int)$order_id . "'");
	}
	
	public function getTotalCouponHistoriesByCouponId($coupon_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` WHERE coupon_id = '" . (int)$coupon_id . "' AND customer_id = '" . $this->customer->getId() . "'");	
		
		return $query->row['total'];
	}

	public function getTotalCouponHistoriesByCoupon($coupon) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "'");	
		
		return $query->row['total'];
	}
	
	public function getTotalCouponHistoriesByCustomerId($coupon, $customer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "' AND ch.customer_id = '" . (int)$customer_id . "'");
		
		return $query->row['total'];
	}

	public function getCouponCodeByIdAndSellerId($coupon_id = 0,$seller_id = 0)
	{
		$query = $this->db->query("SELECT `coupon_id` FROM `" . DB_PREFIX . "coupon_customer2` WHERE coupon_id = '" . (int)$coupon_id . "' AND seller_id = '" . (int)$seller_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
		
		return isset($query->row['coupon_id']) ? (int)$query->row['coupon_id'] : 0;
	}

	public function getNewPeopleCouponsTotal()
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon2` WHERE launch_scene = '2' AND status = '1' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW()))");	
		
		return $query->row['total'];
	}

	public function getNewPeopleCoupons()
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon2` WHERE launch_scene = '2' AND status = '1' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY discount ASC");
		return $query->rows;
	}

	public function isGetNewPeopleCouponTotal()
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_customer2` WHERE launch_scene = '2' AND customer_id = '" . (int)$this->customer->getId() . "'");
		
		return $query->row['total'];
	}

	public function addNewPeopleCoupons($coupons)
	{
		if (!empty($coupons)) {
            $sql    = "INSERT INTO " . DB_PREFIX . "coupon_customer2 (coupon_id,customer_id,`name`,`explain`,`type`,order_total,discount,coupon_total,date_start,date_end,`status`,seller_id,get_limit,uses_limit,launch_scene,date_added,date_modified) VALUES ";
            
            $coupon_id 		= [];
            $coupon_id[] 	= 0;

            foreach ($coupons as $value)
            {
            	$coupon_id[] 	= (int)$value['coupon_id'];

                $sql .= "('"
                . (int)$value['coupon_id'] . "','"
                . (int)$this->customer->getId() . "','"
                . $this->db->escape($value['name']) . "','"
                . $this->db->escape($value['explain']) . "','"
                . (int)$value['type'] . "','"
                . (int)$value['order_total'] . "','"
                . (float)$value['discount'] . "','"
                . (int)$value['coupon_total'] . "','"
                . $this->db->escape($value['date_start']) . "','"
                . $this->db->escape($value['date_end']) . "','0','"
                . (int)$value['seller_id'] . "','"
                . (int)$value['get_limit'] . "','"
                . (int)$value['uses_limit'] . "','"
                . (int)$value['launch_scene'] . "',NOW(),NOW()),";
            }

            $sql        = trim($sql,',');

            $this->db->query($sql);
            $this->db->query("UPDATE " . DB_PREFIX . "coupon2 SET get_total = (get_total + 1) WHERE coupon_id IN ('" .implode("','",$coupon_id). "')");
        }
	}

	private function checkProuctIsUseCoupon($product_id = 0)
	{
	    $this->load->model('setting/module');

	    $checkModule 		= [57,58];
	    foreach ($checkModule as $module_id)
	    {
		    $setting_info 		= $this->model_setting_module->getModule($module_id);
		    if (!empty($setting_info) && isset($setting_info['product']) && !empty($setting_info['product']) && in_array($product_id, $setting_info['product'])) {
		    	return true;
		    }
	    }

	    return false;
	}
}
