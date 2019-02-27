<?php
class ModelExtensionTotalMultisellerShipping extends Model {
	const TYPE = array(
	    'by_weight'  => 1,
        'by_volume'  => 2,
        'by_count'   => 3
    );
	public function getTotal($total) {
		if ($this->cart->hasShipping() && isset($this->session->data['shipping_address']))
        {
            foreach ($this->cart->getProducts() as $product) {
                if ($this->checkProuctIsUseShipping($product['product_id'])) return;
            }

		    $title = '';  // 运费total标题
		    $cost = 0;  //运费金额

            $this->load->model('multiseller/seller');
            $this->load->language('extension/total/multiseller_shipping', 'multiseller');

            $seller_price = array();  // 保存商家和商家商品总额
		    foreach ($this->cart->getProducts() as $product) {
                $seller_info = $this->model_multiseller_seller->getSeller($product['seller_id']);
                $seller_id = $seller_info ? $seller_info['seller_id'] : 0;
                if ($seller_id == 0) {
                    continue;
                }
		        if (isset($seller_price[$seller_id])) {
		            $seller_price[$seller_id]['price'] += $product['price'];
                } else {
                    $store_name                   = $seller_info ? $seller_info['store_name'] : $this->config->get('config_name');
		            $seller_price[$seller_id] = array(
		                'price'       => $product['price'],
                        'seller_name' => htmlspecialchars_decode($store_name)
                    );
                }
            }

            $title = $this->language->get('multiseller')->get('text_multiseller_shipping');
		    $first = true;
            foreach ($seller_price as $key => $value) {
		        $shipping_cost = $this->getShippingCost($key,$this->session->data['shipping_address']);
		        $cost += $shipping_cost;

                $total['totals'][] = array(
                    'seller_id'  => $key,
                    'code'       => 'multiseller_shipping',
                    'title'      => $value['seller_name'] . '&#'.$key.'multiseller_shipping&#' . $title,
                    'value'      => $shipping_cost,
                    'sort_order' => $this->config->get('total_multiseller_shipping_sort_order')
                );
            }

			if ($this->config->get('total_multiseller_shipping_tax_class_id')) {
				$tax_rates = $this->tax->getRates($cost, $this->config->get('total_multiseller_shipping_tax_class_id'));

				foreach ($tax_rates as $tax_rate) {
					if (!isset($total['taxes'][$tax_rate['tax_rate_id']])) {
						$total['taxes'][$tax_rate['tax_rate_id']] = $tax_rate['amount'];
					} else {
						$total['taxes'][$tax_rate['tax_rate_id']] += $tax_rate['amount'];
					}
				}
			}

			$total['total'] += $cost;
		}
	}

    public function getShippingCostByAddress($seller_id = 0,$address = 0)
    {
        if($seller_id <= 0) return 0;

        $address['zone_id']             = isset($address['zone_id']) ? $address['zone_id'] : 0;
        $address['country_id']          = isset($address['country_id']) ? $address['country_id'] : 0;
        $address['quantity']            = isset($address['quantity']) ? $address['quantity'] : 1;
        $address['weight']              = isset($address['weight']) ? $address['weight'] : 0;
        $address['weight_class_id']     = isset($address['weight_class_id']) ? $address['weight_class_id'] : 0;
        $address['length']              = isset($address['length']) ? $address['length'] : 0;
        $address['width']               = isset($address['width']) ? $address['width'] : 0;
        $address['height']              = isset($address['height']) ? $address['height'] : 0;
        $address['length_class_id']     = isset($address['length_class_id']) ? $address['length_class_id'] : 0;

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ms_shipping_cost sc
                                   LEFT JOIN " . DB_PREFIX . "zone_to_geo_zone gz ON sc.geo_zone_id = gz.geo_zone_id
                                   WHERE seller_id = '" . (int)$seller_id . "' AND (gz.zone_id = '" . $address['zone_id'] . "' OR gz.zone_id = '0') AND gz.country_id = '" . $address['country_id'] . "'
                                   ORDER BY sc.sort_order ASC");

        $shipping_cost = $query->row;

        if (!$shipping_cost) {
            $cost = 0;
        } else {
            if ($shipping_cost['type'] == self::TYPE['by_count']) {
                $quantity   = $address['quantity'];
                $over       = $quantity - $shipping_cost['initial'];
                $over       = $over > 0 ? $over : 0;
                $cost       = $shipping_cost['initial_cost'] + ceil(($over) / $shipping_cost['continue']) * $shipping_cost['continue_cost'];
            } else if ($shipping_cost['type'] == self::TYPE['by_weight']) {
                $unit_id    = $shipping_cost['unit_weight'];
                $weight     = $this->formatWeight($address['weight'], $address['weight_class_id'], $unit_id);
                $over       = $weight - $shipping_cost['initial'];
                $over       = $over > 0 ? $over : 0;
                $cost       = $shipping_cost['initial_cost'] + ceil(($over) / $shipping_cost['continue']) * $shipping_cost['continue_cost'];
            } else if ($shipping_cost['type'] == self::TYPE['by_volume']) {
                $unit_id    = $shipping_cost['unit_volume'];
                $volume     = $this->formatLength($address['length'], $address['length_class_id'], $unit_id) * $this->formatLength($address['width'], $address['length_class_id'], $unit_id) * $this->formatLength($address['height'], $address['length_class_id'], $unit_id);
                $over       = $volume - $shipping_cost['initial'];
                $over       = $over > 0 ? $over : 0;
                $cost       = $shipping_cost['initial_cost'] + ceil(($over) / $shipping_cost['continue']) * $shipping_cost['continue_cost'];
            } else {
                $cost = 0;
            }
        }

        return $cost;
    }

