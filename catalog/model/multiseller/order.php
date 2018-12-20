<?php
class ModelMultisellerOrder extends Model {
	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT o.*,mssu.order_status_id as morder_status_id,mssu.order_sn as ms_order_sn, (SELECT c.fullname FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status FROM `" . DB_PREFIX . "ms_suborder` mssu LEFT JOIN `" . DB_PREFIX . "order` o ON (o.order_id = mssu.order_id) WHERE o.order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$this->customer->getId() . "'");

		if ($order_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

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
				'order_sn'                => $order_query->row['ms_order_sn'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'customer'                => $order_query->row['customer'],
				'customer_group_id'       => $order_query->row['customer_group_id'],
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
                'payment_city_id'         => $order_query->row['payment_city_id'],
				'payment_city'            => $order_query->row['payment_city'],
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
                'shipping_city_id'        => $order_query->row['shipping_city_id'],
				'shipping_city'           => $order_query->row['shipping_city'],
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
				'order_status_id'         => $order_query->row['morder_status_id'],
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
			return;
		}
	}

	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT op.* FROM " . DB_PREFIX . "order_product op LEFT JOIN " . DB_PREFIX . "ms_order_product mop ON mop.order_product_id = op.order_product_id WHERE op.order_id = '" . (int)$order_id . "' AND mop.seller_id = '" . (int)$this->customer->getId() . "'");

