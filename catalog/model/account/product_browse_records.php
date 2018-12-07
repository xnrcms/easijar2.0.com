<?php
class ModelAccountProductBrowseRecords extends Model {
	public function addProductBrowseRecords($product_ids) {
		if (!empty($product_ids)) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_browse_records WHERE customer_id = '" . (int)$this->customer->getId() . "' AND product_id IN (" . implode(',', $product_ids) . ")");

			$sql 	= "INSERT INTO " . DB_PREFIX . "product_browse_records (customer_id,product_id,browse_date) VALUES ";
			foreach ($product_ids as $value) {
				$sql .= "('" . (int)$this->customer->getId() . "','" . (int)$value . "',NOW()),";
			}

			$sql 		= trim($sql,',');

			$this->db->query($sql);
		}
	}
}
