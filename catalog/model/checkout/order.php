<?php
class ModelCheckoutOrder extends Model {
	public function addOrder($data) {
		$order_sn 				= date('YmdHis',time()) . '1' . random_string(10);
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET order_sn = '" . $order_sn . "',invoice_prefix = '" . $this->db->escape((string)$data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape((string)$data['store_name']) . "', store_url = '" . $this->db->escape((string)$data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', fullname = '" . $this->db->escape((string)$data['fullname']) . "', email = '" . $this->db->escape((string)$data['email']) . "', telephone = '" . $this->db->escape((string)$data['telephone']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', payment_fullname = '" . $this->db->escape((string)$data['payment_fullname']) . "', payment_telephone = '" . $this->db->escape((string)$data['payment_telephone']) . "', payment_company = '" . $this->db->escape((string)$data['payment_company']) . "', payment_address_1 = '" . $this->db->escape((string)$data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape((string)$data['payment_address_2']) . "', payment_city = '" . $this->db->escape((string)$data['payment_city']) . "', payment_city_id = '" . $this->db->escape((string)$data['payment_city_id']) . "', payment_postcode = '" . $this->db->escape((string)$data['payment_postcode']) . "', payment_country = '" . $this->db->escape((string)$data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_county = '" . $this->db->escape((string)$data['payment_county']) . "', payment_county_id = '" . (int)$data['payment_county_id'] . "', payment_zone = '" . $this->db->escape((string)$data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape((string)$data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(isset($data['payment_custom_field']) ? json_encode($data['payment_custom_field']) : '') . "', payment_method = '" . $this->db->escape((string)$data['payment_method']) . "', payment_code = '" . $this->db->escape((string)$data['payment_code']) . "', shipping_fullname = '" . $this->db->escape((string)$data['shipping_fullname']) . "', shipping_telephone = '" . $this->db->escape((string)$data['shipping_telephone']) . "',shipping_company = '" . $this->db->escape((string)$data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape((string)$data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape((string)$data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape((string)$data['shipping_city']) . "', shipping_city_id = '" . $this->db->escape((string)$data['shipping_city_id']) . "', shipping_postcode = '" . $this->db->escape((string)$data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape((string)$data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_county = '" . $this->db->escape((string)$data['shipping_county']) . "', shipping_county_id = '" . (int)$data['shipping_county_id'] . "', shipping_zone = '" . $this->db->escape((string)$data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape((string)$data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(isset($data['shipping_custom_field']) ? json_encode($data['shipping_custom_field']) : '') . "', shipping_method = '" . $this->db->escape((string)$data['shipping_method']) . "', shipping_code = '" . $this->db->escape((string)$data['shipping_code']) . "', comment = '" . $this->db->escape((string)$data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', marketing_id = '" . (int)$data['marketing_id'] . "', tracking = '" . $this->db->escape((string)$data['tracking']) . "', language_id = '" . (int)$data['language_id'] . "', currency_id = '" . (int)$data['currency_id'] . "', currency_code = '" . $this->db->escape((string)$data['currency_code']) . "', currency_value = '" . (float)$data['currency_value'] . "', ip = '" . $this->db->escape((string)$data['ip']) . "', forwarded_ip = '" .  $this->db->escape((string)$data['forwarded_ip']) . "', user_agent = '" . $this->db->escape((string)$data['user_agent']) . "', accept_language = '" . $this->db->escape((string)$data['accept_language']) . "', date_added = NOW(), date_modified = NOW()");

		$order_id = $this->db->getLastId();

		// Pickup
        if (isset($data['pickup_id'])) {
            $this->load->model('localisation/pickup');
            $pickup_info = $this->model_localisation_pickup->getPickup($data['pickup_id']);
            if ($pickup_info) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "order_pickup` 
                                  SET order_id = '" . $order_id . "', pickup_id = '" . $data['pickup_id'] . "', name = '" . $this->db->escape((string)$pickup_info['name']) . "', address = '" . $this->db->escape((string)$pickup_info['address']) . "', country_id = '" . (int)$pickup_info['country_id'] . "', zone_id = '" . (int)$pickup_info['zone_id'] . "', telephone = '" . $this->db->escape((string)$pickup_info['telephone']) . "', geocode = '" . $this->db->escape((string)$pickup_info['geocode']) . "', open = '" . $this->db->escape((string)$pickup_info['open']) . "', comment = '" . $this->db->escape((string)$pickup_info['comment']) . "'");
            }
        }

		// Products
		if (isset($data['products'])) {
			foreach ($data['products'] as $product) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "', sku = '" . $this->db->escape($product['sku']) . "',image = '" . $this->db->escape($product['image']) . "'");

				$order_product_id = $this->db->getLastId();
				// flash sale
                if($this->config->get('module_flash_sale_status')) {
                    $module_flash_sale_products = $this->config->get('module_flash_sale_products');
                    if($module_flash_sale_products) {
                        foreach($module_flash_sale_products as $module_flash_sale_product) {
                            if($module_flash_sale_product['product_id'] == (int)$product['product_id']) {
                                $this->db->query("insert into " . DB_PREFIX . "flash_sale_product set product_id=" . (int)$product['product_id'] . ", count=" . (int)$product['quantity'] . ", order_id=" . (int)$order_id . ", date_added = NOW()");
                            }
                        }
                    }
                }

				foreach ($product['option'] as $option) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
				}

                if ($product['variant']) {
                    \Models\Order\Product::find($order_product_id)->createOrderVariants($product['variant']);
                }

				// Update product sales number
				$this->db->query("UPDATE " . DB_PREFIX . "product SET sales = (sales + " . (int)$product['quantity'] . ") WHERE product_id = '" . (int)$product['product_id'] . "'");
			}
		}

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		// Vouchers
		if (isset($data['vouchers'])) {
			foreach ($data['vouchers'] as $voucher) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($voucher['description']) . "', code = '" . $this->db->escape($voucher['code']) . "', from_name = '" . $this->db->escape($voucher['from_name']) . "', from_email = '" . $this->db->escape($voucher['from_email']) . "', to_name = '" . $this->db->escape($voucher['to_name']) . "', to_email = '" . $this->db->escape($voucher['to_email']) . "', voucher_theme_id = '" . (int)$voucher['voucher_theme_id'] . "', message = '" . $this->db->escape($voucher['message']) . "', amount = '" . (float)$voucher['amount'] . "'");

