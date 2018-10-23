<?php
class ModelMultisellerSellerBanner extends Model {
	public function saveSellerBanner($seller_id,$data) {

		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "seller_banner WHERE seller_id = '" . (int)$seller_id . "'");
		if ($query->row) {

			$banner_id 		= $query->row['banner_id'];
			
			$this->db->query("UPDATE " . DB_PREFIX . "seller_banner SET name = '" . $this->db->escape((string)$data['name']) . "', status = '" . (int)$data['status'] . "' WHERE banner_id = '" . (int)$banner_id . "'");

			$this->db->query("DELETE FROM " . DB_PREFIX . "seller_banner_image WHERE banner_id = '" . (int)$banner_id . "'");
		}else{

			$this->db->query("INSERT INTO " . DB_PREFIX . "seller_banner SET `seller_id` = '" . (int)$seller_id . "', name = '" . $this->db->escape((string)$data['name']) . "', status = '" . (int)$data['status'] . "'");
			$banner_id = $this->db->getLastId();
		}

		if (isset($data['banner_image'])) {
			foreach ($data['banner_image'] as $language_id => $value) {
				foreach ($value as $banner_image) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "seller_banner_image SET banner_id = '" . (int)$banner_id . "', language_id = '" . (int)$language_id . "', title = '" .  $this->db->escape($banner_image['title']) . "', link = '" .  $this->db->escape($banner_image['link']) . "', image = '" .  $this->db->escape($banner_image['image']) . "', sort_order = '" .  (int)$banner_image['sort_order'] . "'");
				}
			}
		}

		return $banner_id;
	}

	public function getSellerBanner($seller_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "seller_banner WHERE seller_id = '" . (int)$seller_id . "'");

		return $query->row;
	}

	public function getSellerBannerImages($seller_id,$language_id=0) {

		$query 					= $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "seller_banner WHERE seller_id = '" . (int)$seller_id . "'");
		$banner_id 				= (!empty($query->row) && isset($query->row['banner_id'])) ? (int)$query->row['banner_id'] : 0;
		$banner_image_data 		= [];

		$sql 					= '';
		if ($language_id >0) {
			$sql 				.= "AND language_id = '" . (int)$language_id . "' ";
		}

		$banner_image_query 	= $this->db->query("SELECT * FROM " . DB_PREFIX . "seller_banner_image WHERE banner_id = '" . (int)$banner_id . "' " . $sql . "ORDER BY sort_order ASC");

		foreach ($banner_image_query->rows as $banner_image) {
			$banner_image_data[$banner_image['language_id']][] = array(
				'title'      => $banner_image['title'],
				'link'       => $banner_image['link'],
				'image'      => $banner_image['image'],
				'sort_order' => $banner_image['sort_order']
			);
		}

		return $banner_image_data;
	}
}
