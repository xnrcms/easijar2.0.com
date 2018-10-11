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
    public function getCustomerGroups2($data = array())
    {
        $sql = 'SELECT * FROM '.DB_PREFIX.'customer_group cg LEFT JOIN '.DB_PREFIX."customer_group_description cgd ON (cg.customer_group_id = cgd.customer_group_id) WHERE cgd.language_id = '".(int) $this->config->get('config_language_id')."'";

        $implode = array();
        if (!empty($data['filter_name'])) {
            $implode[] = "cgd.name LIKE '%".$this->db->escape($data['filter_name'])."%'";
        }

        if ($implode) {
            $sql .= ' AND '.implode(' AND ', $implode);
        }
        $sort_data = array(
            'cgd.name',
            'cg.sort_order',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= ' ORDER BY '.$data['sort'];
        } else {
            $sql .= ' ORDER BY cgd.name';
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= ' DESC';
        } else {
            $sql .= ' ASC';
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql .= ' LIMIT '.(int) $data['start'].','.(int) $data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
    public function getCouponCustomers($coupon_id)
    {
        $coupon_customer_data = array();

        $query = $this->db->query('SELECT * FROM '.DB_PREFIX."coupon_customer WHERE coupon_id = '".(int) $coupon_id."'");

        foreach ($query->rows as $result) {
            $coupon_customer_data[] = $result['customer_id'];
        }

        return $coupon_customer_data;
    }

    public function getCouponCustomerGroups($coupon_id)
    {
        $coupon_customer_group_data = array();

        $query = $this->db->query('SELECT * FROM '.DB_PREFIX."coupon_customer_group WHERE coupon_id = '".(int) $coupon_id."'");

        foreach ($query->rows as $result) {
            $coupon_customer_group_data[] = $result['customer_group_id'];
        }

        return $coupon_customer_group_data;
    }
}