		return $query->rows;
	}

	public function getOrders($data = array()) {
		$sql = "SELECT o.order_id, o.fullname AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = mo.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, mo.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o";
        $sql .= " LEFT JOIN `" . DB_PREFIX . "ms_suborder` mo ON mo.order_id = o.order_id AND seller_id = '" . (int)$this->customer->getId() . "'";

		if (!empty($data['filter_order_status'])) {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "mo.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} elseif (isset($data['filter_order_status_id']) && $data['filter_order_status_id'] !== '') {
			$sql .= " WHERE mo.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$sql .= " WHERE mo.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND o.fullname LIKE '%" . $this->db->escape((string)$data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(o.date_added) >= DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(o.date_added) <= DATE('" . $this->db->escape((string)$data['filter_date_modified']) . "')";
		}

        if (!empty($data['filter_total'])) {
            if (stripos($data['filter_total'], '-')) {
                $totals = explode('-', $data['filter_total']);
                $from_total = $totals[0];
                $to_total = $totals[1];
                $sql .= " AND mo.total >= '" . (float)$from_total . "' AND mo.total <= '" . (float)$to_total . "'";
            } else {
                $sql .= " AND mo.total = '" . (float)$data['filter_total'] . "'";
            }
        }

		$sort_data = array(
			'o.order_id',
			'customer',
			'order_status',
			'o.date_added',
			'o.date_modified',
			'o.total'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY o.order_id";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalOrders($data = array()) {
		$sql = "SELECT COUNT(o.order_id) AS total FROM `" . DB_PREFIX . "order` o";
		$sql .= " LEFT JOIN `" . DB_PREFIX . "ms_suborder` mo ON mo.order_id = o.order_id AND seller_id = '" . (int)$this->customer->getId() . "'";

		if (!empty($data['filter_order_status'])) {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "mo.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} elseif (isset($data['filter_order_status_id']) && $data['filter_order_status_id'] !== '') {
			$sql .= " WHERE mo.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$sql .= " WHERE mo.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND o.fullname LIKE '%" . $this->db->escape((string)$data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(date_added) >= DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(date_modified) <= DATE('" . $this->db->escape((string)$data['filter_date_modified']) . "')";
		}

        if (!empty($data['filter_total'])) {
            if (stripos($data['filter_total'], '-')) {
                $totals = explode('-', $data['filter_total']);
                $from_total = $totals[0];
                $to_total = $totals[1];
                $sql .= " AND mo.total >= '" . (float)$from_total . "' AND mo.total <= '" . (float)$to_total . "'";
            } else {
                $sql .= " AND mo.total = '" . (float)$data['filter_total'] . "'";
            }
        }

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

    public function addOrderShippingtrack($order_id, $data)
    {
        //增加快递单号和快递公司信息
        if ($data['tracking_code']) {
            $tracking_name = $this->getExpressNameByCode($data['tracking_code']);
            //更新订单状态 并 记录快递单号
            $this->db->query('INSERT INTO '.DB_PREFIX."order_shippingtrack SET order_id = '".(int) $order_id."', tracking_name = '".trim($tracking_name)."', tracking_code = '".trim($data['tracking_code'])."', tracking_number = '".trim($data['tracking_number'])."', comment = '".$this->db->escape($data['kd_comment'])."', seller_id = '" . (int)$this->customer->getId() . "', date_added = NOW()");
        }
    }

    public function getOrderShippingtracks($order_id, $start = 0, $limit = 10)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 10;
        }

        $query = $this->db->query('SELECT * FROM '.DB_PREFIX."order_shippingtrack WHERE order_id = '".(int) $order_id."' AND seller_id = '" . (int)$this->customer->getId() . "' ORDER BY date_added DESC LIMIT ".(int) $start.','.(int) $limit);

        return $query->rows;
    }

    public function getTotalOrderShippingtracks($order_id)
    {
        $query = $this->db->query('SELECT COUNT(*) AS total FROM '.DB_PREFIX."order_shippingtrack WHERE order_id = '".(int) $order_id."' AND seller_id = '" . (int)$this->customer->getId() . "'");

        return $query->row['total'];
    }

	//跟后台同名函数相同
    public function delOrderShippingtrack($id)
    {
        $this->db->query('DELETE FROM '.DB_PREFIX."order_shippingtrack WHERE id = '".(int) $id."'");
    }

	//跟后台同名函数相同
    public function getExpressNameByCode($code)
    {
        $kd_tracking_data = $this->config->get('module_express_tracking_data');

        foreach ($kd_tracking_data as $express) {
            if ($express['code'] == $code) {
                return $express['name'];
            }
        }

        return $code;
    }


	public function getSubOrderHistories($order_id, $start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT oh.date_added, os.name AS status, oh.comment, oh.notify FROM " . DB_PREFIX . "ms_suborder_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND oh.seller_id = '" . (int)$this->customer->getId() . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY oh.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalSubOrderHistories($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ms_suborder_history WHERE order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$this->customer->getId() . "'");

		return $query->row['total'];
	}

	public function getSuborder($order_id, $seller_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ms_suborder` WHERE order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$seller_id . "'");

		return $query->row;
	}

	public function getSuborders($order_id) {
		$query = $this->db->query("SELECT s.*, os.name AS order_status_name FROM `" . DB_PREFIX . "ms_suborder` s LEFT JOIN `" . DB_PREFIX . "order_status` os ON os.order_status_id = s.order_status_id WHERE s.order_id = '" . (int)$order_id . "' AND os.language_id = '" . $this->config->get('config_language_id') . "'");

		return $query->rows;
	}

	//跟后台同名函数相同
	public function getOrderSellers($order_id) {
		$query = $this->db->query("SELECT DISTINCT s.* FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "ms_order_product` mop ON mop.order_product_id = op.order_product_id LEFT JOIN `" . DB_PREFIX . "ms_seller` s ON s.seller_id = mop.seller_id WHERE op.order_id = '" . (int)$order_id . "'");

		return $query->rows;
    }

    public function getSubOrderTotals($order_id) {
        $query = $this->db->query("SELECT ot.* FROM " . DB_PREFIX . "order_total ot
                                    LEFT JOIN " . DB_PREFIX . "ms_order_total mot ON mot.order_total_id = ot.order_total_id
                                    WHERE ot.order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$this->customer->getId() . "' ORDER BY sort_order");

        return $query->rows;
    }

    public function getSellerIdByOrderProductId($order_product_id) {
		$query = $this->db->query("SELECT seller_id FROM " . DB_PREFIX . "ms_order_product WHERE order_product_id = '" . (int)$order_product_id . "'");

		return isset($query->row['seller_id']) ? $query->row['seller_id'] : 0;
    }



    public function getSuborderByCustomerIdForMs($order_id = 0,$seller_id = 0,$customer_id = 0)
    {
        $order_id       = (int)$order_id;
        $seller_id      = (int)$seller_id;
        $customer_id    = (int)$customer_id;

        if ( $order_id <= 0 ||  $seller_id <= 0 ||  $customer_id <= 0 )  return [];

        $fields         = format_find_field('order_id,date_added','o');
        $fields         .= ',' . format_find_field('suborder_id,order_sn,seller_id,total,order_status_id','mssu');

        $order_query = $this->db->query("SELECT " . $fields . " FROM `" . DB_PREFIX . "ms_suborder` mssu LEFT JOIN  `" . DB_PREFIX . "order` o ON (o.order_id = mssu.order_id) WHERE mssu.order_id = '" . $order_id . "'AND mssu.seller_id = '" . $seller_id . "' AND o.customer_id = '" . $customer_id . "' AND o.order_status_id > '0'");

        return $order_query->num_rows ? $order_query->row : [];
    }
}