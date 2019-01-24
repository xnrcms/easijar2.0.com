<?php
class ControllerAccountOrder extends Controller {
	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/order');

			$this->response->redirect($this->url->link('account/login'));
		}

		$this->load->language('account/order');

        $data['type'] = array_get($this->request->get, 'type');

        $data['order'] = $this->url->link('account/order');
        $data['order_unpaid'] = $this->url->link('account/order', 'type=unpaid');
        $data['order_unshipped'] = $this->url->link('account/order', 'type=unshipped');
        $data['order_shipped'] = $this->url->link('account/order', 'type=shipped');

		$this->document->setTitle($this->language->get('heading_title'));

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/order', $url)
		);

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['orders'] 		= array();
		$data['error_warning'] 	= isset($this->request->get['notice']) ? $this->request->get['notice'] : '';

		$this->load->model('account/order');
		$this->load->model('tool/image');

		$this->load->model('account/order');

        $limit          = 10;

        //订单类型 0-所有订单 1-待付款 2-待发货 3-待收货 4-待评论 5-退货退款
        $otype 			= ['shipped'=>3,'unshipped'=>2,'unpaid'=>1];
        $order_type 	= isset($otype[$data['type']]) ? $otype[$data['type']] : 0;
        $results 		= $this->model_account_order->getOrdersForMs($order_type,($page - 1) * $limit, $limit);
        $order_total 	= $this->model_account_order->getTotalOrdersForMs($order_type);
        
        /*$oid        = [];
        foreach ($results as $key => $value) {
            $oid[$value['oid']]     = $value['oid'];
        }

        $ms_total                  = $this->model_account_order->getTotalsForMsByCode($oid,'multiseller_shipping');
        $shipping                  = [];
        foreach ($ms_total as $mskey => $msval) {
            $shipping[$msval['order_id'].'-'.$msval['seller_id']]   = $msval['value'];
        }

        foreach ($results as $keys =>$result)
        {
            $shipping                  = isset($shipping[$result['oid'].'-'.$result['msid']]) ? $shipping[$result['oid'].'-'.$result['msid']] : 0;

            $results[$keys]['oid']     = $result['soid'];

            $results[$keys]['total']     = $this->currency->format($result['total'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']);
            $results[$keys]['shipping']  = $this->currency->format($shipping, $result['currency_code'], $result['currency_value'], $this->session->data['currency']);

            foreach ($result['product_info'] as $reskey => $resval) {
                $results[$keys]['product_info'][$reskey]['price']   = $this->currency->format($resval['price'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']);
                $results[$keys]['product_info'][$reskey]['total']   = $this->currency->format($resval['total'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']);

                $results[$keys]['product_info'][$reskey]['image']   = $this->model_tool_image->resize($resval['image'], 100, 100);

                unset($results[$keys]['product_info'][$reskey]['tax']);
            }

            unset($results[$keys]['currency_code']);
            unset($results[$keys]['currency_value']);
            unset($results[$keys]['soid']);
        }*/

        /*switch ($data['type']) {
            case 'shipped':
                $order_total = $this->model_account_order->getTotalShippedOrders();
                $results = $this->model_account_order->getShippedOrders(($page - 1) * 10, 10);
                break;
            case 'unshipped':
                $order_total = $this->model_account_order->getTotalUnshippedOrders();
                $results = $this->model_account_order->getUnshippedOrders(($page - 1) * 10, 10);
                break;
            case 'unpaid':
                $order_total = $this->model_account_order->getTotalUnpaidOrders();
                $results = $this->model_account_order->getUnpaidOrders(($page - 1) * 10, 10);
                break;
            default:
                $order_total = $this->model_account_order->getTotalOrders();
                $results = $this->model_account_order->getOrders(($page - 1) * 10, 10);
                break;
        }*/

		foreach ($results as $result) {
			$product_total 	= $this->model_account_order->getTotalOrderProductsByOrderId($result['oid'],$result['msid']);
			$voucher_total 	= 0;//$this->model_account_order->getTotalOrderVouchersByOrderId($result['order_id']);
			$recharge_total = 0;//$this->model_account_order->getTotalOrderRechargesByOrderId($result['order_id']);

            $product_list 		= array();
            $product_results 	= !empty($result['product_info']) ? $result['product_info'] : [];
            //$this->model_account_order->getOrderProducts($result['order_id']);
            foreach($product_results as $product) {
                $product_list[] = array(
                    'name'  => $product['name'],
                    'href'  => $this->url->link('product/product', 'product_id=' . $product['product_id']),
                    'image' => $this->model_tool_image->resize($product['image'], 100, 100),
                    'total' => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $result['currency_code'], $result['currency_value'], $this->session->data['currency']),
                );
            }

            $voucher_list 	= [];
            /*$voucher_results = $this->model_account_order->getOrderVouchers($result['order_id']);
            foreach($voucher_results as $voucher) {
                $voucher_list[] = array(
                    'name'  => $voucher['description'],
                    'href'  => '',
                    'image' => $this->model_tool_image->resize('placeholder.png', 100, 100),
                    'total' => $this->currency->format($voucher['amount'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']),
                );
            }*/

            $recharge_list 	= [];
            /*$recharge_results = $this->model_account_order->getOrderRecharges($result['order_id']);
            foreach($recharge_results as $recharge) {
                $recharge_list[] = array(
                    'name'  => $recharge['description'],
                    'href'  => '',
                    'image' => $this->model_tool_image->resize('placeholder.png', 100, 100),
                    'total' => $this->currency->format($recharge['amount'], $result['currency_code'], $result['currency_value'], $this->session->data['currency']),
                );
            }*/

            if ($result['status_id'] == $this->config->get('config_unpaid_status_id')) {
                $href_cancel = $this->url->link('account/order/cancel', 'type='.$data['type'].'&order_id=' . $result['order_sn']);
            } else {
                $href_cancel = '';
            }

            if ($result['status_id'] == $this->config->get('config_shipped_status_id')) {
                $href_confirm = $this->url->link('account/order/confirm', 'type='.$data['type'].'&order_id=' . $result['order_sn']);
            } else {
                $href_confirm = '';
            }

			$data['orders'][] = array(
			    'product_list' => array_merge($product_list, $voucher_list, $recharge_list),
				'order_id'   => $result['order_sn'],
				'name'       => $result['fullname'],
				'status'     => $result['status'],
				'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'products'   => ($product_total + $voucher_total + $recharge_total),
				'total'      => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                'cancel'     => $href_cancel,
                'confirm'    => $href_confirm,
				'view'       => $this->url->link('account/order/info', 'order_id=' . $result['order_sn']),
			);
		}

		$pagination = new Pagination();
		$pagination->total = $order_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('account/order', 'page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($order_total - 10)) ? $order_total : ((($page - 1) * 10) + 10), $order_total, ceil($order_total / 10));

		$data['continue'] = $this->url->link('account/account');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/order_list', $data));
	}

	public function info() {
		$this->load->language('account/order');

		if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];
		} else {
			$order_id = '';
		}

		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/order/info', 'order_id=' . $order_id);

			$this->response->redirect($this->url->link('account/login'));
		}

		$this->load->model('account/order');

		$order_info = $this->model_account_order->getOrderForMs($order_id);

		$data['order_id'] 		= (int)$order_info['order_id'];

		if ($order_info) {
			$this->document->setTitle($this->language->get('text_order'));

			$url = '';

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('account/order', $url)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_order'),
				'href' => $this->url->link('account/order/info', 'order_id=' . $order_id . $url)
			);

			if (isset($this->session->data['error'])) {
				$data['error_warning'] = $this->session->data['error'];

				unset($this->session->data['error']);
			} else {
				$data['error_warning'] = '';
			}

			if (isset($this->session->data['success'])) {
				$data['success'] = $this->session->data['success'];

				unset($this->session->data['success']);
			} else {
				$data['success'] = '';
			}

			if ($order_info['invoice_no']) {
				$data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
			} else {
				$data['invoice_no'] = '';
			}

			$data['date_added'] = date($this->language->get('datetime_format'), strtotime($order_info['date_added']));

            if (is_ft()) {
    			$data['payment_address'] = address_format($order_info, $order_info['payment_address_format'], 'payment');
            }
			$data['payment_method'] = $order_info['payment_method'];

			if ($order_info['shipping_method']) {
				$data['shipping_address'] = address_format($order_info, $order_info['shipping_address_format'], 'shipping');
                $this->load->model('account/custom_field');
                $custom_fields = $this->model_account_custom_field->getCustomFields($this->customer->getGroupId());
                $shipping_custom_field = $order_info['shipping_custom_field'];
                if ($shipping_custom_field) {
                    foreach ($shipping_custom_field as $key => $value) {
                        $name = '';
                        foreach ($custom_fields as $custom_field) {
                            if ($custom_field['custom_field_id'] == $key && ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time')) {
                                $name = $custom_field['name'];
                            }
                        }
                        if ($name && $value) {
                            $data['shipping_address'] .= '<br/>' . $name . ': ' . $value;
                        }
                    }
                }

				$data['shipping_method'] = $order_info['shipping_method'];
			} else {
				$data['shipping_address'] = '';
				$data['shipping_method'] = '';
			}

			$data['is_pickup'] = false;
            $pickup_info = $this->model_account_order->getOrderPickup((int)$this->request->get['order_id']);
            if ($pickup_info) {
                $data['is_pickup'] = true;
                $data['pickup_name'] = $pickup_info['name'];
                $data['pickup_telephone'] = $pickup_info['telephone'];
                $data['pickup_address'] = $pickup_info['address'];;
                $data['pickup_open'] = $pickup_info['open'];
                $data['pickup_comment'] = $pickup_info['comment'];
            }

			$this->load->model('catalog/product');
			$this->load->model('tool/upload');

			// Products
			$data['products'] = array();

			$products = $this->model_account_order->getOrderProductsForMs($order_info['order_id'],$order_info['seller_id']);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_account_order->getOrderOptions($order_info['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
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

                if ($variantData = Models\Order\Product::find($product['order_product_id'])->getVariantLabels()) {
                    $option_data = array_merge($option_data, $variantData);
                }

				$product_info = $this->model_catalog_product->getProduct($product['product_id']);

				if ($product_info) {
					$reorder = $this->url->link('account/order/reorder', 'order_id=' . $order_info['order_id'] . '&order_product_id=' . $product['order_product_id']);
				} else {
					$reorder = '';
				}

				$this->load->model('account/oreview');
                $is_reviewed = $this->model_account_oreview->isReviewed($product['order_product_id']);
                $complated = in_array($order_info['order_status_id'], $this->config->get('config_complete_status'));
                $this->load->model('tool/image');
                if ($product_info['image'] && is_file(DIR_IMAGE . $product_info['image'])) {
                  $image = $this->model_tool_image->resize($product_info['image'], 100, 100);
                } else {
                  $image = $this->model_tool_image->resize('placeholder.png', 100, 100);
                }

				$data['products'][] = array(
				    'image'    => $image,
					'name'     => $product['name'],
					'product_link' => $this->url->link('product/product', 'product_id=' . $product['product_id']),
					'model'    => $product['model'],
					'option'   => $option_data,
					'quantity' => $product['quantity'],
					'price'    => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'reorder'  => $reorder,
					'oreview'   => $is_reviewed || !$complated ? '' : $this->url->link('account/oreview/add', 'order_id=' . $order_info['order_id'] . '&order_product_id=' . $product['order_product_id'], true),
					'return'   => !$complated ? '' : $this->url->link('account/return/add', 'order_id=' . $order_info['order_id'] . '&product_id=' . $product['product_id'])
				);
			}

			// Voucher
			$data['vouchers'] = array();

			/*$vouchers = $this->model_account_order->getOrderVouchers($this->request->get['order_id']);

			foreach ($vouchers as $voucher) {
				$data['vouchers'][] = array(
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value'])
				);
			}*/

			// Recharge
			$data['recharges'] = array();

			/*$recharges = $this->model_account_order->getOrderRecharges($this->request->get['order_id']);

			foreach ($recharges as $recharge) {
				$data['recharges'][] = array(
					'description' => $recharge['description'],
					'amount'      => $this->currency->format($recharge['amount'], $order_info['currency_code'], $order_info['currency_value'])
				);
			}*/

			// Totals
			$data['totals'] = array();

			$totals = $this->model_account_order->getOrderTotals($this->request->get['order_id']);

			foreach ($totals as $total)
			{
	            $title          = $total['title'];
	            if (in_array($total['code'], ['multiseller_shipping','multiseller_coupon'])) {
	                $titles     = explode('&', $title);
	                $title      = $titles[0] . ' ' . $titles[2];
	            }

	            if ($total['code'] == 'shipping') continue;

				$data['totals'][] = array(
	                'title' => $title,
					'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']),
				);
			}

			$seller_id 		 	= isset($order_info['seller_id']) ? (int)$order_info['seller_id'] : 0;
			$comment 		 	= !empty($order_info['comment']) ? json_decode($order_info['comment'],true) : [];
			$data['comment'] 	= isset($comment[$seller_id]) ? nl2br($comment[$seller_id]) : '';
			$data['order_sn'] 	= $this->request->get['order_id'];

            //快递单信息
            $data['kd_tracking_status'] = $this->config->get('module_express_tracking_status');
            if ($this->config->get('module_express_tracking_status')) {
                $this->load->model('extension/module/express_tracking');
                $order_expresss = $this->model_extension_module_express_tracking->getOrderShippingtrack($order_id);
                $this->load->model('extension/module/express_tracking');

                $data['order_express'] = array();
                foreach ($order_expresss as $order_express) {
                    $data['order_express'][] = array(
                        'tracking_code' => $order_express['tracking_code'],
                        'kd_express_name' => $this->model_extension_module_express_tracking->getExpressNameByCode($order_express['tracking_code']),
                        'tracking_number' => $order_express['tracking_number'],
                        'kd_comment' => $order_express['comment']
                    );
                }
            }

            if($order_info['order_status_id'] == $this->config->get('config_unpaid_status_id') && $order_info['payment_code'] != 'cod') {
                $this->session->data['order_id'] = $order_id;
                $payment = $this->load->controller('extension/payment/' . $order_info['payment_code']);
                $data['payment'] = str_replace($this->language->get('button_confirm'), $this->language->get('button_pay_continue'), $payment);
            }else{
                $data['payment'] = '';
            }

			// History
			$data['histories'] = array();

			$results = $this->model_account_order->getOrderHistories($this->request->get['order_id']);

			foreach ($results as $result) {
				$data['histories'][] = array(
					'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
					'status'     => $result['status'],
					'comment'    => $result['notify'] ? nl2br($result['comment']) : ''
				);
			}

			$data['continue'] = $this->url->link('account/order');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('account/order_info', $data));
		} else {
			return new Action('error/not_found');
		}
	}

	public function reorder() {
		$this->load->language('account/order');

		if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		$this->load->model('account/order');

		$order_info = $this->model_account_order->getOrder($order_id);

		if ($order_info) {
			if (isset($this->request->get['order_product_id'])) {
				$order_product_id = $this->request->get['order_product_id'];
			} else {
				$order_product_id = 0;
			}

			$order_product_info = $this->model_account_order->getOrderProduct($order_id, $order_product_id);

			if ($order_product_info) {
				$this->load->model('catalog/product');
				$product_id = $order_product_info['product_id'];
                $flash_data = Flash::getSingleton()->getFlashPriceAndCount($product_id);
                $cart_product_count = $this->cart->getCartProductCount($product_id);
                $product_quantity = $order_product_info['quantity'];
                $flash_error = '';
                if ($flash_data) {
                    if (!$flash_data['checkout']) {
                        $flash_error = sprintf($this->language->get('error_flash_out'), $flash_data['count']);
                    } else {
                        if ($flash_data && $flash_data['count'] && ($product_quantity + $cart_product_count) > $flash_data['count']) {
                            $flash_error = sprintf($this->language->get('error_flash_count'), $flash_data['count']);
                        }
                    }
                }
                if ($flash_error) {
                    $this->session->data['error'] = $flash_error;
                    $this->response->redirect($this->url->link('account/order/info', 'order_id=' . $order_id));
                }
				$product_info = $this->model_catalog_product->getProduct($order_product_info['product_id']);

				if ($product_info) {
					$option_data = array();

					$order_options = $this->model_account_order->getOrderOptions($order_product_info['order_id'], $order_product_id);

					foreach ($order_options as $order_option) {
						if ($order_option['type'] == 'select' || $order_option['type'] == 'radio' || $order_option['type'] == 'image') {
							$option_data[$order_option['product_option_id']] = $order_option['product_option_value_id'];
						} elseif ($order_option['type'] == 'checkbox') {
							$option_data[$order_option['product_option_id']][] = $order_option['product_option_value_id'];
						} elseif ($order_option['type'] == 'text' || $order_option['type'] == 'textarea' || $order_option['type'] == 'date' || $order_option['type'] == 'datetime' || $order_option['type'] == 'time') {
							$option_data[$order_option['product_option_id']] = $order_option['value'];
						} elseif ($order_option['type'] == 'file') {
							$option_data[$order_option['product_option_id']] = $this->encryption->encrypt($this->config->get('config_encryption'), $order_option['value']);
						}
					}

					$this->cart->add($order_product_info['product_id'], $order_product_info['quantity'], $option_data);

					$this->session->data['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $product_info['product_id']), $product_info['name'], $this->url->link('checkout/cart'));

					unset($this->session->data['shipping_method']);
					unset($this->session->data['shipping_methods']);
					unset($this->session->data['payment_method']);
					unset($this->session->data['payment_methods']);
				} else {
					$this->session->data['error'] = sprintf($this->language->get('error_reorder'), $order_product_info['name']);
				}
			}
		}

		$this->response->redirect($this->url->link('account/order/info', 'order_id=' . $order_id));
	}

    public function cancel()
    {
        $this->load->language('account/order');

        if (isset($this->request->get['order_id'])) {
            $order_sn = $this->request->get['order_id'];
        } else {
            $order_sn = 0;
        }

        $reason_id 					= 1;

        if ( empty($order_sn) ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_sn is error']));
        }

        $this->load->model('account/order');
        $this->load->model('multiseller/checkout');
		$this->load->model('localisation/return_reason');

        $order_info 					= $this->model_account_order->getOrderStatusForMs($order_sn);
        if (empty($order_info)) {
        	$this->response->redirect($this->url->link('account/order','type='.$this->request->get['type'].'&notice='.t('error_order_info')));return;
        }

        $isReturn                       = $this->model_account_order->isReturn($order_sn);
        if ($isReturn) {
        	$this->response->redirect($this->url->link('account/order','type='.$this->request->get['type'].'&notice='.t('error_is_return')));return;
        }

        //未付款 直接取消
        if( isset($order_info['order_status_id']) && $order_info['order_status_id'] === $this->config->get('config_unpaid_status_id')){

        	//取消原因
        	$reason 			= $this->model_localisation_return_reason->getRsasonNameByType($reason_id,0);

        	$this->model_multiseller_checkout->addSubOrderHistory($order_info['order_id'], $order_info['seller_id'], $this->config->get('config_cancelled_status_id'),isset($reason['name']) ? $reason['name'] : '',false,true);

        	$this->response->redirect($this->url->link('account/order','type='.$this->request->get['type']));return;
        }
        else{
        	$this->response->redirect($this->url->link('account/order','type='.$this->request->get['type'].'&notice=order_status is error'));return;
        }
    }

    public function confirm()
    {
        $this->load->language('account/order');

        if (isset($this->request->get['order_id'])) {
            $order_sn = $this->request->get['order_id'];
        } else {
            $order_sn = '0';
        }

        /*$this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if($order_info['order_status_id'] == $this->config->get('config_shipped_status_id')) {
            $complete_status = $this->config->get('config_complete_status');
            $this->model_checkout_order->addOrderHistory($order_id, $complete_status[0], t('text_customer_confirm'), true);
        }*/

        $this->load->model('account/order');

        $order_info 					= $this->model_account_order->getOrderStatusForMs($order_sn);
        if (empty($order_info)) {
        	$this->response->redirect($this->url->link('account/order','type='.$this->request->get['type'].'&notice='.t('error_order_info')));return;
        }

        $isReturn                       = $this->model_account_order->isReturn($order_sn);
        if ($isReturn) {
        	$this->response->redirect($this->url->link('account/order','type='.$this->request->get['type'].'&notice='.t('error_is_return')));return;
        }

        if( isset($order_info['order_status_id']) && $order_info['order_status_id'] === $this->config->get('config_shipped_status_id')){

        	$this->load->model('multiseller/checkout');

        	$complete_status = $this->config->get('config_complete_status');

        	$this->model_multiseller_checkout->addSubOrderHistory($order_info['order_id'], $order_info['seller_id'], $complete_status[0],t('text_customer_confirm'),false,true);

        	$this->response->redirect($this->url->link('account/order','type='.$this->request->get['type']));return;
        }else{
        	$this->response->redirect($this->url->link('account/order','type='.$this->request->get['type'].'&notice=order_status is error'));return;
        }
    }
}
