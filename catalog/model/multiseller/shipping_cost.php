<?php
class ModelMultisellerShippingCost extends Model {
	public function addShippingCost($seller_id, $data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "ms_shipping_cost SET title = '" . $this->db->escape((string)$data['title']) . "', `type` = '" . (int)$data['type'] . "', unit_weight = '" . (int)$data['unit_weight'] . "', unit_volume = '" . (int)$data['unit_volume'] . "', initial = '" . (float)$data['initial'] . "', initial_cost = '" . (float)$data['initial_cost'] . "', `continue` = '" . (float)$data['continue'] . "', continue_cost = '" . (float)$data['continue_cost'] . "', geo_zone_id = '" . (int)$data['geo_zone_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', seller_id = '" . (int)$seller_id . "'");

		$shipping_cost_id = $this->db->getLastId();

		return $shipping_cost_id;
	}

	public function editShippingCost($shipping_cost_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "ms_shipping_cost SET title = '" . $this->db->escape((string)$data['title']) . "', `type` = '" . (int)$data['type'] . "', unit_weight = '" . (int)$data['unit_weight'] . "', unit_volume = '" . (int)$data['unit_volume'] . "', initial = '" . (float)$data['initial'] . "', initial_cost = '" . (float)$data['initial_cost'] . "', `continue` = '" . (float)$data['continue'] . "', continue_cost = '" . (float)$data['continue_cost'] . "', geo_zone_id = '" . (int)$data['geo_zone_id'] . "', sort_order = '" . (int)$data['sort_order'] . "' WHERE shipping_cost_id  = '" . (int)$shipping_cost_id . "' AND seller_id = '" . (int)$this->customer->getId() . "'");
	}

	public function deleteShippingCost($shipping_cost_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ms_shipping_cost WHERE shipping_cost_id = '" . (int)$shipping_cost_id . "' AND seller_id = '" . (int)$this->customer->getId() . "'");
	}

	public function getShippingCost($shipping_cost_id) {
		$shipping_cost_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "ms_shipping_cost WHERE shipping_cost_id = '" . (int)$shipping_cost_id . "' AND seller_id = '" . (int)$this->customer->getId() . "'");

		if ($shipping_cost_query->num_rows) {
			$shipping_cost_data = array(
				'shipping_cost_id'     => $shipping_cost_query->row['shipping_cost_id'],
				'title'                => $shipping_cost_query->row['title'],
				'seller_id'            => $shipping_cost_query->row['seller_id'],
				'geo_zone_id'          => $shipping_cost_query->row['geo_zone_id'],
				'type'                 => $shipping_cost_query->row['type'],
				'unit_weight'          => $shipping_cost_query->row['unit_weight'],
				'unit_volume'          => $shipping_cost_query->row['unit_volume'],
				'initial'              => $shipping_cost_query->row['initial'],
                'initial_cost'         => $shipping_cost_query->row['initial_cost'],
				'continue'             => $shipping_cost_query->row['continue'],
				'continue_cost'        => $shipping_cost_query->row['continue_cost'],
				'sort_order'           => $shipping_cost_query->row['sort_order']
			);

			return $shipping_cost_data;
		} else {
			return false;
		}
	}

	public function getShippingCosts() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ms_shipping_cost WHERE seller_id = '" . (int)$this->customer->getId() . "'");

		$shipping_cost_data = array();

		foreach ($query->rows as $result) {
			$shipping_cost_data[$result['shipping_cost_id']] = array(
				'shipping_cost_id'     => $result['shipping_cost_id'],
				'title'                => $result['title'],
				'seller_id'            => $result['seller_id'],
				'geo_zone_id'          => $result['geo_zone_id'],
				'type'                 => $result['type'],
				'unit_weight'          => $result['unit_weight'],
				'unit_volume'          => $result['unit_volume'],
				'initial'              => $result['initial'],
                'initial_cost'         => $result['initial_cost'],
				'continue'             => $result['continue'],
				'continue_cost'        => $result['continue_cost']
			);
		}

		return $shipping_cost_data;
	}

	public function getTotalShippingCosts() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ms_shipping_cost WHERE seller_id = '" . (int)$this->customer->getId() . "'");

		return $query->row['total'];
	}
}
