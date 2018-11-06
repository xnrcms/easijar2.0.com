<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-10 18:12:00
 * @modified         2016-11-10 18:12:00
 */

class ModelCustomercouponCoupon extends Model
{
    public function getCouponsByCustomer($customer = null, $view_expire = false,$seller_id = 0)
    {
        $customer = !empty($customer) ? $customer : $this->customer;
        $customer_group_id = $customer->getGroupId();
        $customer_id = $customer->getId();
        $coupon_list = array();

        if (!empty($customer_id)) {
            $customer_query = $this->db->query('SELECT coupon_id FROM `'.DB_PREFIX.'coupon_customer` WHERE customer_id='.(int) $customer_id);
            if ($customer_query->num_rows > 0) {
                foreach ($customer_query->rows as $item) {
                    $coupon_list[] = $item['coupon_id'];
                }
            }
        }
        if (!empty($customer_group_id)) {
            $customer_group_query = $this->db->query('SELECT coupon_id FROM `'.DB_PREFIX.'coupon_customer_group` WHERE customer_group_id='.(int) $customer_group_id);
            if ($customer_group_query->num_rows > 0) {
                foreach ($customer_group_query->rows as $item) {
                    if (!in_array($item['coupon_id'], $coupon_list)) {
                        $coupon_list[] = $item['coupon_id'];
                    }
                }
            }
        }
        if (!empty($coupon_list)) {
            if ($view_expire) {
                $coupon_query = $this->db->query('SELECT c.* FROM `'.DB_PREFIX."coupon` AS c WHERE c.status = '1' AND c.seller_id = '" . (int) $seller_id . "' AND c.coupon_id IN (".implode(',', $coupon_list).')');
            } else {
                $coupon_query = $this->db->query('SELECT c.* FROM `'.DB_PREFIX."coupon` AS c WHERE ((c.date_start = '0000-00-00' OR c.date_start < NOW()) AND (DATE_ADD(c.date_end,INTERVAL 1 DAY ) = '0000-00-00' OR c.date_end > NOW())) AND c.status = '1' AND c.seller_id = '" . (int) $seller_id . "' AND c.coupon_id IN (".implode(',', $coupon_list).')');
            }

            if ($coupon_query->num_rows > 0) {
                foreach ($coupon_query->rows as $key => $coupon) {
                    $coupon_query->rows[$key]['valid'] = true;

                    $coupon_history_query = $this->db->query('SELECT COUNT(*) AS total FROM `'.DB_PREFIX."coupon_history` ch WHERE ch.coupon_id = '".(int) $coupon['coupon_id']."'");
                    if ($coupon['uses_total'] > 0 && ($coupon_history_query->row['total'] >= $coupon['uses_total'])) {
                        $coupon_query->rows[$key]['valid'] = false;
                        continue;
                    }

                    $coupon_history_query = $this->db->query('SELECT COUNT(*) AS total FROM `'.DB_PREFIX."coupon_history` ch WHERE ch.coupon_id = '".(int) $coupon['coupon_id']."' AND ch.customer_id = '".(int) $this->customer->getId()."'");
                    if ($coupon['uses_customer'] > 0 && ($coupon_history_query->row['total'] >= $coupon['uses_customer'])) {
                        $coupon_query->rows[$key]['valid'] = false;
                        continue;
                    }
                }

                return $coupon_query->rows;
            }
        }

        return array();
    }

    public function getCouponById($coupon_id)
    {
        $query = $this->db->query('SELECT DISTINCT * FROM '.DB_PREFIX."coupon WHERE coupon_id = '".(int) $coupon_id."'");

        return $query->row;
    }

    public function getCouponsByCustomerIdForApi($data = [])
    {
        $customer_id            = (int)array_get($data, 'customer_id');
        if ($customer_id <= 0)  return [];

        $fields      = format_find_field('coupon_id,name,discount,total,date_start,date_end,explain,seller_id,type','c');
        $fields     .= ',' . format_find_field('store_name,avatar','ms');

        $sql        = 'SELECT ' . $fields . ',(date_end-CURDATE()) AS over_time FROM `'.DB_PREFIX.'coupon_customer` AS cc LEFT JOIN `'.DB_PREFIX.'coupon` AS c ON (cc.coupon_id = c.coupon_id) LEFT JOIN `'.DB_PREFIX.'ms_seller` AS ms ON (ms.seller_id = c.seller_id) WHERE customer_id='.(int) $customer_id;

        $seller_id            = (int)array_get($data, 'seller_id');
        if ($seller_id > 0) {
            $sql    .= ' AND c.seller_id = ' . (int)$seller_id;
        }

        $sort_data  = [
            'c.coupon_id',
            'c.date_added',
            'c.total',
            'over_time'
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= ' ORDER BY '.$data['sort'];
        } else {
            $sql .= ' ORDER BY ' . $sort_data[0];
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= ' ASC';
        } else {
            $sql .= ' DESC';
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
        
        $customer_query = $this->db->query($sql) ;

        return $customer_query->num_rows > 0 ? $customer_query->rows : [];
    }
}
