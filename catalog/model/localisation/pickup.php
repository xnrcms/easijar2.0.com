<?php
class ModelLocalisationPickup extends Model {
	public function getPickup($pickup_id) {
		$query = $this->db->query("SELECT p.*, c.name AS country, z.name AS zone
                                    FROM " . DB_PREFIX . "pickup p 
                                    LEFT JOIN " . DB_PREFIX . "country c ON c.country_id = p.country_id
                                    LEFT JOIN " . DB_PREFIX . "zone z ON z.zone_id = p.zone_id
                                    WHERE pickup_id = '" . (int)$pickup_id . "'");

		return $query->row;
	}

	public function getPickups() {
		$query = $this->db->query("SELECT p.*, c.name AS country, z.name AS zone
                                    FROM " . DB_PREFIX . "pickup p 
                                    LEFT JOIN " . DB_PREFIX . "country c ON c.country_id = p.country_id
                                    LEFT JOIN " . DB_PREFIX . "zone z ON z.zone_id = p.zone_id");

		return $query->rows;
	}

	public function getPickupsByZoneId($zone_id) {
		$query = $this->db->query("SELECT p.*, c.name AS country, z.name AS zone
                                    FROM " . DB_PREFIX . "pickup p 
                                    LEFT JOIN " . DB_PREFIX . "country c ON c.country_id = p.country_id
                                    LEFT JOIN " . DB_PREFIX . "zone z ON z.zone_id = p.zone_id
                                    WHERE p.zone_id = '" . (int)$zone_id . "'");

		return $query->rows;
	}
}