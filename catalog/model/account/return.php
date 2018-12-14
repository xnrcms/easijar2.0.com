<?php
class ModelAccountReturn extends Model {
	public function addReturn($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "return` SET order_id = '" . (int)$data['order_id'] . "', customer_id = '" . (int)$this->customer->getId() . "',product_id = '" . (isset($data['product_id']) ? (int)$data['product_id'] : 0) ."', fullname = '" . $this->db->escape((string)$data['fullname']) . "', email = '" . $this->db->escape((string)$data['email']) . "', telephone = '" . $this->db->escape((string)$data['telephone']) . "', product = '" . $this->db->escape((string)$data['product']) . "', model = '" . $this->db->escape((string)$data['model']) . "', quantity = '" . (int)$data['quantity'] . "', opened = '" . (int)$data['opened'] . "', return_reason_id = '" . (int)$data['return_reason_id'] . "', return_status_id = '" . (int)$this->config->get('config_return_status_id') . "', is_receive = '" . (isset($data['is_receive']) ? (int)$data['is_receive'] : 0) . "', is_service= '" . (isset($data['is_service']) ? (int)$data['is_service'] : 0) . "', comment = '" . $this->db->escape((string)$data['comment']) . "',image = '" . (isset($data['image']) ? $this->db->escape((string)$data['image']) : '') . "',return_money = '" . (isset($data['return_money']) ? (float)$data['return_money'] : '') . "', date_ordered = '" . $this->db->escape((string)$data['date_ordered']) . "', date_added = NOW(), date_modified = NOW()");

