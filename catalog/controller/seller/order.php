<?php
class ControllerSellerOrder extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('seller/order');

			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
		}

		$this->load->language('seller/order');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/order');
		$this->load->model('account/order');

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = '';
		}

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = '';
		}

		if (isset($this->request->get['filter_order_status'])) {
			$filter_order_status = $this->request->get['filter_order_status'];
		} else {
			$filter_order_status = '';
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = '';
		}

		if (isset($this->request->get['filter_total'])) {
			$filter_total = $this->request->get['filter_total'];
		} else {
			$filter_total = '';
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = '';
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$filter_date_modified = $this->request->get['filter_date_modified'];
		} else {
			$filter_date_modified = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'o.order_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('seller/order', $url)
		);

		$data['shipping'] = $this->url->link('seller/order/shipping');

		$data['orders'] = array();

		$filter_data = array(
			'filter_order_id'        => $filter_order_id,
			'filter_customer'	     => $filter_customer,
			'filter_order_status'    => $filter_order_status,
			'filter_order_status_id' => $filter_order_status_id,
			'filter_total'           => $filter_total,
			'filter_date_added'      => $filter_date_added,
			'filter_date_modified'   => $filter_date_modified,
			'sort'                   => $sort,
			'order'                  => $order,
			'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                  => $this->config->get('config_limit_admin')
		);

		$order_total = $this->model_multiseller_order->getTotalOrders($filter_data);

		$results = $this->model_multiseller_order->getOrders($filter_data);

		foreach ($results as $result) {
			$data['orders'][] = array(
				'order_id'      => $result['order_id'],
				'customer'      => $result['customer'],
				'order_status'  => $result['order_status'] ? $result['order_status'] : $this->language->get('text_missing'),
				'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'date_added'    => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'date_modified' => date($this->language->get('datetime_format'), strtotime($result['date_modified'])),
				'shipping_code' => $result['shipping_code'],
				'view'          => $this->url->link('seller/order/info', 'order_id=' . $result['order_id'] . $url),
			);
		}

        $expOrders = array();
        $header = array(
            $this->language->get('column_order_id'),
            $this->language->get('column_customer'),
            $this->language->get('column_status'),
            $this->language->get('column_total'),
            $this->language->get('column_date_added'),
            $this->language->get('column_date_modified'),
        );
        foreach($results as $result) {
            $expOrders[] = array(
                'order_id'    => $result['order_id'],
                'customer'    => $result['customer'],
                'order_status'  => $result['order_status'],
                'total'     => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified']))
            );
        }

        if (isset($this->request->get['export']) && ($this->request->get['export']==="export")) {
            $this->response->downloadCsv("order.csv", $header, $expOrders);
            exit();
        }

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_order'] = $this->url->link('seller/order', 'sort=o.order_id' . $url);
		$data['sort_customer'] = $this->url->link('seller/order', 'sort=customer' . $url);
		$data['sort_status'] = $this->url->link('seller/order', 'sort=order_status' . $url);
		$data['sort_total'] = $this->url->link('seller/order', 'sort=o.total' . $url);
		$data['sort_date_added'] = $this->url->link('seller/order', 'sort=o.date_added' . $url);
		$data['sort_date_modified'] = $this->url->link('seller/order', 'sort=o.date_modified' . $url);

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $order_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('seller/order', $url . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

		$data['filter_order_id'] = $filter_order_id;
		$data['filter_customer'] = $filter_customer;
		$data['filter_order_status'] = $filter_order_status;
		$data['filter_order_status_id'] = $filter_order_status_id;
		$data['filter_total'] = $filter_total;
		$data['filter_date_added'] = $filter_date_added;
		$data['filter_date_modified'] = $filter_date_modified;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('seller/order_list', $data));
	}

	public function info() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('seller/order');

			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
		}

        $this->load->language('seller/layout');

		$this->load->model('multiseller/order');
		$this->load->model('account/order');

		if (isset($this->request->get['order_id'])) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		$order_info = $this->model_multiseller_order->getOrder($order_id);

		if ($order_info) {
			$this->load->language('seller/order');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['text_order'] = sprintf($this->language->get('text_order'), $order_id);

			$url = '';

			if (isset($this->request->get['filter_order_id'])) {
				$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
			}

			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_order_status'])) {
				$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
			}

			if (isset($this->request->get['filter_order_status_id'])) {
				$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
			}

			if (isset($this->request->get['filter_total'])) {
				$url .= '&filter_total=' . $this->request->get['filter_total'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['filter_date_modified'])) {
				$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('seller/order', $url)
			);

			$data['cancel'] = $this->url->link('seller/order', $url);

			$data['order_id'] = (int)$this->request->get['order_id'];

			$data['store_id'] = $order_info['store_id'];
			$data['store_name'] = $order_info['store_name'];

			if ($order_info['invoice_no']) {
				$data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
			} else {
				$data['invoice_no'] = '';
			}

			$data['date_added'] = date($this->language->get('datetime_format'), strtotime($order_info['date_added']));

			$data['fullname'] = $order_info['fullname'];

			if ($order_info['customer_id']) {
				$data['customer'] = $this->url->link('customer/customer/edit', 'customer_id=' . $order_info['customer_id']);
			} else {
				$data['customer'] = '';
			}

			$this->load->model('account/customer_group');

			$customer_group_info = $this->model_account_customer_group->getCustomerGroup($order_info['customer_group_id']);

			if ($customer_group_info) {
				$data['customer_group'] = $customer_group_info['name'];
			} else {
				$data['customer_group'] = '';
			}

			$data['email'] = $order_info['email'];
			$data['telephone'] = $order_info['telephone'];

			$data['shipping_method'] = $order_info['shipping_method'];
			$data['payment_method'] = $order_info['payment_method'];

			if (!empty($order_info['shipping_method'])) {
				// Payment Address
				$data['payment_address'] = address_format($order_info, $order_info['payment_address_format'], 'payment');

				// Shipping Address
				$data['shipping_address'] = address_format($order_info, $order_info['shipping_address_format'], 'shipping');
			} else {
				// Payment Address
				$data['payment_address'] = '';

				// Shipping Address
				$data['shipping_address'] = '';
			}

			$this->load->model('multiseller/order');

			// Uploaded files
			$this->load->model('tool/upload');

			$data['products'] = array();

			$products = $this->model_multiseller_order->getOrderProducts($this->request->get['order_id']);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_account_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $upload_info['name'],
								'type'  => $option['type'],
								'href'  => $this->url->link('tool/upload/download', 'code=' . $upload_info['code'])
							);
						}
					}
				}

                if ($variantData = Models\Order\Product::find($product['order_product_id'])->getVariantLabels()) {
                    $option_data = array_merge($option_data, $variantData);
                }

				$data['products'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name'    	 	   => $product['name'],
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'quantity'		   => $product['quantity'],
					'price'    		   => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    		   => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'href'     		   => $this->url->link('catalog/product/edit', 'product_id=' . $product['product_id'])
				);
			}

            //快递数据
            $data['kd_tracking_data'] = $this->config->get('module_express_tracking_data');
            $data['kd_tracking_status'] = $this->config->get('module_express_tracking_status');

			$data['totals'] = array();

			$totals = $this->model_multiseller_order->getSubOrderTotals($this->request->get['order_id']);

			foreach ($totals as $total) {
				$data['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])
				);
			}

			$data['comment'] = nl2br($order_info['comment']);

			$this->load->model('localisation/order_status');

			$order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

			if ($order_status_info) {
				$data['order_status'] = $order_status_info['name'];
			} else {
				$data['order_status'] = '';
			}

			$order_statuses = $this->model_localisation_order_status->getOrderStatuses();

			//允许商家添加的订单状态
			$seller_alow_status = array($this->config->get('config_shipped_status_id'));
			$data['order_statuses'] = array();
			foreach ($order_statuses as $order_status) {
			    if (in_array($order_status['order_status_id'], $seller_alow_status)) {
			        $data['order_statuses'][] = $order_status;
                }
            }

			$data['order_status_id'] = $order_info['order_status_id'];

			$data['account_custom_field'] = $order_info['custom_field'];

			// Custom Fields
			$this->load->model('account/custom_field');

			$data['account_custom_fields'] = array();

			$filter_data = array(
				'sort'  => 'cf.sort_order',
				'order' => 'ASC'
			);

			$custom_fields = $this->model_account_custom_field->getCustomFields($filter_data);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'account' && isset($order_info['custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_account_custom_field->getCustomFieldValue($order_info['custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['account_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_account_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['account_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['account_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['custom_field'][$custom_field['custom_field_id']]
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['account_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name']
							);
						}
					}
				}
			}

			// Custom fields
			$data['payment_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_account_custom_field->getCustomFieldValue($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['payment_custom_fields'][] = array(
								'name'       => $custom_field['name'],
								'value'      => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['payment_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_account_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['payment_custom_fields'][] = array(
									'name'       => $custom_field['name'],
									'value'      => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['payment_custom_fields'][] = array(
							'name'       => $custom_field['name'],
							'value'      => $order_info['payment_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['payment_custom_fields'][] = array(
								'name'       => $custom_field['name'],
								'value'      => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}

			// Shipping
			$data['shipping_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_account_custom_field->getCustomFieldValue($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['shipping_custom_fields'][] = array(
								'name'       => $custom_field['name'],
								'value'      => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['shipping_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_account_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['shipping_custom_fields'][] = array(
									'name'       => $custom_field['name'],
									'value'      => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['shipping_custom_fields'][] = array(
							'name'       => $custom_field['name'],
							'value'      => $order_info['shipping_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['shipping_custom_fields'][] = array(
								'name'       => $custom_field['name'],
								'value'      => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}
            foreach ($data['shipping_custom_fields'] as $custom_field) {
                $data['shipping_address'] .= '<br/>' . $custom_field['name'] . ': ' . $custom_field['value'];
            }

		    $data['shipping'] = $this->url->link('seller/order/shipping', 'order_id=' . (int)$this->request->get['order_id']);

            $data['module_aftership_status'] = $this->config->get('module_aftership_status');
            $aftership_trackings = $this->config->get('module_aftership_data');
            $data['aftership_trackings'] = array();
            if ($aftership_trackings) {
                foreach ($aftership_trackings as $track) {
                    if($track['status']) {
                        $data['aftership_trackings'][] = $track;
                    }
                }
            }

			$data['ip'] = $order_info['ip'];
			$data['forwarded_ip'] = $order_info['forwarded_ip'];
			$data['user_agent'] = $order_info['user_agent'];
			$data['accept_language'] = $order_info['accept_language'];

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('seller/order_info', $data));
		} else {
			return new Action('error/not_found');
		}
	}

	public function express() {
        $this->load->language('seller/order');

        $data['error'] = '';
        $data['success'] = '';

        $this->load->model('multiseller/order');

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['histories'] = array();

        $results = $this->model_multiseller_order->getOrderShippingtracks($this->request->get['order_id'], ($page - 1) * 10, 10);

        $tracking_datas = $this->config->get('module_express_tracking_data');

        foreach ($results as $result) {
            //快递code
            $express_code = $result['tracking_code'];
            //快递单号
            $tracking_number = $result['tracking_number'];

            $typeCom = $express_code; //快递公司
            $typeNu = $tracking_number;  //快递单号
            $track = $this->url->link('seller/order/getTrace', 'com='.$typeCom.'&nu='.$typeNu.'&user_token='.$this->session->data['user_token']);

            $name = '';
            foreach ($tracking_datas as $item) {
              if ($item['code'] == $result['tracking_code']) {
                $name = $item['name'];
              }

            }
            $data['histories'][] = array(
                'tracking_code' => $name,
                'tracking_number' => $result['tracking_number'],
                'kd_comment' => $result['comment'],
                'id' => $result['id'],
                'kd_track' => $track,
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
            );
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['order_id'] = $this->request->get['order_id'];

        $history_total = $this->model_multiseller_order->getTotalOrderShippingtracks($this->request->get['order_id']);

        $pagination = new Pagination();
        $pagination->total = $history_total;
        $pagination->page = $page;
        $pagination->limit = 10;
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('seller/order/express', 'order_id='.$this->request->get['order_id'].'&page={page}');

        $data['pagination'] = $pagination->render();

        $this->response->setOutput($this->load->view('seller/order_express', $data));
    }

    public function getTrace()
    {
        $this->load->language('seller/order');

        if (isset($this->request->get['com'])) {
            $typeCom = $this->request->get['com'];
        } else {
            $typeCom = 0;
        }
        if (isset($this->request->get['nu'])) {
            $typeNu = $this->request->get['nu'];
        } else {
            $typeNu = 0;
        }

        $key = $this->config->get('module_express_tracking_key');
        $id = $this->config->get('module_express_tracking_id');

        $class = $this->config->get('module_express_tracking_platform');
        $express = new $class($id, $key);
        $tracking = $express->getOrderTraces($typeCom, $typeNu);

        if (isset($tracking['message'])) { //查询出错
            $track = '<div id=errordiv style=width:500px;border:#fe8d1d 1px solid;padding:20px;background:#FFFAE2;>
									<p style=line-height:28px;margin:0px;padding:0px;color:#F21818;>' .$tracking['message'].'</p>
								</div>';
        } else {
            $track = "<table width='520px' border='0' cellspacing='0' cellpadding='0' id='showtablecontext' style='border-collapse:collapse;border-spacing:0;'>";

            $track .= "<tr>
					<td width='163' style='background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;'>" .$this->language->get('text_time')."</td>
					<td width='354' style='background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;'>" .$this->language->get('text_station').'</td>
				</tr>';
            foreach ($tracking['traces'] as $trace) {
                $track .= "<tr>
						<td width='163' style='border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;'>" .$trace['time']."</td>
						<td width='354' style='border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;'>" .$trace['station'].'</td>
					</tr>';
            }
            $track .= '</table>';
        }
        $this->response->setOutput($track);
    }

	public function addExpress() {
        $this->load->language('seller/order');

        $json = array();

        $this->load->model('multiseller/order');

		if (!$this->customer->isLogged() || !$this->customer->isSeller()) {
            $json['error'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['tracking_code'] || !$this->request->post['tracking_number']) {
            $json['error'] = $this->language->get('error_param_required');
        }
        if (!isset($json['error'])) {
            $this->model_multiseller_order->addOrderShippingtrack($this->request->get['order_id'], $this->request->post);
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteExpress() {
        $this->load->language('seller/order');

        $json = array();

        $this->load->model('multiseller/order');

		if (!$this->customer->isLogged() || !$this->customer->isSeller()) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->model_multiseller_order->delOrderShippingtrack($this->request->get['id']);
            $json['success'] = $this->language->get('text_del_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

	public function history() {
		$this->load->language('seller/order');

		if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['histories'] = array();

		$this->load->model('multiseller/order');

		$results = $this->model_multiseller_order->getSubOrderHistories($order_id, ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['histories'][] = array(
				'notify'     => $result['notify'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'status'     => $result['status'],
				'comment'    => nl2br($result['comment']),
				'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added']))
			);
		}

		$history_total = $this->model_multiseller_order->getTotalSubOrderHistories($order_id);

		$pagination = new Pagination();
		$pagination->total = $history_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('seller/order/history', 'order_id=' . $order_id . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));

		$this->response->setOutput($this->load->view('seller/order_history', $data));
	}

	public function addHistory() {
        $this->load->language('seller/order');

        $json = array();

        $this->load->model('multiseller/order');
        $this->load->model('multiseller/checkout');

		if (!$this->customer->isLogged() || !$this->customer->isSeller()) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!isset($json['error'])) {
            $this->load->model('checkout/order');

            if (isset($this->request->get['order_id'])) {
                $order_id = $this->request->get['order_id'];
            } else {
                $order_id = 0;
            }

            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {
                $this->model_multiseller_checkout->addSubOrderHistory($order_id, $this->customer->getId(), $this->request->post['order_status_id'],
                    $this->request->post['comment'], $this->request->post['notify']);

                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }

    public function shipping()
    {
        $this->load->language('seller/common');
        $this->load->language('seller/order');

        $data['title'] = $this->language->get('text_shipping');

        $data['base'] = HTTP_SERVER . 'admin/';

        $data['direction'] = $this->language->get('direction');
        $data['lang'] = $this->language->get('code');

		$this->load->model('multiseller/seller');
		$this->load->model('multiseller/order');
        $this->load->model('account/order');
        $this->load->model('catalog/product');
        $this->load->model('multiseller/product');

        $data['orders'] = array();

        $orders = array();

        if (isset($this->request->post['selected'])) {
            $orders = $this->request->post['selected'];
        } elseif (isset($this->request->get['order_id'])) {
            $orders[] = $this->request->get['order_id'];
        }

        $seller_info = $this->model_multiseller_seller->getSeller($this->customer->getId());
        $seller_name = $seller_info['store_name'];

        foreach ($orders as $order_id) {
            $order_info = $this->model_multiseller_order->getOrder($order_id);

            // Make sure there is a shipping method
            if ($order_info && $order_info['shipping_code']) {
                $store_address = $this->config->get('config_address');
                $store_email = $this->config->get('config_email');
                $store_telephone = $this->config->get('config_telephone');
                $store_fax = $this->config->get('config_fax');

                if ($order_info['invoice_no']) {
                    $invoice_no = $order_info['invoice_prefix'].$order_info['invoice_no'];
                } else {
                    $invoice_no = '';
                }

                $shipping_address = address_format($order_info, $order_info['shipping_address_format'], 'shipping');

                // Shipping
                $filter_data = array(
                    'sort' => 'cf.sort_order',
                    'order' => 'ASC',
                );

                $this->load->model('account/custom_field');
                $custom_fields = $this->model_account_custom_field->getCustomFields($filter_data);
                $data['shipping_custom_fields'] = array();

                foreach ($custom_fields as $custom_field) {
                    if ($custom_field['location'] == 'address' && isset($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
                        if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
                            $custom_field_value_info = $this->model_account_custom_field->getCustomFieldValue($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

                            if ($custom_field_value_info) {
                                $data['shipping_custom_fields'][] = array(
                                    'name' => $custom_field['name'],
                                    'value' => $custom_field_value_info['name'],
                                    'sort_order' => $custom_field['sort_order'],
                                );
                            }
                        }

                        if ($custom_field['type'] == 'checkbox' && is_array($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
                            foreach ($order_info['shipping_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
                                $custom_field_value_info = $this->model_account_custom_field->getCustomFieldValue($custom_field_value_id);

                                if ($custom_field_value_info) {
                                    $data['shipping_custom_fields'][] = array(
                                        'name' => $custom_field['name'],
                                        'value' => $custom_field_value_info['name'],
                                        'sort_order' => $custom_field['sort_order'],
                                    );
                                }
                            }
                        }

                        if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
                            $data['shipping_custom_fields'][] = array(
                                'name' => $custom_field['name'],
                                'value' => $order_info['shipping_custom_field'][$custom_field['custom_field_id']],
                                'sort_order' => $custom_field['sort_order'],
                            );
                        }
                    }
                }
                foreach ($data['shipping_custom_fields'] as $custom_field) {
                    $shipping_address .= '('.$custom_field['name'].':'.$custom_field['value'].')';
                }

                $this->load->model('tool/upload');

                $product_data = array();

                $products = $this->model_multiseller_order->getOrderProducts($order_id);
                $product_total = 0;

                foreach ($products as $product) {
                    $option_weight = 0;

                    $product_info = $this->model_catalog_product->getProduct($product['product_id']);

                    if ($product_info) {
                        $option_data = array();

                        $options = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

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
                                'name' => $option['name'],
                                'value' => $value,
                            );

                            $product_option_value_info = $this->model_multiseller_product->getProductOptionValue($product['product_id'], $option['product_option_value_id']);

                            if ($product_option_value_info) {
                                if ($product_option_value_info['weight_prefix'] == '+') {
                                    $option_weight += $product_option_value_info['weight'];
                                } elseif ($product_option_value_info['weight_prefix'] == '-') {
                                    $option_weight -= $product_option_value_info['weight'];
                                }
                            }
                        }

                        if ($variantData = Models\Order\Product::find($product['order_product_id'])->getVariantLabels()) {
                            $option_data = array_merge($option_data, $variantData);
                        }

                        $product_data[] = array(
                            'name' => $product_info['name'],
                            'model' => $product_info['model'],
                            'option' => $option_data,
                            'quantity' => $product['quantity'],
                            'price' => $product['price'],
                            'location' => $product_info['location'],
                            'sku' => $product_info['sku'],
                            'upc' => $product_info['upc'],
                            'ean' => $product_info['ean'],
                            'jan' => $product_info['jan'],
                            'isbn' => $product_info['isbn'],
                            'mpn' => $product_info['mpn'],
                            'weight' => $this->weight->format(($product_info['weight'] + (float)$option_weight) * $product['quantity'], $product_info['weight_class_id'], $this->language->get('decimal_point'), $this->language->get('thousand_point')),
                        );
                    }
                    $product_total += $product['quantity'] * $product['price'];
                }

                $sub_order_total = $this->model_multiseller_order->getSubOrderTotals($order_id);
                $order_total_fee = 0;
                foreach ($sub_order_total as $value) {
                    $order_total_fee += $value['value'];
                }
                $suborder_total = $product_total + $order_total_fee;
                $data['orders'][] = array(
                    'order_id' => $order_id,
                    'seller_name' => $seller_name,
                    'invoice_no' => $invoice_no,
                    'date_added' => date($this->language->get('date_format_short'), strtotime($order_info['date_added'])),
                    'store_name' => $order_info['store_name'],
                    'store_url' => rtrim($order_info['store_url'], '/'),
                    'store_address' => nl2br($store_address),
                    'store_email' => $store_email,
                    'store_telephone' => $store_telephone,
                    'store_fax' => $store_fax,
                    'email' => $order_info['email'],
                    'telephone' => $order_info['telephone'],
                    'shipping_name' => $order_info['shipping_fullname'],
                    'shipping_company' => $order_info['shipping_company'],
                    'shipping_address' => $shipping_address,
                    'shipping_method' => $order_info['shipping_method'],
                    'product' => $product_data,
                    'order_total_fee' => $this->currency->format($order_total_fee, $order_info['currency_code']),
                    'product_total' => $this->currency->format($product_total, $order_info['currency_code']),
                    'order_total' => $this->currency->format($suborder_total, $order_info['currency_code']),
                    'comment' => nl2br($order_info['comment']),
                );
            }
        }

        $this->response->setOutput($this->load->view('seller/order_shipping', $data));
    }
}
