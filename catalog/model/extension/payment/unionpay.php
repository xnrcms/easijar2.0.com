<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-23 11:12:00
 * @modified         2016-11-23 15:11:10
 */

class ModelExtensionPaymentUnionpay extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/unionpay');

        if ($this->config->get('payment_unionpay_total') > 0 && $this->config->get('payment_unionpay_total') > $total) {
            $status = false;
        } else {
            $status = true;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code' => 'unionpay',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('payment_unionpay_sort_order'),
              );
        }

        return $method_data;
    }

}
