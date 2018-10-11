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
    public function getCouponsByCustomer($customer = null, $view_expire = false)
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
                $coupon_query = $this->db->query('SELECT c.* FROM `'.DB_PREFIX."coupon` AS c WHERE c.status = '1' AND c.coupon_id IN (".implode(',', $coupon_list).')');
            } else {
                $coupon_query = $this->db->query('SELECT c.* FROM `'.DB_PREFIX."coupon` AS c WHERE ((c.date_start = '0000-00-00' OR c.date_start < NOW()) AND (DATE_ADD(c.date_end,INTERVAL 1 DAY ) = '0000-00-00' OR c.date_end > NOW())) AND c.status = '1' AND c.coupon_id IN (".implode(',', $coupon_list).')');
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
}