				$order_voucher_id = $this->db->getLastId();

				$voucher_id = $this->model_extension_total_voucher->addVoucher($order_id, $voucher);

				$this->db->query("UPDATE " . DB_PREFIX . "order_voucher SET voucher_id = '" . (int)$voucher_id . "' WHERE order_voucher_id = '" . (int)$order_voucher_id . "'");
			}
		}

		// Recharges
		if (isset($data['recharges'])) {
			foreach ($data['recharges'] as $recharge) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_recharge SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($recharge['description']) . "', customer_id = '" . $this->db->escape($recharge['customer_id']) . "', message = '" . $this->db->escape($recharge['message']) . "', amount = '" . (float)$recharge['amount'] . "'");

				$order_voucher_id = $this->db->getLastId();
			}
		}

		// Totals
		if (isset($data['totals'])) {

			$this->load->model('marketing/coupon');

			foreach ($data['totals'] as $total) {
				$this->setCouponStatus($total);
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
			}
		}

		return $order_id;
	}

	public function editOrder($order_id, $data) {
		// Void the order first
		$this->addOrderHistory($order_id, 0);

		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape((string)$data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape((string)$data['store_name']) . "', store_url = '" . $this->db->escape((string)$data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', fullname = '" . $this->db->escape((string)$data['fullname']) . "', email = '" . $this->db->escape((string)$data['email']) . "', telephone = '" . $this->db->escape((string)$data['telephone']) . "', custom_field = '" . $this->db->escape(json_encode($data['custom_field'])) . "', payment_fullname = '" . $this->db->escape((string)$data['payment_fullname']) . "', payment_telephone = '" . $this->db->escape((string)array_get($data, 'payment_telephone')) . "', payment_company = '" . $this->db->escape((string)$data['payment_company']) . "', payment_address_1 = '" . $this->db->escape((string)$data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape((string)$data['payment_address_2']) . "', payment_city = '" . $this->db->escape((string)$data['payment_city']) . "', payment_city_id = '" . $this->db->escape((string)array_get($data, 'payment_city_id')) . "', payment_postcode = '" . $this->db->escape((string)$data['payment_postcode']) . "', payment_country = '" . $this->db->escape((string)$data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_county = '" . $this->db->escape((string)array_get($data, 'payment_county')) . "', payment_county_id = '" . (int)array_get($data, 'payment_county_id') . "', payment_zone = '" . $this->db->escape((string)$data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape((string)$data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(json_encode($data['payment_custom_field'])) . "', payment_method = '" . $this->db->escape((string)$data['payment_method']) . "', payment_code = '" . $this->db->escape((string)$data['payment_code']) . "', shipping_fullname = '" . $this->db->escape((string)$data['shipping_fullname']) . "', shipping_telephone = '" . $this->db->escape((string)array_get($data, 'shipping_telephone')) . "', shipping_company = '" . $this->db->escape((string)$data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape((string)$data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape((string)$data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape((string)$data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape((string)$data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape((string)$data['shipping_country']) . "', shipping_country_id = '" . (int)array_get($data, 'shipping_country_id') . "', shipping_county = '" . $this->db->escape((string)array_get($data, 'shipping_county')) . "', shipping_county_id = '" . (int)array_get($data, 'shipping_county_id') . "', shipping_zone = '" . $this->db->escape((string)$data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape((string)$data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(json_encode($data['shipping_custom_field'])) . "', shipping_method = '" . $this->db->escape((string)$data['shipping_method']) . "', shipping_code = '" . $this->db->escape((string)$data['shipping_code']) . "', comment = '" . $this->db->escape((string)$data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "'");

		// Products
		if (isset($data['products'])) {
			foreach ($data['products'] as $product) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");

				$order_product_id = $this->db->getLastId();

				foreach ($product['option'] as $option) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
				}

                if ($product['variant']) {
                    \Models\Order\Product::find($order_product_id)->createOrderVariants($product['variant']);
                }
			}
		}

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		$this->model_extension_total_voucher->disableVoucher($order_id);

		// Vouchers
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

		if (isset($data['vouchers'])) {
			foreach ($data['vouchers'] as $voucher) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($voucher['description']) . "', code = '" . $this->db->escape($voucher['code']) . "', from_name = '" . $this->db->escape($voucher['from_name']) . "', from_email = '" . $this->db->escape($voucher['from_email']) . "', to_name = '" . $this->db->escape($voucher['to_name']) . "', to_email = '" . $this->db->escape($voucher['to_email']) . "', voucher_theme_id = '" . (int)$voucher['voucher_theme_id'] . "', message = '" . $this->db->escape($voucher['message']) . "', amount = '" . (float)$voucher['amount'] . "'");

				$order_voucher_id = $this->db->getLastId();

				$voucher_id = $this->model_extension_total_voucher->addVoucher($order_id, $voucher);

				$this->db->query("UPDATE " . DB_PREFIX . "order_voucher SET voucher_id = '" . (int)$voucher_id . "' WHERE order_voucher_id = '" . (int)$order_voucher_id . "'");
			}
		}

          // Recharges
        $this->db->query("DELETE FROM " . DB_PREFIX . "order_recharge WHERE order_id = '" . (int)$order_id . "'");

        if (isset($data['recharges'])) {
            foreach ($data['recharges'] as $recharge) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_recharge SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($recharge['description']) . "', customer_id = '" . $this->db->escape($recharge['customer_id']) . "', message = '" . $this->db->escape($recharge['message']) . "', amount = '" . (float)$recharge['amount'] . "'");

                $order_recharge_id = $this->db->getLastId();
            }
        }

		// Totals
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");

		if (isset($data['totals'])) {
			foreach ($data['totals'] as $total) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
			}
		}
	}

	public function deleteOrder($order_id) {
		// Void the order first
		$this->addOrderHistory($order_id, 0);

		$this->db->query("DELETE FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_recharge` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE `or`, ort FROM `" . DB_PREFIX . "order_recurring` `or`, `" . DB_PREFIX . "order_recurring_transaction` `ort` WHERE order_id = '" . (int)$order_id . "' AND ort.order_recurring_id = `or`.order_recurring_id");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_transaction` WHERE order_id = '" . (int)$order_id . "'");

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		$this->model_extension_total_voucher->disableVoucher($order_id);
	}

	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT *, (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = o.language_id) AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

		if ($order_query->num_rows) {
			$country_query = $this->db->query("SELECT `iso_code_2`,`iso_code_3` FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			$country_query = $this->db->query("SELECT `iso_code_2`,`iso_code_3` FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
			} else {
				$language_code = $this->config->get('config_language');
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'order_sn'                => $order_query->row['order_sn'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'fullname'               => $order_query->row['fullname'],
				'email'                   => $order_query->row['email'],
				'telephone'               => $order_query->row['telephone'],
				'custom_field'            => json_decode($order_query->row['custom_field'], true),
				'payment_fullname'       => $order_query->row['payment_fullname'],
                'payment_telephone'       => $order_query->row['payment_telephone'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
                'payment_city_id'         => $order_query->row['payment_city_id'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
                'payment_county_id'      => $order_query->row['payment_county_id'],
                'payment_county'         => $order_query->row['payment_county'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_custom_field'    => json_decode($order_query->row['payment_custom_field'], true),
				'payment_method'          => $order_query->row['payment_method'],
				'payment_code'            => $order_query->row['payment_code'],
				'shipping_fullname'      => $order_query->row['shipping_fullname'],
                'shipping_telephone'      => $order_query->row['shipping_telephone'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
                'shipping_city_id'        => $order_query->row['shipping_city_id'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
                'shipping_county_id'      => $order_query->row['shipping_county_id'],
                'shipping_county'         => $order_query->row['shipping_county'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
				'shipping_method'         => $order_query->row['shipping_method'],
				'shipping_code'           => $order_query->row['shipping_code'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'order_status_id'         => $order_query->row['order_status_id'],
				'order_status'            => $order_query->row['order_status'],
				'affiliate_id'            => $order_query->row['affiliate_id'],
				'commission'              => $order_query->row['commission'],
				'language_id'             => $order_query->row['language_id'],
				'language_code'           => $language_code,
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'ip'                      => $order_query->row['ip'],
				'forwarded_ip'            => $order_query->row['forwarded_ip'],
				'user_agent'              => $order_query->row['user_agent'],
				'accept_language'         => $order_query->row['accept_language'],
				'date_added'              => $order_query->row['date_added'],
				'date_modified'           => $order_query->row['date_modified']
			);
		} else {
			return false;
		}
	}

    public function getOrderProducts($order_id)
    {
        $cache_key      = 'order_product_' . (int)$order_id . '.getOrderProducts.by.order_id';
        $result         = $this->cache->get($cache_key);

        if ($result && is_array($result))  return $result;

        $query          = $this->db->query("SELECT op.*, p.image FROM " . DB_PREFIX . "order_product op LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id) WHERE op.order_id = '" . (int)$order_id . "'");

        $result = $query->rows;

        $this->cache->set($cache_key, $result);
        
        return $result;
    }

	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

		return $query->rows;
	}

	public function getOrderVouchers($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderRecharges($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_recharge WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false)
	{
		$order_info = $this->getOrder($order_id);

		if ($order_info)
		{
			// Fraud Detection
			$this->load->model('account/customer');
			$this->load->language('checkout/checkout');

			// If current order status is not processing or complete but new status is processing or complete then commence completing the order
			$status1 	= array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'));
			if (!in_array($order_info['order_status_id'], $status1) && in_array($order_status_id, $status1))
			{
				// Redeem coupon, vouchers and reward points
				$order_totals = $this->getOrderTotals($order_id);

				foreach ($order_totals as $order_total)
				{
					$this->load->model('extension/total/' . $order_total['code']);

					if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'confirm'))
					{
						$fraud_status_id = $this->{'model_extension_total_' . $order_total['code']}->confirm($order_info, $order_total);
						if ($fraud_status_id) {
							$order_status_id = $fraud_status_id;
						}
					}
				}

				// Stock subtraction
				$order_products = $this->getOrderProducts($order_id);

				foreach ($order_products as $order_product)
				{	
					$sql = "UPDATE " . get_tabname('product') . " SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'";
					$this->db->query($sql);
				}
			}

			// Update the DB with the new statuses
			$sql = "UPDATE " . get_tabname('order') . " SET order_status_id = '".(int)$order_status_id."', date_modified = NOW() WHERE order_id = '".(int)$order_id."'";
			$this->db->query($sql);

			$sql = "INSERT INTO " . get_tabname('order_history') . " SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()";
			$this->db->query($sql);

			if (in_array($order_info['order_status_id'], $status1) && in_array($order_status_id, $status1))
			{
				// Restock
				$order_products = $this->getOrderProducts($order_id);

				foreach($order_products as $order_product)
				{
					$sql 	= "UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'";
					$this->db->query($sql);
				}

				// Remove coupon, vouchers and reward points history
				$order_totals = $this->getOrderTotals($order_id);

				foreach ($order_totals as $order_total)
				{
					$this->load->model('extension/total/' . $order_total['code']);

					if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'unconfirm'))
					{
						$this->{'model_extension_total_' . $order_total['code']}->unconfirm($order_id);
					}
				}
			}

			$order_products      = $this->getOrderProducts($order_id);
            if (!empty($order_products)) {
                foreach ($order_products as $value) {
                    $this->cache->delete('product.id' . $value['product_id']);
                }
            }
		}
	}

	//获取定单信息用来生成地址信息
	public function getOrderByOrderSnUseAddressInfoForMs($order_sn,$order_type)
	{
		if (!in_array($order_type, [1,2]))  return [];

		$shipping_ 		= ',shipping_fullname,shipping_telephone,shipping_company,shipping_address_1,shipping_address_2,shipping_city,shipping_city_id,shipping_postcode,shipping_country,shipping_country_id,shipping_zone,shipping_zone_id,shipping_county,shipping_county_id,shipping_address_format,shipping_method,shipping_code';

		$fields 		= format_find_field('order_id,currency_code,currency_value,customer_id,language_id,email' . $shipping_,'o');

		if ($order_type == 1) {
			$fields 		.= ',' . format_find_field('order_sn,total','o');
			$sql 			= "SELECT " . $fields . " FROM `" . DB_PREFIX . "order` o WHERE o.order_sn = '" . (string)$order_sn . "'";
		}else{
			$fields 		.= ',' . format_find_field('seller_id,order_sn,total','mssu');
			$sql 			= "SELECT " . $fields . " FROM `" . DB_PREFIX . "ms_suborder` mssu LEFT JOIN  `" . DB_PREFIX . "order` o ON (o.order_id = mssu.order_id) WHERE mssu.order_sn = '" . (string)$order_sn . "' AND o.order_status_id > '0'";
		}

		$order_query = $this->db->query($sql);
		
		return $order_query->num_rows > 0 ? $order_query->row : [];
	}

	//获取定单信息用来生成地址信息
	public function setOrderByOrderSnUseAddressInfoForMs($order_id,$address)
	{
		if ($order_id <= 0 || empty($address))  return;

		$sql 	= "UPDATE " . get_tabname('order') ." SET shipping_fullname = '" . $this->db->escape($address['fullname']) . 
		"',shipping_telephone = '" . $this->db->escape($address['telephone']) . 
		"',shipping_address_1 = '" . $this->db->escape($address['address_1']) . 
		"',shipping_address_2 = '" . $this->db->escape($address['address_2']) . 
		"',shipping_city = '" . $this->db->escape($address['city']) . 
		"',shipping_postcode = '" . $this->db->escape($address['postcode']) . 
		"',shipping_zone_id = '" . (int)$address['zone_id'] . 
		"',shipping_country_id = '" . (int)$address['country_id'] . 
		"' WHERE order_id = '" . (int)$order_id . "'";

		$this->db->query($sql);
	}

	//获取点单信息用来生成支付信息
	public function getOrderByOrderSnUsePayInfoForMs($order_sn,$order_type)
	{
		if (!in_array($order_type, [1,2]))  return [];

		$shipping_ 		= ',shipping_fullname,shipping_telephone,shipping_company,shipping_address_1,shipping_address_2,shipping_city,shipping_city_id,shipping_postcode,shipping_country,shipping_country_id,shipping_zone,shipping_zone_id,shipping_county,shipping_county_id,shipping_address_format,shipping_method,shipping_code';
		$payment_ 		= ',payment_fullname,payment_country_id';

		$fields 		= format_find_field('order_id,currency_code,currency_value,customer_id,language_id,email' . $shipping_ . $payment_,'o');

		if ($order_type == 1) {
			$fields 		.= ',' . format_find_field('order_sn,total','o');
			$sql 			= "SELECT " . $fields . ", (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = o.language_id) AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_sn = '" . (string)$order_sn . "'";
		}else{
			$fields 		.= ',' . format_find_field('seller_id,order_sn,total','mssu');
			$sql 			= "SELECT " . $fields . ", (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = mssu.order_status_id AND os.language_id = o.language_id) AS order_status FROM `" . DB_PREFIX . "ms_suborder` mssu LEFT JOIN  `" . DB_PREFIX . "order` o ON (o.order_id = mssu.order_id) WHERE mssu.order_sn = '" . (string)$order_sn . "' AND o.order_status_id > '0'";
		}

		$order_query = $this->db->query($sql);

		if ($order_query->num_rows) {

			$country_query = $this->db->query("SELECT * FROM ".get_tabname('country')." WHERE country_id = '".(int)$order_query->row['payment_country_id']."'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'seller_id'               => isset($order_query->row['seller_id']) ? $order_query->row['seller_id'] : 0,
				'order_type'              => $order_type,
				'order_sn'                => $order_query->row['order_sn'],
				'total'                	  => $order_query->row['total'],
				'email'                	  => $order_query->row['email'],
				'payment_fullname'        => $order_query->row['payment_fullname'],
				'currency_value'          => $order_query->row['currency_value'],
				'currency_code'           => $order_query->row['currency_code'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'customer_id'             => $order_query->row['customer_id'],
				'language_id'             => $order_query->row['language_id'],
			);
		}

		return [];
	}

	public function getOrderByOrderSnForMs($order_sn,$order_type = 0)
	{
		if (!in_array($order_type, [1,2]))  return [];

		$fields 		= format_find_field('order_id,order_sn,total,customer_id,affiliate_id,commission','o');
		if ($order_type == 1) {
			$fields 		.= ',' . format_find_field('order_status_id','o');
			$sql 			= "SELECT " . $fields . ", (SELECT os.name FROM " . get_tabname('order_status') . " os WHERE os.order_status_id = o.order_status_id AND os.language_id = o.language_id) AS order_status FROM " . get_tabname('order') . " o WHERE o.order_sn = '" . (string)$order_sn . "'";
		}else{
			$fields 		.= ',' . format_find_field('suborder_id,seller_id,order_sn,total,order_status_id','mssu');
			$sql 			= "SELECT " . $fields . ", (SELECT os.name FROM " . get_tabname('order_status') . " os WHERE os.order_status_id = mssu.order_status_id AND os.language_id = o.language_id) AS order_status FROM " . get_tabname('ms_suborder') . " mssu LEFT JOIN " . get_tabname('order') . " o ON (o.order_id = mssu.order_id) WHERE mssu.order_sn = '" . (string)$order_sn . "' AND o.order_status_id > '0'";
		}

		$order_query 	= $this->db->query($sql);
		$ext_data 		= [];

		return array_merge($order_query->row,$ext_data);
	}

	public function updateSubOrderPayCode($order_sn,$paycode)
	{
		if (empty($order_sn) || empty($paycode))  return;

		$order_type 		= get_order_type($order_sn);
		$order_info 		= $this->getOrderByOrderSnForMs($order_sn,$order_type);
		$order_id 			= isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0;
		
		if ($order_info && $order_id > 0)
		{
			//如果是合并订单 需要添加所有子订单状态以及记录
			if ($order_type == 1) {
				$sql 	= "UPDATE " . get_tabname('ms_suborder') ." SET pay_code = '" . (string)$paycode . "' WHERE order_id = '" . (int)$order_id . "'";
			}else{
				$sql 	= "UPDATE " . get_tabname('ms_suborder') . " SET pay_code = '" . (string)$paycode . "' WHERE order_sn = '" . (string)$order_sn . "'";
			}

			$this->db->query($sql);
		}
	}

	public function addOrderHistoryForMs($order_sn, $order_status_id, $comment = '', $notify = false, $override = false)
	{
		$order_type 		= get_order_type($order_sn);
		$order_info 		= $this->getOrderByOrderSnForMs($order_sn,$order_type);
		$order_id 			= isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0;
		
		$query 				= $this->db->query("SELECT `order_id`,`seller_id` FROM " . get_tabname('ms_suborder') . " WHERE order_id = '" . (int)$order_id . "'");
		$suborder 			= $query->rows;

		if ($order_info && $order_id > 0)
		{
			foreach ($suborder as $subk => $subv) {
				$order_status[$subv['seller_id']] = $order_status_id;
			}

			// Fraud Detection
			$this->load->model('account/customer');
			$this->load->language('checkout/checkout');

			$customer_info 	= $this->model_account_customer->getCustomer($order_info['customer_id']);
			$safe 			= ($customer_info && $customer_info['safe']) ? true : false;

			//反欺诈暂时不做
			// Only do the fraud check if the customer is not on the safe list and the order status is changing into the complete or process order status
			/*if (!$safe && !$override && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Anti-Fraud
				$this->load->model('setting/extension');

				$extensions = $this->model_setting_extension->getExtensions('fraud');

				foreach ($extensions as $extension) {
					if ($this->config->get('fraud_' . $extension['code'] . '_status')) {
						$this->load->model('extension/fraud/' . $extension['code']);

						if (property_exists($this->{'model_extension_fraud_' . $extension['code']}, 'check')) {
							$fraud_status_id = $this->{'model_extension_fraud_' . $extension['code']}->check($order_info);

							if ($fraud_status_id) {
								$order_status_id = $fraud_status_id;
							}
						}
					}
				}
			}*/

			// If current order status is not processing or complete but new status is processing or complete then commence completing the order
			// 如果当前订单状态未处理或完成，但新状态正在处理或完成，则开始完成订单。
			if (!in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {

				foreach ($suborder as $subk => $subv) {
					$order_totals = $this->getOrderTotalsByOrderIdForMs($order_id,$subv['seller_id']);

					foreach ($order_totals as $order_total) {
						$this->load->model('extension/total/' . $order_total['code']);

						if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'confirm')) {
							// Confirm coupon, vouchers and reward points
							$fraud_status_id = $this->{'model_extension_total_' . $order_total['code']}->confirm($order_info, $order_total);

							// 如果优惠券、凭证和奖励点数上的余额不足以覆盖交易或已被使用，则返回欺诈订单状态
							if ($fraud_status_id) {
								$order_status[$subv['seller_id']] = $fraud_status_id;
							}
						}
					}

					// Stock subtraction
					$order_products = $this->getOrderProductsByOrderIdForMs($order_id,$subv['seller_id']);

					foreach ($order_products as $order_product) {
						$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

						$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

						foreach ($order_options as $order_option) {
							$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
						}
					}
				}

				// Add commission if sale is linked to affiliate referral.
				/*if ($order_info['affiliate_id'] && $this->config->get('config_affiliate_auto')) {
					$this->load->model('account/customer');

					if (!$this->model_account_customer->getTotalTransactionsByOrderId($order_id)) {
						$this->model_account_customer->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
					}
				}*/
			}

			//如果是合并订单 需要添加所有子订单状态以及记录
			if ($order_type == 1) {
				// Update the DB with the new statuses
				$sql1 	= "UPDATE " . get_tabname('ms_suborder') ." SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'";

				$sql2 	= '';
				
				if (!empty($suborder)) {
					$sql2 	= "INSERT INTO ". get_tabname('ms_suborder_history') . " (order_id,order_status_id,notify,seller_id,comment,date_added) VALUES ";
					foreach ($suborder as $key => $value) {
						$sql2 .=  "('".$order_id."','".(int)$order_status_id ."','".(int)$notify . "','".$value['seller_id']."','".$this->db->escape($comment)."',NOW()),";
					}

					$sql2 	= trim($sql2,',');
				}
				
				$sql3 	= "UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'";
			}else{
				// Update the DB with the new statuses
				$sql1 	= "UPDATE " . get_tabname('ms_suborder') . " SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_sn = '" . (string)$order_sn . "'";
				$sql2 	= "INSERT INTO " . get_tabname('ms_suborder_history') . " SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', seller_id = '" . (int)$order_info['seller_id'] . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()";
			}

			$this->db->query($sql1);

			if (!empty($sql2)) {
				$this->db->query($sql2);
			}

			if ($order_type == 1) {
				$noPay = $this->db->query('SELECT COUNT(*) AS total FROM '.get_tabname('ms_suborder')." WHERE order_id = '".(int) $order_id."' AND order_status_id <= 1");
				if ($noPay->row['total'] <= 0) {
					$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
				}
			}else{
					$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
			}
		
            // If current order status is not complete but new status is complete then get the order's recharges and add the customer transaction balance
            // 如果当前订单状态不完整，但新状态已完成，则获取订单的充值，并添加客户事务余额 暂时不做
            /*if (!in_array($order_info['order_status_id'], array_merge($this->config->get('config_complete_status'), $this->config->get('config_paid_status')))
                  && in_array($order_status_id, array_merge($this->config->get('config_complete_status'), $this->config->get('config_paid_status')))) {
			
                $recharge_infos = $this->getOrderRecharges($order_id);

                foreach ($recharge_infos as $recharge_info) {
                    if (!$this->model_account_customer->getTotalTransactionsByOrderRechargeId($recharge_info['order_recharge_id'])) {
                        // 如果改条transaction记录是充值，只保存recharge_id，定好通过recharge_id来关联获取，以保证transaction表的的order_id代表的含义保持原生，不影响相关功能
                        $this->model_account_customer->addTransaction($order_info['customer_id'], $this->language->get('text_order_id') . ' #' . $order_id, $recharge_info['amount'], 0, $recharge_info['order_recharge_id']);
                    }
                }
            }*/

            // If current order status is complete but new status is not complete then remove the customer transaction balance
            // 如果当前订单状态已完成，但新状态未完成，则删除客户事务余额 暂时不做
            /*if (in_array($order_info['order_status_id'], array_merge($this->config->get('config_complete_status'), $this->config->get('config_paid_status')))
                  && !in_array($order_status_id, array_merge($this->config->get('config_complete_status'), $this->config->get('config_paid_status')))) {
                $recharge_infos = $this->getOrderRecharges($order_id);

                foreach ($recharge_infos as $recharge_info) {
                    $this->model_account_customer->deleteTransactionByOrderRechargeId($recharge_info['order_recharge_id']);
                }
            }*/

			// If old order status is the processing or complete status but new status is not then commence restock, and remove coupon, voucher and reward history
			// 如果旧订单状态是处理或完成状态，但新状态不是，则开始重新上锁，并删除优惠券、凭证和奖励历史
			if (in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && !in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {

				foreach ($suborder as $key => $value)
				{
					// Restock
					$order_products = $this->getOrderProductsByOrderIdForMs($order_id,$value['seller_id']);

					foreach($order_products as $order_product) {
						$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

						$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

						foreach ($order_options as $order_option) {
							$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
						}
					}

					// Remove coupon, vouchers and reward points history
					$order_totals = $this->getOrderTotalsByOrderIdForMs($order_id,$value['seller_id']);

					foreach ($order_totals as $order_total) {
						$this->load->model('extension/total/' . $order_total['code']);

						if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'unconfirm')) {
							$this->{'model_extension_total_' . $order_total['code']}->unconfirm($order_id);
						}
					}
				}

				// Remove commission if sale is linked to affiliate referral.
				/*if ($order_info['affiliate_id']) {
					$this->load->model('account/customer');

					$this->model_account_customer->deleteTransactionByOrderId($order_id);
				}*/
			}

			$order_products      = $this->getOrderProducts($order_id);
            if (!empty($order_products)) {
                foreach ($order_products as $value) {
                    $this->cache->delete('product.id' . $value['product_id']);
                }
            }
		}
	}

	public function getOrderProductsByOrderIdForMs($order_id = '',$seller_id = 0) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ms_order_product msop LEFT JOIN " . DB_PREFIX . "order_product op ON (op.order_product_id = msop.order_product_id) WHERE msop.order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$seller_id . "'");

		return $query->rows;
	}

	public function getOrderTotalsByOrderIdForMs($order_id = '',$seller_id = 0) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ms_order_total` msot LEFT JOIN " . DB_PREFIX . "order_total ot ON (ot.order_total_id = msot.order_total_id) WHERE ot.order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$seller_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getOrderProductMsByProductId($order_sn)
	{
		$order_type 		= intval(substr($order_sn, 14,1));
		if ($order_type !== 2)  return [];

		$fields 		= format_find_field('order_id,customer_id,affiliate_id,commission','o');
		$fields 		.= ',' . format_find_field('seller_id,order_sn,total,order_status_id','mssu');

		$sql 			= "SELECT " . $fields . ", (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = mssu.order_status_id AND os.language_id = o.language_id) AS order_status FROM `" . DB_PREFIX . "ms_suborder` mssu LEFT JOIN  `" . DB_PREFIX . "order` o ON (o.order_id = mssu.order_id) WHERE mssu.order_sn = '" . (string)$order_sn . "' AND o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0'";

		$order_query = $this->db->query($sql);

		$ext_data 		= [];

		return array_merge($order_query->row,$ext_data);
	}

	private function setCouponStatus($data = [])
	{
		if (!empty($data) && isset($data['code']) && $data['code'] === 'multiseller_coupon')
		{
			$total_id 		= isset($data['total_id']) ? (int)$data['total_id'] : 0;
			$this->model_marketing_coupon->setCouponStatus($total_id);
		}
	}
}
