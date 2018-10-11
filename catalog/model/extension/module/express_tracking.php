<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-15 10:00:00
 * @modified         2016-11-15 10:00:00
 */

class ModelExtensionModuleExpressTracking extends Model
{
    public function getOrderShippingtrack($order_id)
    {
        $order_track_query = $this->db->query('SELECT * FROM `'.DB_PREFIX."order_shippingtrack` WHERE order_id = '".(int) $order_id."'");

        return $order_track_query->rows;
    }

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
}