		return $this->db->getLastId();
	}

	public function getReturn($return_id) {
		$query = $this->db->query("SELECT r.return_id, r.order_id, r.fullname, r.email, r.telephone, r.product, r.model, r.quantity, r.opened, (SELECT rr.name FROM " . DB_PREFIX . "return_reason rr WHERE rr.return_reason_id = r.return_reason_id AND rr.language_id = '" . (int)$this->config->get('config_language_id') . "') AS reason, (SELECT ra.name FROM " . DB_PREFIX . "return_action ra WHERE ra.return_action_id = r.return_action_id AND ra.language_id = '" . (int)$this->config->get('config_language_id') . "') AS action, (SELECT rs.name FROM " . DB_PREFIX . "return_status rs WHERE rs.return_status_id = r.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status, r.comment, r.date_ordered, r.date_added, r.date_modified FROM `" . DB_PREFIX . "return` r WHERE r.return_id = '" . (int)$return_id . "' AND r.customer_id = '" . $this->customer->getId() . "'");

		return $query->row;
	}

	public function getReturns($start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$query = $this->db->query("SELECT r.return_id, r.order_id, r.fullname, rs.name as status, r.date_added FROM `" . DB_PREFIX . "return` r LEFT JOIN " . DB_PREFIX . "return_status rs ON (r.return_status_id = rs.return_status_id) WHERE r.customer_id = '" . $this->customer->getId() . "' AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY r.return_id DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalReturns() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "return`WHERE customer_id = '" . $this->customer->getId() . "'");

		return $query->row['total'];
	}



	public function getReturnsForMs($start = 0, $limit = 20)
	{
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$fields 		= format_find_field('return_id,order_id,product,model,quantity,image,seller_id,is_service','r');
		$fields 		.= "," . format_find_field('product_id','op');
		$fields 		.= "," . format_find_field('store_name','ms');
		$fields 		.= ",(SELECT ra.name FROM " . get_tabname('return_action') . " ra WHERE ra.return_action_id = (r.is_service + 5) AND ra.language_id = '" . (int)$this->config->get('config_language_id') . "') AS action";

		$query = $this->db->query("SELECT " . $fields . " FROM " . get_tabname('return') . " r LEFT JOIN " . get_tabname('order_product') . " op ON (r.product_id = op.order_product_id) LEFT JOIN " . get_tabname('ms_seller') . " ms ON (r.seller_id = ms.seller_id) WHERE r.customer_id = '" . $this->customer->getId() . "' ORDER BY r.return_id DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getReturnHistories($return_id) {
		$query = $this->db->query("SELECT rh.date_added, rs.name AS status, rh.comment FROM " . DB_PREFIX . "return_history rh LEFT JOIN " . DB_PREFIX . "return_status rs ON rh.return_status_id = rs.return_status_id WHERE rh.return_id = '" . (int)$return_id . "' AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY rh.date_added ASC");

		return $query->rows;
	}

	public function getReturnRecord($order_id = 0,$order_product_id = 0)
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "return` WHERE customer_id = '" . $this->customer->getId() . "' AND order_id = '" . (int)$order_id . "' AND product_id = '" . (int)$order_product_id . "' AND return_status_id > 0");
		return $query->row['total'];
	}

	public function getReturnForMs($return_id) {
		$fields 		= format_find_field('return_id,order_id,fullname,email,telephone,product,model,image,quantity,return_status_id,seller_id,comment,date_ordered,date_added,date_modified,return_reason_id,overtime','r');
		$fields 		.= ",(SELECT rr.name FROM " . get_tabname('return_reason') . " rr WHERE rr.return_reason_id = r.return_reason_id AND rr.language_id = '" . (int)$this->config->get('config_language_id') . "') AS reason";
		$fields 		.= ",(SELECT ra.name FROM " . get_tabname('return_action') . " ra WHERE ra.return_action_id = r.return_action_id AND ra.language_id = '" . (int)$this->config->get('config_language_id') . "') AS action";
		$fields 		.= ",(SELECT rs.name FROM " . get_tabname('return_status') . " rs WHERE rs.return_status_id = r.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status";

		$query = $this->db->query("SELECT " . $fields . " FROM  " . get_tabname('return') . " r WHERE r.return_id = '" . (int)$return_id . "' AND r.customer_id = '" . $this->customer->getId() . "'");

		return $query->row;
	}

	public function getSuborderInfo($order_id = 0,$seller_id = 0)
	{
		$fields 		= format_find_field('suborder_id,order_sn,order_status_id','mssu');
		$query = $this->db->query("SELECT " . $fields . " FROM  " . get_tabname('ms_suborder') . " mssu WHERE mssu.order_id = '" . (int)$order_id . "' AND mssu.seller_id = '" . (int)$seller_id . "'");
		return $query->row;
	}

	public function getReturnImagesByReturnId($return_id = 0)
	{
		$query = $this->db->query("SELECT `return_image_id`,`return_id`,`code` FROM " . get_tabname('return_image') . " WHERE return_id = '" .  (int)$return_id . "' ORDER BY return_image_id DESC LIMIT 10");

		return $query->rows;
	}

	public function deleteReturnImagesByReturnId($return_id = 0,$image)
	{
		$imageList 			= $this->getReturnImagesByReturnId($return_id);
		if (!empty($imageList)) {
			foreach ($imageList as $key => $value) {
				if (is_file(DIR_IMAGE . $value['code']) && !in_array($value['code'], $image)) {
		            unlink(DIR_IMAGE . $value['code']);
		        }
			}
		}

		$this->db->query("DELETE FROM " . get_tabname('return_image') . " WHERE return_id = '" . (int)$return_id . "'");
	}

	public function addReturnImagesByReturnId($return_id = 0,$image)
	{
		if (!empty($image) && isset($image[0]) && !empty($image[0])) {
			$sql 	= "INSERT INTO ". get_tabname('return_image') . " (return_id,code) VALUES ";
			foreach ($image as $key => $value) {
				$sql .=  "('".(int)$return_id ."','".(string)$value . "'),";
			}

			$sql 	= trim($sql,',');

			$this->db->query($sql);
		}
	}

	public function getReturnHistorysForMs($return_id,$start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$query = $this->db->query("SELECT rh.`return_history_id`,rh.`return_status_id`,rh.`comment`,rh.`evidences`,rac.`name`,rh.`date_added`,cus.`email`,cus.`fullname` FROM " . get_tabname('return_history') . " rh LEFT JOIN " . get_tabname('return_status') . " rs ON (rh.return_status_id = rs.return_status_id) LEFT JOIN " . get_tabname('return_action') . " rac ON (rac.return_action_id = rh.proposal) LEFT JOIN " . get_tabname('customer') . " cus ON (cus.customer_id = rh.customer_id) WHERE rh.return_id = '" . (int)$return_id . "' AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "' AND rac.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY rh.return_history_id DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function addReturnHistoryForCs($returnData)
	{
		$this->db->query("UPDATE `" . DB_PREFIX . "return` SET `return_status_id` = '" . (int)$returnData['return_status_id'] . "', date_modified = NOW() WHERE return_id = '" . (int)$returnData['return_id'] . "'");

		$this->db->query("INSERT INTO " . get_tabname('return_history') . " SET return_id = '" . (int)$returnData['return_id'] . "', return_status_id = '" . (int)$returnData['return_status_id'] . "', comment = '" . $returnData['comment'] . "',proposal = '" . (int)$returnData['proposal'] . "',return_reason_id = '" . $returnData['return_reason_id'] . "',evidences = '" . $returnData['evidences'] . "', customer_id = '" . (int)$this->customer->getId() . "',date_added = NOW()");
	}

	public function getReturnIdByOrderProductId($order_id = 0,$product_id = 0)
	{
		$query = $this->db->query("SELECT `return_id`,`return_status_id` FROM `" . DB_PREFIX . "return`WHERE customer_id = '" . $this->customer->getId() . "' AND order_id = '" . (int)$order_id . "' AND product_id = '" . (int)$product_id . "' ORDER BY return_id DESC LIMIT 1 ");

		return isset($query->row['return_id']) ? (int)$query->row['return_id'] : 0;
	}

	public function getReturnHistoryForRefuseNums()
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "return_history`WHERE return_id = '" . (int)$return_id . "' AND return_status_id = 4");
		return isset($query->row['total']) ? (int)$query->row['total'] : 0;
	}
}