    public function getProductsCost($seller_id,$address){
        if ($seller_id <= 0 || !isset($address['zone_id']) || !isset($address['country_id']) )  return 0;
        return $this->getShippingCost($seller_id,$address);
    }

	private function getShippingCost($seller_id,$address) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ms_shipping_cost sc
		                           LEFT JOIN " . DB_PREFIX . "zone_to_geo_zone gz ON sc.geo_zone_id = gz.geo_zone_id
		                           WHERE seller_id = '" . (int)$seller_id . "' AND (gz.zone_id = '" . $address['zone_id'] . "' OR gz.zone_id = '0') AND gz.country_id = '" . $address['country_id'] . "'
		                           ORDER BY sc.sort_order ASC");

	    $shipping_cost = $query->row;

	    if (!$shipping_cost) {
	        $cost = 0;
        } else {
            if ($shipping_cost['type'] == self::TYPE['by_count']) {
                $quantity = 0;
                foreach ($this->cart->getProducts() as $product) {
                    if ($seller_id == $product['seller_id']) {
                        $quantity += $product['quantity'];
                    }
                }
                $over = $quantity - $shipping_cost['initial'];
                $over = $over > 0 ? $over : 0;
                $cost = $shipping_cost['initial_cost'] + ceil(($over) / $shipping_cost['continue']) * $shipping_cost['continue_cost'];
            } else if ($shipping_cost['type'] == self::TYPE['by_weight']) {
	            $unit_id = $shipping_cost['unit_weight'];
                $weight = 0;
                foreach ($this->cart->getProducts() as $product) {
                    if ($seller_id == $product['seller_id']) {
                        $weight += $this->formatWeight($product['weight'], $product['weight_class_id'], $unit_id);
                        foreach ($product['option'] as $option) {
                            if (isset($option['weight'])) {
                                if ($option['weight_prefix'] == '+') {
                                    $weight += $this->formatWeight($option['weight'], $product['weight_class_id'], $unit_id);
                                } else if ($option['weight_prefix'] == '-') {
                                    $weight -= $this->formatWeight($option['weight'], $product['weight_class_id'], $unit_id);
                                }
                            }
                        }
                    }
                }
                $over = $weight - $shipping_cost['initial'];
                $over = $over > 0 ? $over : 0;
                $cost = $shipping_cost['initial_cost'] + ceil(($over) / $shipping_cost['continue']) * $shipping_cost['continue_cost'];
            } else if ($shipping_cost['type'] == self::TYPE['by_volume']) {
	            $unit_id = $shipping_cost['unit_volume'];
                $volume = 0;
                foreach ($this->cart->getProducts() as $product) {
                    if ($seller_id == $product['seller_id']) {
                        $volume += $this->formatLength($product['length'], $product['length_class_id'], $unit_id) * $this->formatLength($product['width'], $product['length_class_id'], $unit_id) * $this->formatLength($product['height'], $product['length_class_id'], $unit_id);
                    }
                }
                $over = $volume - $shipping_cost['initial'];
                $over = $over > 0 ? $over : 0;
                $cost = $shipping_cost['initial_cost'] + ceil(($over) / $shipping_cost['continue']) * $shipping_cost['continue_cost'];
            } else {
                $cost = 0;
            }
        }

	    return $cost;
    }

    private function formatWeight($weight, $unit_from, $unit_to) {
        $this->load->model('localisation/weight_class');

        $from = $this->model_localisation_weight_class->getWeightClass($unit_from);
        $to = $this->model_localisation_weight_class->getWeightClass($unit_to);

        return $weight * $to['value'] / $from['value'];
    }

    private function formatLength($length, $unit_from, $unit_to) {
        $this->load->model('localisation/length_class');

        $from = $this->model_localisation_length_class->getLengthClass($unit_from);
        $to = $this->model_localisation_length_class->getLengthClass($unit_to);

        return $length * $to['value'] / $from['value'];
    }

    private function checkProuctIsUseShipping($product_id = 0)
    {
        $this->load->model('setting/module');

        $checkModule        = [59];
        foreach ($checkModule as $module_id)
        {
            $setting_info       = $this->model_setting_module->getModule($module_id);
            if (!empty($setting_info) && isset($setting['product']) && !empty($setting['product']) && in_array($product_id, $setting['product'])) {
                return true;
            }
        }

        return false;
    }
}