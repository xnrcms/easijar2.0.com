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
    public function addOrderShippingtrack($order_id, $data)
    {
        $this->load->model('sale/order');
        $order_info = $this->model_sale_order->getOrder($order_id);

        //增加快递单号和快递公司信息
        if ($data['tracking_code']) {
            $tracking_name = $this->getExpressNameByCode($data['tracking_code']);
            //更新订单状态 并 记录快递单号
            $this->db->query('INSERT INTO '.DB_PREFIX."order_shippingtrack SET order_id = '".(int) $order_id."', tracking_name = '".trim($tracking_name)."', tracking_code = '".trim($data['tracking_code'])."', tracking_number = '".trim($data['tracking_number'])."', comment = '".$this->db->escape($data['kd_comment'])."', date_added = NOW()");
        }
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

    public function delOrderShippingtrack($id)
    {
        $this->db->query('DELETE FROM '.DB_PREFIX."order_shippingtrack WHERE id = '".(int) $id."'");
    }

    public function getOrderShippingtracks($order_id, $start = 0, $limit = 10)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 10;
        }

        $query = $this->db->query('SELECT * FROM '.DB_PREFIX."order_shippingtrack WHERE order_id = '".(int) $order_id."' ORDER BY date_added DESC LIMIT ".(int) $start.','.(int) $limit);

        return $query->rows;
    }

    public function getTotalOrderShippingtracks($order_id)
    {
        $query = $this->db->query('SELECT COUNT(*) AS total FROM '.DB_PREFIX."order_shippingtrack WHERE order_id = '".(int) $order_id."'");

        return $query->row['total'];
    }

    public function getShippingtrack($order_id)
    {
        $query = $this->db->query('SELECT * FROM '.DB_PREFIX."order_shippingtrack WHERE order_id = '".(int) $order_id."'");

        return $query->rows;
    }
}
