<?php
class ModelDesignSeoUrl extends Model {
	public function addSeoUrl($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET store_id = '" . (int)$data['store_id'] . "', language_id = '" . (int)$data['language_id'] . "', query = '" . $this->db->escape((string)$data['query']) . "', keyword = '" . $this->db->escape((string)$data['keyword']) . "'");
	}

	public function editSeoUrl($seo_url_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "seo_url` SET store_id = '" . (int)$data['store_id'] . "', language_id = '" . (int)$data['language_id'] . "', query = '" . $this->db->escape((string)$data['query']) . "', keyword = '" . $this->db->escape((string)$data['keyword']) . "' WHERE seo_url_id = '" . (int)$seo_url_id . "'");
	}

	public function deleteSeoUrl($seo_url_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE seo_url_id = '" . (int)$seo_url_id . "'");
	}

	public function getSeoUrl($seo_url_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE seo_url_id = '" . (int)$seo_url_id . "'");

		return $query->row;
	}

	public function getSeoUrls($data = array()) {
		$sql = "SELECT *, (SELECT `name` FROM `" . DB_PREFIX . "store` s WHERE s.store_id = su.store_id) AS store, (SELECT `name` FROM `" . DB_PREFIX . "language` l WHERE l.language_id = su.language_id) AS language FROM `" . DB_PREFIX . "seo_url` su";

		$implode = array();

		if (!empty($data['filter_query'])) {
			$implode[] = "`query` LIKE '" . $this->db->escape((string)$data['filter_query']) . "'";
		}

		if (!empty($data['filter_keyword'])) {
			$implode[] = "`keyword` LIKE '" . $this->db->escape((string)$data['filter_keyword']) . "'";
		}

		if (isset($data['filter_store_id']) && $data['filter_store_id'] !== '') {
			$implode[] = "`store_id` = '" . (int)$data['filter_store_id'] . "'";
		}

		if (!empty($data['filter_language_id']) && $data['filter_language_id'] !== '') {
			$implode[] = "`language_id` = '" . (int)$data['filter_language_id'] . "'";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$sort_data = array(
			'query',
			'keyword',
			'language_id',
			'store_id'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY query";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

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

		return $query->rows;
	}

	public function getTotalSeoUrls($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "seo_url`";

		$implode = array();

		if (!empty($data['filter_query'])) {
			$implode[] = "query LIKE '" . $this->db->escape((string)$data['filter_query']) . "'";
		}

		if (!empty($data['filter_keyword'])) {
			$implode[] = "keyword LIKE '" . $this->db->escape((string)$data['filter_keyword']) . "'";
		}

		if (!empty($data['filter_store_id']) && $data['filter_store_id'] !== '') {
			$implode[] = "store_id = '" . (int)$data['filter_store_id'] . "'";
		}

		if (!empty($data['filter_language_id']) && $data['filter_language_id'] !== '') {
			$implode[] = "language_id = '" . (int)$data['filter_language_id'] . "'";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getSeoUrlsByKeyword($keyword) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE keyword = '" . $this->db->escape($keyword) . "'");

		return $query->rows;
	}

	public function getSeoUrlsByQuery($keyword) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE keyword = '" . $this->db->escape($keyword) . "'");

		return $query->rows;
	}

	/**
	 * Check if seo uri is available
	 */
	public function isAvailable($keyword, $languageId = 1, $storeId = 0, $excludeQuery = '') {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "seo_url` WHERE keyword = '" . $this->db->escape($keyword) . "' AND language_id='" . (int)$languageId . "' AND store_id='" . (int)$storeId . "'";

		if (!empty($excludeQuery)) {
			$sql .= " AND query <> '{$excludeQuery}'";
		}

		$query = $this->db->query($sql);
		return !(int)$query->row['total'];
	}

	public function createUniqueKeyword($text, $languageId = 1, $storeId = 0, $type = 'product', $id = 0) {
		$keyword = $this->createKeyword($text);
		$excludeQuery = "{$type}_id={$id}"; // product_id=x

		if (empty($keyword)) {
			// Add type prefix: product-100
			$keyword = $type . '-' . $id;
		}

		if ($this->isAvailable($keyword, $languageId, $storeId, $excludeQuery)) {
			return $keyword;
		}

		// Add prefix: product-iphone
		$keyword = $type . '-' . $keyword;
		if ($this->isAvailable($keyword, $languageId, $storeId, $excludeQuery)) {
			return $keyword;
		}

		// Add suffix: product-iphone-100
		$keyword .= '-' . $id;
		if ($this->isAvailable($keyword, $languageId, $storeId, $excludeQuery)) {
			return $keyword;
		}

		// Add random number: product-iphone-100-33333 / product-100-33333
		while (true) {
			$keyword .= rand(10000, 99999);
			if ($this->isAvailable($keyword, $languageId, $storeId, $excludeQuery)) {
				return $keyword;
			}
		}
	}

	private function createKeyword($text) {
		$entities = [
			'&nbsp;',
			'&lt;',
			'&gt;',
			'&amp;',
			'&quot;',
			'&apos;',
			'&cent;',
			'&pound;',
			'&yen;',
			'&euro;',
			'&copy;',
			'&reg;',
		];

		$punctuations = [
			' ',
			'"',
			"'",
			'<',
			'>',
			'&',
			'*',
			'@',
			'^',
			'(',
			')',
			',',
			';',
			'#',
			'%',
			'$',
			'{',
			'}',
			'[',
			']',
			'.',
			'/',
			'\\',
			'+',
		];

		$keyword = trim(strtolower($text));
		$keyword = str_replace($entities, '-', $keyword);
		$keyword = str_replace($punctuations, '-', $keyword);
		$keyword = preg_replace('/-+/', '-', $keyword);
		$keyword = rtrim($keyword, '-');
		$keyword = ltrim($keyword, '-');
		return $keyword;
	}
}
