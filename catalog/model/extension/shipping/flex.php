<?php
/**
 * flex.php
 *
 * @copyright 2018 OpenCart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-07-13 15:26
 * @modified 2018-07-13 15:26
 */

class ModelExtensionShippingFlex extends Model
{

    function getQuote($address)
    {
        $this->load->language('extension/shipping/flex');

        //$status = $this->config->get('shipping_flex_status');
        $status = true;

        $info = $this->config->get('shipping_flex_info');
        if (!$info) {
            $status = false;
        }

        $country_id = isset($address['country_id']) ? $address['country_id'] : '';
        $zone_id = isset($address['zone_id']) ? $address['zone_id'] : '';
        if (!$country_id || !$zone_id) {
            $status = false;
        }

        $shipping_data = Flex::getInstance()->getShippingDataByCountryId($country_id, $zone_id);
        if (!$shipping_data) {
            $status = false;
        }

        if ($this->cart->getTotal() < $this->config->get('shipping_flex_cost')) {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $fixFree = Flex::getInstance()->fixFreeShipping($country_id, $zone_id);
            if ($fixFree) {
                $quote_data['flex'] = array(
                    'code' => 'flex.flex',
                    'title' => $this->language->get('text_title'),
                    'cost' => 0,
                    'tax_class_id' => $this->config->get('shipping_flex_tax_class_id'),
                    'text' => $this->currency->format($this->tax->calculate(0, $this->config->get('shipping_flex_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
                );
                $method_data = array(
                    'code'       => 'flex',
                    'title'      => '',
                    'quote'      => $quote_data,
                    'sort_order' => $this->config->get('shipping_flex_sort_order'),
                    'error'      => false
                );
            } else {
                $quote_item = array();
                foreach ($shipping_data as $key => $item) {
                    $delivery_tile = array_get($item['shipping'], 'delivery_time', '');
                    if ($delivery_tile) {
                        $delivery = '(' . $delivery_tile . ')';
                    } else {
                        $delivery = '';
                    }
                    $quote_item[$item['shipping']['express_id']] = array(
                        'code' => 'flex.' . $item['shipping']['express_id'],
                        'title' => $item['shipping']['express_title'] . $delivery,
                        'cost' => $item['cost'],
                        'tax_class_id' => $this->config->get('shipping_flex_tax_class_id'),
                        'text' => $this->currency->format($this->tax->calculate($item['cost'], $this->config->get('shipping_flex_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency']),
                    );
                }

                if (!$quote_item) {
                    return array();
                }

                $method_data = array(
                    'code' => 'flex',
                    'title' => $this->language->get('text_title'),
                    'quote' => $quote_item,
                    'sort_order' => $this->config->get('shipping_flex_sort_order'),
                    'error' => false
                );
            }
        }
        return $method_data;
    }
}