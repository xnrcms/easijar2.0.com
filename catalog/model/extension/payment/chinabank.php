<?php 
class ModelExtensionPaymentChinabank extends Model {
	public function getMethod($address, $total)
	{
		$this->load->language('extension/payment/chinabank');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('chinabank_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('chinabank_total') > 0 && $this->config->get('chinabank_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('chinabank_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$currencies = array('CHY', 'CNY', 'HKD', 'USD');

		if (!in_array($this->session->data['currency'], $currencies)) {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code' => 'chinabank',
				'title' => $this->language->get('text_title'),
				'terms' => '',
				'sort_order' => $this->config->get('chinabank_sort_order'),
			);
		}

		return $method_data;
	}
}
