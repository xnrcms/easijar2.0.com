<?php
class ModelAccountOrder extends Model {
    public function getOrder($order_id) {
        $order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND order_status_id > '0'");

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

            return array(
                'order_id'                => $order_query->row['order_id'],
                'invoice_no'              => $order_query->row['invoice_no'],
                'invoice_prefix'          => $order_query->row['invoice_prefix'],
                'store_id'                => $order_query->row['store_id'],
                'store_name'              => $order_query->row['store_name'],
                'store_url'               => $order_query->row['store_url'],
                'customer_id'             => $order_query->row['customer_id'],
                'fullname'               => $order_query->row['fullname'],
                'telephone'               => $order_query->row['telephone'],
                'email'                   => $order_query->row['email'],
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
                'shipping_method'         => $order_query->row['shipping_method'],
				'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
                'comment'                 => $order_query->row['comment'],
                'total'                   => $order_query->row['total'],
                'order_status_id'         => $order_query->row['order_status_id'],
                'language_id'             => $order_query->row['language_id'],
                'currency_id'             => $order_query->row['currency_id'],
                'currency_code'           => $order_query->row['currency_code'],
                'currency_value'          => $order_query->row['currency_value'],
                'date_modified'           => $order_query->row['date_modified'],
                'date_added'              => $order_query->row['date_added'],
                'ip'                      => $order_query->row['ip']
            );
        } else {
            return false;
        }
    }

    public function getOrders($start = 0, $limit = 20) {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 1;
        }

        $query = $this->db->query("SELECT o.order_id, o.fullname, os.name as status, o.order_status_id, o.date_added, o.total, o.currency_code, o.currency_value FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit);

        return $query->rows;
    }

    public function getOrderProduct($order_id, $order_product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

        return $query->row;
    }

    public function getOrderProducts($order_id) {
        $query = $this->db->query("SELECT op.*, p.image FROM " . DB_PREFIX . "order_product op LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id) WHERE op.order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    public function getOrderProductsNameForMs($order_id = 0, $seller_id = 0)
    {
        $order_id           = (int)$order_id;
        $seller_id          = (int)$seller_id;

        if ($order_id <= 0 )  return [];

        if ($order_id > 0 && $seller_id > 0) {
            $sql            = "SELECT op.`name` FROM " . DB_PREFIX . "ms_order_product mop LEFT JOIN " . DB_PREFIX . "order_product op ON (op.order_product_id = mop.order_product_id) WHERE op.order_id = '" . (int)$order_id . "' AND mop.seller_id = '" . $seller_id . "'";
        }else{
            $sql            = "SELECT `name` FROM " . DB_PREFIX . "order_product WHERE order_id = '" . $order_id . "'";
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getOrderOptions($order_id, $order_product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

        return $query->rows;
    }

    public function getOrderVouchers($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    public function getOrderTotals($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");

        return $query->rows;
    }

    public function getOrderHistories($order_id) {
        $query = $this->db->query("SELECT date_added, os.name AS status, oh.comment, oh.notify FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY oh.date_added");

        return $query->rows;
    }

    public function getTotalOrders() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o WHERE customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "'");

        return $query->row['total'];
    }

    public function getTotalOrderProductsByOrderId($order_id,$seller_id = 0) {
        $seller_map     = '';
        $seller_id      = (int)$seller_id;
        if ($seller_id > 0) {
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . get_tabname('ms_order_product') . "msop LEFT JOIN " . get_tabname('order_product') . " op ON (msop.order_product_id = op.order_product_id) WHERE msop.order_id = '" . (int)$order_id . "' AND msop.seller_id = " . $seller_id);
        }else{
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
        }

        return $query->row['total'];
    }

    public function getTotalOrderVouchersByOrderId($order_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");

        return $query->row['total'];
    }

    public function getUnpaidOrders($start = 0, $limit = 20)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 1;
        }

        $unpaid_status = $this->config->get('config_unpaid_status_id');
        $query = $this->db->query("SELECT o.order_id, o.fullname, o.shipping_fullname, os.name as status, o.order_status_id, o.date_added, o.total, o.currency_code, o.currency_value FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id = '$unpaid_status' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit);

        return $query->rows;
    }


    public function getTotalUnpaidOrders()
    {
        $unpaid_status = $this->config->get('config_unpaid_status_id');
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE customer_id = '" . (int)$this->customer->getId() . "' AND order_status_id = '$unpaid_status'");

        return $query->row['total'];
    }

    public function getShippedOrders($start = 0, $limit = 20)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 1;
        }

        $query = $this->db->query("SELECT o.order_id, o.fullname, o.shipping_fullname, os.name as status, o.order_status_id, o.date_added, o.total, o.currency_code, o.currency_value FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id = '" . $this->config->get('config_shipped_status_id') . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit);

        return $query->rows;
    }

    public function getTotalShippedOrders()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE customer_id = '" . (int)$this->customer->getId() . "' AND order_status_id = '" . $this->config->get('config_shipped_status_id') . "'");

        return $query->row['total'];
    }

    public function getUnshippedOrders($start = 0, $limit = 20)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 1;
        }

        $unshipped_status = $this->config->get('config_paid_status');
        if ($this->config->get('payment_cod_status')) {
            $unshipped_status[] = $this->config->get('payment_cod_order_status_id');
        }

        $query = $this->db->query("SELECT o.order_id, o.fullname, o.shipping_fullname, os.name as status, o.order_status_id, o.date_added, o.total, o.currency_code, o.currency_value FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id in ('" . implode("','",$unshipped_status) . "') AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit);

        return $query->rows;
    }

    public function getTotalUnshippedOrders()
    {
        if ($this->config->get('payment_cod_status')) {
            $unshipped_status[] = $this->config->get('payment_cod_order_status_id');
        }
        $unshipped_status = $this->config->get('config_paid_status');
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE customer_id = '" . (int)$this->customer->getId() . "' AND order_status_id in ('" . implode("','",$unshipped_status) . "')");

        return $query->row['total'];
    }

    public function paymentTimeout()
    {
        $unpaid_status = $this->config->get('config_unpaid_status_id');
        $minute = $this->config->get('config_unpaid_time');
        $cancelled_status = $this->config->get('config_cancelled_status_id');

        $sql = "SELECT o.order_id, o.order_status_id FROM `" . DB_PREFIX . "order` o WHERE o.order_status_id = '$unpaid_status' AND o.date_added < date_sub(now(),INTERVAL $minute MINUTE )";

        $query = $this->db->query($sql);

        if ($query->rows) {
            $this->load->model('checkout/order');
            foreach ($query->rows as $unpaid_order) {
                $this->model_checkout_order->addOrderHistory($unpaid_order['order_id'], $cancelled_status, '', true);
            }
        }
    }

    public function autoCompleteOrder()
    {
        $shipped_status = $this->config->get('config_shipped_status_id');
        $days = $this->config->get('config_complete_time');
        $complete_status = $this->config->get('config_complete_status');
        $complete_status = $complete_status[0];

        $sql = "SELECT o.order_id, o.order_status_id FROM `" . DB_PREFIX . "order` o WHERE o.order_status_id = '$shipped_status' AND o.date_modified < date_sub(now(),INTERVAL $days DAY )";

        $query = $this->db->query($sql);

        if ($query->rows) {
            $this->load->model('checkout/order');
            foreach ($query->rows as $shipped_order) {
                $this->model_checkout_order->addOrderHistory($shipped_order['order_id'], $complete_status, '', true);
            }
        }
    }

    public function getTotalOrderRechargesByOrderId($order_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order_recharge` WHERE order_id = '" . (int)$order_id . "'");

        return $query->row['total'];
    }

    public function getOrderRecharges($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_recharge` WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    public function getOrderPickup($order_id) {
        $query = $this->db->query("SELECT p.*, c.name AS country, z.name AS zone
                                   FROM " . DB_PREFIX . "order_pickup p
                                   LEFT JOIN " . DB_PREFIX . "country c ON c.country_id = p.country_id
                                   LEFT JOIN " . DB_PREFIX . "zone z ON z.zone_id = p.zone_id
                                   WHERE order_id = '" . (int)$order_id . "'");

        return $query->row;
    }

    public function getOrderStatusForMs($order_sn){
        $order_query = $this->db->query("SELECT o.order_id,o.customer_id,mssu.suborder_id,mssu.seller_id,mssu.order_sn,mssu.order_status_id FROM `" . DB_PREFIX . "ms_suborder` mssu LEFT JOIN  `" . DB_PREFIX . "order` o ON (o.order_id = mssu.order_id) WHERE mssu.order_sn = '" . $order_sn . "' AND o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0'");
        return $order_query->row;
    }

    public function checkOrderProductStatusForMs($order_product_id = 0,$status = 0)
    {
        $order_product_id   = (int)$order_product_id;
        if ( $order_product_id <= 0 )  return [];

        $fields         = format_find_field('order_id,order_product_id','op');
        $fields         .= ',' . format_find_field('seller_id','msop');

        $order_query = $this->db->query("SELECT " . $fields . " FROM " . get_tabname('order_product') . " op LEFT JOIN " . get_tabname('ms_order_product') . " msop ON (op.order_product_id = msop.order_product_id) WHERE op.order_product_id = '" . $order_product_id . "'");

        $product_info   = $order_query->num_rows ? $order_query->row : [];
        if (!empty($product_info)) {

            $order_query = $this->db->query("SELECT o.`order_id`,mssu.`order_status_id` FROM " . get_tabname('ms_suborder') . " mssu LEFT JOIN " . get_tabname('order') . " o ON (o.order_id = mssu.order_id) WHERE mssu.order_id = '" . $product_info['order_id'] . "' AND mssu.seller_id = '" . $product_info['seller_id'] . "' AND o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0'");
            
            foreach ($order_query->rows as $value) {
                if ($value['order_status_id'] != $status) return 1;
            }

            return 2;
        }else{
            return 0;
        }
    }

    public function getOrderProductForMsByOrderProductId($product_id = 0)
    {
        $product_id = (int)$product_id;
        if ( $product_id <= 0 )  return [];

        $fields         = format_find_field('order_id,currency_code,currency_value','o');
        $fields         .= ',' . format_find_field('quantity,price,total,name,image,sku','op');
        $fields         .= ',' . format_find_field('seller_id','msop');

        $order_query = $this->db->query("SELECT " . $fields . " FROM `" . DB_PREFIX . "ms_order_product` msop LEFT JOIN  `" . DB_PREFIX . "order_product` op ON (op.order_product_id = msop.order_product_id) LEFT JOIN `" . DB_PREFIX . "order` o ON (o.order_id = op.order_id) WHERE msop.order_product_id = '" . $product_id . "' AND o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0'");

        return $order_query->num_rows ? $order_query->row : [];
    }

    public function getOrderForMs($order_sn = '')
    {
        if ( empty($order_sn) )  return [];

        $fields         = format_find_field('order_id,payment_code,currency_code,currency_value,fullname,telephone,email,date_added,shipping_method,shipping_country,shipping_zone,shipping_zone,shipping_address_format,shipping_fullname,shipping_telephone,shipping_address_1,shipping_address_2,shipping_postcode,shipping_city,invoice_no,payment_address_format,payment_method,shipping_custom_field,comment,date_added','o');
        $fields         .= ',' . format_find_field('suborder_id,order_sn,seller_id,total,order_status_id','mssu');
        $fields         .= ',' . format_find_field('avatar,store_name,','ms');

        $order_query = $this->db->query("SELECT " . $fields . " FROM `" . DB_PREFIX . "ms_suborder` mssu LEFT JOIN  `" . DB_PREFIX . "order` o ON (o.order_id = mssu.order_id) LEFT JOIN `" . DB_PREFIX . "ms_seller` ms ON (ms.seller_id = mssu.seller_id) WHERE mssu.order_sn = '" . $this->db->escape($order_sn) . "' AND o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0'");

        return $order_query->num_rows ? $order_query->row : [];
    }

    public function getOrderPayinfoForMs($order_sn = '')
    {
        if ( empty($order_sn) )  return [];
        
        $fields         = format_find_field('order_id,payment_code,currency_code,currency_value,payment_country_id,payment_zone_id,payment_county_id,payment_city_id','o');
        $fields         .= ',' .  format_find_field('suborder_id,order_sn,seller_id,total,order_status_id','mssu');

        $order_query = $this->db->query("SELECT " . $fields . " FROM `" . DB_PREFIX . "ms_suborder` mssu LEFT JOIN  `" . DB_PREFIX . "order` o ON (o.order_id = mssu.order_id) WHERE mssu.order_sn = '" .  $this->db->escape($order_sn) . "' AND o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0'");

        return $order_query->num_rows ? $order_query->row : [];
    }

    public function getOrderProductsForMs($order_id = 0 , $seller_id = 0)
    {
        $order_id       = (int)$order_id;
        $seller_id      = (int)$seller_id;

        if ($order_id <= 0 || $seller_id <= 0)  return [];

        $query = $this->db->query("SELECT op.`order_product_id`,op.`product_id`, op.`name`,op.`quantity`,op.`price`,op.`total`,p.`image`,op.`sku`,op.`tax`,op.`model` FROM `" . DB_PREFIX . "order_product` op 
            LEFT JOIN  `" . DB_PREFIX . "ms_order_product` msop ON (op.order_product_id = msop.order_product_id) 
            LEFT JOIN  `" . DB_PREFIX . "product` p ON (p.product_id = op.product_id) 
            WHERE msop.seller_id = '" . $seller_id . "' AND msop.order_id = '" . $order_id . "' ORDER BY op.order_product_id DESC LIMIT 0,100");

        return !empty($query->rows) ? $query->rows : [];
    }

    //商家订单处理
    public function getTotalOrdersForMs($order_type = 0)
    {
        $order_type         = (int)$order_type;

        if ($order_type == 0) {
            $status_where       = "AND mssu.order_status_id > '0' ";
        }elseif ($order_type == 1) {
            $status_where   = "AND mssu.order_status_id = '" . $this->config->get('config_unpaid_status_id') . "' ";
        }elseif ($order_type == 2) {

            $unshipped_status = $this->config->get('config_paid_status');

            if ($this->config->get('payment_cod_status')) {
                $unshipped_status[] = $this->config->get('payment_cod_order_status_id');
            }

            $status_where   = "AND mssu.order_status_id in ('" . implode("','",$unshipped_status) . "') ";
        }elseif ($order_type == 3) {
            $status_where   = "AND mssu.order_status_id = '" . $this->config->get('config_shipped_status_id') . "' ";
        }elseif($order_type == 4){
            $order_statuses = $this->config->get('config_complete_status');
            foreach ($order_statuses as $order_status_id) {
                $implode[] = "mssu.order_status_id = '".(int) $order_status_id."'";
            }

            $status_where   = "AND " . implode(" OR ",$implode) . ' ';
        }

        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . get_tabname('ms_suborder') . " mssu LEFT JOIN  `oc_order`o ON (o.order_id = mssu.order_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' " . $status_where . "AND o.store_id = '" . (int)$this->config->get('config_store_id') . "'");

        return $query->row['total'];
    }

    public function getTotalsForMs($order_id, $seller_id) {
        $query = $this->db->query("SELECT ot.* FROM `" . DB_PREFIX . "order_total` ot
                                    LEFT JOIN `" . DB_PREFIX . "ms_order_total` mot ON mot.order_total_id = ot.order_total_id
                                    WHERE order_id = '" . (int)$order_id . "' AND mot.seller_id = '" . (int)$seller_id . "' ORDER BY sort_order ASC");

        return $query->rows;
    }

    public function getTotalsForMsByCode($order_ids,$code) {
        $order_ids      = (!empty($order_ids) && is_array($order_ids)) ? $order_ids : [0];
        $query = $this->db->query("SELECT ot.*,mot.seller_id FROM `" . DB_PREFIX . "order_total` ot
                                    LEFT JOIN `" . DB_PREFIX . "ms_order_total` mot ON mot.order_total_id = ot.order_total_id
                                    WHERE order_id IN ('" . implode("','",$order_ids) . "') AND ot.code = '" . $code . "' ORDER BY sort_order ASC");

        return $query->rows;
    }

    public function getOrdersForMs($order_type = 0,$start = 0, $limit = 20)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 1;
        }

        $order_type         = (int)$order_type;

        if ($order_type == 0) {
            $status_where       = "AND mssu.order_status_id > '0' ";
        }elseif ($order_type == 1) {
            $status_where   = "AND mssu.order_status_id = '" . $this->config->get('config_unpaid_status_id') . "' AND mssu.is_return = '0' ";
        }elseif ($order_type == 2) {

            $unshipped_status = $this->config->get('config_paid_status');

            if ($this->config->get('payment_cod_status')) {
                $unshipped_status[] = $this->config->get('payment_cod_order_status_id');
            }

            $status_where   = "AND mssu.order_status_id IN ('" . implode("','",$unshipped_status) . "') AND mssu.is_return = '0' ";
        }elseif ($order_type == 3) {
            $status_where   = "AND mssu.order_status_id = '" . $this->config->get('config_shipped_status_id') . "' ";
        }elseif($order_type == 4){
            $order_statuses = $this->config->get('config_complete_status');
            foreach ($order_statuses as $order_status_id) {
                $implode[] = "mssu.order_status_id = '".(int) $order_status_id."'";
            }

            $status_where   = "AND (" . implode(" OR ",$implode) . ") AND mssu.is_return = '0' ";
        }

        $fields         = format_find_field('order_id AS oid,date_added,currency_code,currency_value,fullname,date_added','o');
        $fields         .= ',' . format_find_field('order_status_id AS status_id,suborder_id AS soid,order_sn,total','mssu');
        $fields         .= ',' . format_find_field('name AS status','os');
        $fields         .= ',' . format_find_field('store_name,seller_id AS msid','ms');

        $sql        = "SELECT " . $fields . " FROM " . get_tabname('ms_suborder') . " mssu 
        LEFT JOIN  " . get_tabname('order') . " o ON (o.order_id = mssu.order_id)
        LEFT JOIN " . get_tabname('ms_seller') . " ms ON (ms.seller_id = mssu.seller_id)
        LEFT JOIN " . get_tabname('order_status') . " os ON (mssu.order_status_id = os.order_status_id) 
        WHERE o.customer_id = '" . (int)$this->customer->getId() . "' " . $status_where . "AND o.store_id = '0' 
        AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' 
        ORDER BY mssu.suborder_id 
        DESC LIMIT " . (int)$start . "," . (int)$limit;

        $query = $this->db->query($sql);

        $data           = $query->rows;
        if ($data) {
            foreach ($query->rows as $key => $value) {
                $data[$key]['product_info']   = [];

                $sql  = "SELECT op.`order_product_id`,op.`product_id`, op.`name`,op.`quantity`,op.`price`,op.`total`,p.`image`,op.`tax`  FROM `" . DB_PREFIX . "order_product` op 
                LEFT JOIN  `" . DB_PREFIX . "ms_order_product` msop ON (op.order_product_id = msop.order_product_id) 
                LEFT JOIN  `" . DB_PREFIX . "product` p ON (p.product_id = op.product_id) 
                WHERE msop.seller_id = '" . $value['msid'] . "' AND msop.order_id = '" . $value['oid'] . "' ORDER BY op.order_product_id DESC LIMIT 0,100";
                $query = $this->db->query($sql);

                $product_info                 = !empty($query->rows) ? $query->rows : [];

                foreach ($product_info as $pkey => $pvalue) {
                    $option_data                    = \Models\Product::find($pvalue['product_id'])->getVariantLabels();
                    $opt                            = [];
                    foreach ($option_data as $okey => $ovalue) {
                        $opt[]      = $ovalue['name'] . ':' . $ovalue['value'];
                    }

                    $product_info[$pkey]['option']   = implode(',', $opt);
                }

                $data[$key]['product_info']   = $product_info;
            }
        }

        return $data;
    }

    public function addOrderHistoryForMs($order_id, $order_status_id, $comment = '', $notify = false, $override = false)
    {
        $order_info = $this->getOrderStatusForMs($order_id);

        if ($order_info) {
            // Fraud Detection
            $this->load->model('account/customer');
            $this->load->language('checkout/checkout');

            $customer_info  = $this->model_account_customer->getCustomer($order_info['customer_id']);

            $safe           = ($customer_info && $customer_info['safe']) ? true : false;

            // Only do the fraud check if the customer is not on the safe list and the order status is changing into the complete or process order status
            if (!$safe && !$override && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
                // Anti-Fraud
                /*$this->load->model('setting/extension');

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
                }*/
            }

            // config_processing_status：订单处理状态 config_complete_status：订单完成状态
            if (!in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
                // Redeem coupon, vouchers and reward points
                /*$order_totals = $this->getOrderTotals($order_id);

                foreach ($order_totals as $order_total) {
                    $this->load->model('extension/total/' . $order_total['code']);

                    if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'confirm')) {
                        // Confirm coupon, vouchers and reward points
                        $fraud_status_id = $this->{'model_extension_total_' . $order_total['code']}->confirm($order_info, $order_total);

                        // If the balance on the coupon, vouchers and reward points is not enough to cover the transaction or has already been used then the fraud order status is returned.
                        if ($fraud_status_id) {
                            $order_status_id = $fraud_status_id;
                        }
                    }
                }

                // Stock subtraction
                $order_products = $this->getOrderProducts($order_id);

                foreach ($order_products as $order_product) {
                    $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

                    $order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

                    foreach ($order_options as $order_option) {
                        $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
                    }
                }

                // Add commission if sale is linked to affiliate referral.
                if ($order_info['affiliate_id'] && $this->config->get('config_affiliate_auto')) {
                    $this->load->model('account/customer');

                    if (!$this->model_account_customer->getTotalTransactionsByOrderId($order_id)) {
                        $this->model_account_customer->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
                    }
                }*/
            }

            // Update the DB with the new statuses
            $this->db->query("UPDATE `" . DB_PREFIX . "ms_suborder` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");

            // If current order status is not complete but new status is complete then get the order's recharges and add the customer transaction balance
            if (!in_array($order_info['order_status_id'], array_merge($this->config->get('config_complete_status'), $this->config->get('config_paid_status')))
                  && in_array($order_status_id, array_merge($this->config->get('config_complete_status'), $this->config->get('config_paid_status')))) {
                $recharge_infos = $this->getOrderRecharges($order_id);

                foreach ($recharge_infos as $recharge_info) {
                    if (!$this->model_account_customer->getTotalTransactionsByOrderRechargeId($recharge_info['order_recharge_id'])) {
                        // 如果改条transaction记录是充值，只保存recharge_id，定好通过recharge_id来关联获取，以保证transaction表的的order_id代表的含义保持原生，不影响相关功能
                        $this->model_account_customer->addTransaction($order_info['customer_id'], $this->language->get('text_order_id') . ' #' . $order_id, $recharge_info['amount'], 0, $recharge_info['order_recharge_id']);
                    }
                }
            }
            // If current order status is complete but new status is not complete then remove the customer transaction balance
            if (in_array($order_info['order_status_id'], array_merge($this->config->get('config_complete_status'), $this->config->get('config_paid_status')))
                  && !in_array($order_status_id, array_merge($this->config->get('config_complete_status'), $this->config->get('config_paid_status')))) {
                $recharge_infos = $this->getOrderRecharges($order_id);

                foreach ($recharge_infos as $recharge_info) {
                    $this->model_account_customer->deleteTransactionByOrderRechargeId($recharge_info['order_recharge_id']);
                }
            }

            // If old order status is the processing or complete status but new status is not then commence restock, and remove coupon, voucher and reward history
            if (in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && !in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
                // Restock
                $order_products = $this->getOrderProducts($order_id);

                foreach($order_products as $order_product) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

                    $order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

                    foreach ($order_options as $order_option) {
                        $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
                    }
                }

                // Remove coupon, vouchers and reward points history
                $order_totals = $this->getOrderTotals($order_id);

                foreach ($order_totals as $order_total) {
                    $this->load->model('extension/total/' . $order_total['code']);

                    if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'unconfirm')) {
                        $this->{'model_extension_total_' . $order_total['code']}->unconfirm($order_id);
                    }
                }

                // Remove commission if sale is linked to affiliate referral.
                if ($order_info['affiliate_id']) {
                    $this->load->model('account/customer');

                    $this->model_account_customer->deleteTransactionByOrderId($order_id);
                }
            }

            $this->cache->delete('product');
        }
    }

    public function getOrderHistoriesDateForMs($order_id=0,$seller_id=0,$order_status_id=0) {
        $query = $this->db->query("SELECT date_added FROM " . DB_PREFIX . "ms_suborder_history WHERE order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$seller_id . "' AND order_status_id = '" . (int)$order_status_id . "' ORDER BY date_added DESC LIMIT 1");

        return $query->row;
    }

    public function deleteSubOrder($order_sn = ''){
        $this->db->query("UPDATE `" . DB_PREFIX . "ms_suborder` SET order_status_id = '-1', date_modified = NOW() WHERE order_sn = '" . (string)$order_sn . "'");
    }

    public function setReturnStatus($is_return,$order_sn)
    {
        if (in_array($is_return, [0,1]) && !empty($order_sn)) {
            $this->db->query("UPDATE `" . DB_PREFIX . "ms_suborder` SET is_return = '" . (int)$is_return . "' WHERE order_sn = '" . (string)$order_sn . "'");
        }
    }

    public function isReturn($order_sn)
    {
        $isReturn       = false;
        $order_info     = $this->getOrderForMs($order_sn);

        if(!empty($order_info)){
            $order_id       = isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0;
            $seller_id      = isset($order_info['seller_id']) ? (int)$order_info['seller_id'] : 0;

            $query_product  = $this->db->query("SELECT `order_id`,`order_product_id` FROM " . DB_PREFIX . "ms_order_product WHERE order_id = '" . $order_id . "' AND seller_id = '" . $seller_id . "'");

            if($query_product->num_rows){
                $order_product_id       = [];
                foreach ($query_product->rows as $value) {
                    $order_product_id[$value['order_product_id']]   = $value['order_product_id'];
                }

                if (!empty($order_product_id)) {
                    $query_return  = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "return WHERE order_id = '" . $order_id . "' AND product_id IN (" . implode(',', $order_product_id) . ")");

                    if (isset($query_return->row['total']) && $query_return->row['total'] > 0) {
                        $isReturn   = true;
                    }
                }
            }
        }

        return $isReturn;
    }
}