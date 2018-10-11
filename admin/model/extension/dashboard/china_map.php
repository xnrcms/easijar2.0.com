<?php
class ModelExtensionDashboardChinaMap extends Model {
	public function getTotalOrdersByZone() {
		$implode = array();
		
		if (is_array($this->config->get('config_complete_status'))) {
			foreach ($this->config->get('config_complete_status') as $order_status_id) {
				$implode[] = (int)$order_status_id;
			}
		}
		
		if ($implode) {
			$query = $this->db->query("SELECT COUNT(*) AS total, SUM(o.total) AS amount, c.code FROM `" . DB_PREFIX . "order` o LEFT JOIN `" . DB_PREFIX . "zone` c ON (o.shipping_zone_id = c.zone_id) WHERE o.order_status_id IN('" . (int)implode(',', $implode) . "') GROUP BY o.shipping_zone_id");

			return $query->rows;
		} else {
			return array();
		}
	}
}