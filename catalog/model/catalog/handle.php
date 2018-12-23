<?php
class ModelCatalogHandle extends Model {
    public function get_product_option_value_total()
    {
    	$query = $this->db->query("SELECT COUNT(total) AS total FROM (SELECT COUNT(*) as total FROM " . DB_PREFIX . "product_option_value WHERE 1 GROUP BY product_id) AS tt WHERE 1");
      	return $query->row['total'];
    }

    public function get_product_option_value_list($data)
    {
    	$sql 	= "SELECT COUNT(*) as total,product_id FROM " . DB_PREFIX . "product_option_value WHERE 1 GROUP BY product_id ORDER BY product_id";
    	if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

    	$query = $this->db->query($sql);
      	return $query->row;
    }
}
