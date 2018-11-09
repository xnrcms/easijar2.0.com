<?php
class ModelExtensionTotalCoupon extends Model {
	public function getCoupon($code,$total=0) {
		$status = true;

		$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) AND status = '1'");

		if ($coupon_query->num_rows) {
			if ($coupon_query->row['total'] > $total) {
				$status = false;
			}

			$coupon_total = $this->getTotalCouponHistoriesByCoupon($code);

			if ($coupon_query->row['uses_total'] > 0 && ($coupon_total >= $coupon_query->row['uses_total'])) {
				$status = false;
			}

			if ($coupon_query->row['logged'] && !$this->customer->getId()) {
				$status = false;
			}

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

	public function getTotal($total) {
		if (isset($this->session->data['coupon'])) {

			$title 			= '';  	//优惠券total标题
		    $amount_coupon 	= 0;  	//运费金额

            $this->load->model('multiseller/seller');
            $this->load->language('extension/total/multiseller_coupon', 'multiseller');
            $this->load->language('extension/total/coupon', 'coupon');

            $seller_price 	= [];  // 保存商家和商家商品总额
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
                    $seller_price[$seller_id]['seller_name'] 	= $seller_info ? $seller_info['store_name'] : $this->config->get('config_name');
                }
            }

            $title = $this->language->get('multiseller')->get('text_multiseller_coupon');

            foreach ($seller_price as $key => $value) {
            	if (isset($this->session->data['coupon'][$key]) && !empty($this->session->data['coupon'][$key])) {
            		$seller_coupon = $this->getShellerCoupon($this->session->data['coupon'][$key],$value,$total);
            		if ($seller_coupon) {
	            		$amount_coupon += $seller_coupon;
	            		$total['totals'][] = array(
		                    'seller_id'  => $key,
		                    'code'       => 'multiseller_coupon',
		                    'title'      => $value['seller_name'] . ' ' . $title,
		                    'value'      => $seller_coupon,
							'sort_order' => $this->config->get('total_coupon_sort_order')
		                );
            		}
            	}
            }

            if ($amount_coupon > 0) {
            	$total['total'] -= $amount_coupon;
            }
		}
	}

	private function getShellerCoupon($code = '',$products = [],$total)
	{
		$coupon_info 		= $this->getCoupon($code);wr("\n================881\n");wr($products);
		if ($coupon_info && isset($products['price'])) 
		{
			$discount_total = 0;
			$sub_total 		= isset($products['price']) ? $products['price'] : 0;

			if ($coupon_info['type'] == 'F') {
				$coupon_info['discount'] = min($coupon_info['discount'], $sub_total);
			}

			foreach ($this->cart->getProducts() as $product) {

				if (!(isset($products['product_id']) && in_array($product['product_id'], $products['product_id']))) {
					continue;
				}

				$discount = 0;

				if (!$coupon_info['product']) {
					$status = true;
				} else {
					$status = in_array($product['product_id'], $coupon_info['product']);
				}

				if ($status) {
					if ($coupon_info['type'] == 'F') {
						$discount = $coupon_info['discount'] * ($product['total'] / $sub_total);
					} elseif ($coupon_info['type'] == 'P') {
						$discount = $product['total'] / 100 * $coupon_info['discount'];
					}

					if ($product['tax_class_id']) {
						$tax_rates = $this->tax->getRates($product['total'] - ($product['total'] - $discount), $product['tax_class_id']);

						foreach ($tax_rates as $tax_rate) {
							if ($tax_rate['type'] == 'P') {
								$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
							}
						}
					}
				}

				$discount_total += $discount;
			}

			if ($coupon_info['shipping'] && isset($this->session->data['shipping_method'])) {
				if (!empty($this->session->data['shipping_method']['tax_class_id'])) {
					$tax_rates = $this->tax->getRates($this->session->data['shipping_method']['cost'], $this->session->data['shipping_method']['tax_class_id']);

					foreach ($tax_rates as $tax_rate) {
						if ($tax_rate['type'] == 'P') {
							$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
						}
					}
				}

				$discount_total += $this->session->data['shipping_method']['cost'];
			}

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
		$query = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "coupon` WHERE coupon_id = '" . (int)$coupon_id . "' AND seller_id = '" . (int)$seller_id . "'");
		
		return (isset($query->row['code']) && !empty($query->row['code'])) ? $query->row['code'] : '';
	}
}
