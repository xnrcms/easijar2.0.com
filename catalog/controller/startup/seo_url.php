<?php
class ControllerStartupSeoUrl extends Controller {
	private $static_routes = array(
		'account/register',
		'account/login',
		'account/logout',
		'account/edit',
		'account/password',
		'account/forgotten',
		'account/account',
		'account/address',
		'account/address/add',
		'account/wishlist',
		'account/order',
		'account/download',
		'account/recurring',
		'account/reward',
		'account/return',
		'account/return/add',
		'account/transaction',
		'account/newsletter',
		'account/voucher',
		'account/success',
		'affiliate/account',
		'checkout/cart',
		'checkout/checkout',
		'checkout/success',
		'information/contact',
		'information/sitemap',
		'product/manufacturer',
		'product/special',
		'product/search',
		'blog/home',
	);

	private $allSeoUrls = null;

	public function index() {
		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

		// Decode URL
		if (isset($this->request->get['_route_'])) {
			// decode on-site static routes
			if ($this->config->get('seo_static_link_status') && in_array($this->request->get['_route_'], $this->static_routes)) {
				$this->request->get['route'] = $this->request->get['_route_'];
				return;
			}

			$parts = explode('/', $this->request->get['_route_']);

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			foreach ($parts as $part) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'product_id') {
						$this->request->get['product_id'] = $url[1];
					}

					if ($url[0] == 'category_id') {
						if (!isset($this->request->get['path'])) {
							$this->request->get['path'] = $url[1];
						} else {
							$this->request->get['path'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'manufacturer_id') {
						$this->request->get['manufacturer_id'] = $url[1];
					}

					if ($url[0] == 'information_id') {
						$this->request->get['information_id'] = $url[1];
					}

					if ($url[0] == 'blog_category_id') {
						$this->request->get['blog_category_id'] = $url[1];
					}

					if ($url[0] == 'blog_post_id') {
						$this->request->get['blog_post_id'] = $url[1];
					}

					if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id' && $url[0] != 'blog_category_id' && $url[0] != 'blog_post_id') {
						$this->request->get['route'] = $query->row['query'];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';

					break;
				}
			}

			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['path'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				} elseif (isset($this->request->get['blog_category_id'])) {
					$this->request->get['route'] = 'blog/category';
				} elseif (isset($this->request->get['blog_post_id'])) {
					$this->request->get['route'] = 'blog/post';
				}
			}
		}
	}

	public function rewrite($link) {
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		$url = '';

		$data = array();

		parse_str($url_info['query'], $data);

		foreach ($data as $key => $value) {
			if (!isset($data['route'])) {
				continue;
			}

			if (($data['route'] == 'product/product' && $key == 'product_id')
				|| (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id')
				|| ($data['route'] == 'information/information' && $key == 'information_id')
				|| ($data['route'] == 'blog/category' && $key == 'blog_category_id')
				|| ($data['route'] == 'blog/post' && $key == 'blog_post_id')) {

				$value = (int)$value;
				if ($keyword = $this->getKeyword("{$key}={$value}")) {
					$url .= '/' . $keyword;
					unset($data[$key]);
				}
			} elseif ($data['route'] == 'common/home') {

				$url .= '/';

				unset($data[$key]);

			} elseif ($key == 'path') {
				$categories = explode('_', $value);

				foreach ($categories as $category) {
					$category = (int)$category;
					if ($keyword = $this->getKeyword("category_id={$category}")) {
						$url .= '/' . $keyword;
					} else {
						$url = '';

						break;
					}
				}

				unset($data[$key]);
			}
		}

		if ($url) {
			unset($data['route']);

			$query = '';

			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}

				if ($query) {
					$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
				}
			}

			return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
		} else {
			if ($this->config->get('seo_static_link_status')) {
				// rewrite on-site static routes
				$prefix = $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']);
				if ($data['route'] == 'common/home') {
					$link = $prefix;
				}
				if (in_array($data['route'], $this->static_routes)) {
					$link = $prefix . '/' . $data['route'];
				}
			}
		}

		return $link;
	}

	// product_id=5: laptop
	private function getKeyword($query)
	{
		$allSeoUrls = $this->getAllSeoUrls();
		if (!$allSeoUrls) {
			return null;
		}

		foreach ($allSeoUrls as $key => $keyword) {
			if ($key == $query) {
				return $keyword;
			}
		}

		return null;
	}

	private function getAllSeoUrls()
	{
		if (!is_null($this->allSeoUrls)) {
			return $this->allSeoUrls;
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE language_id = '" . current_language_id() . "' AND store_id = '" . (int)config('config_store_id') . "'");

		$this->allSeoUrls = [];
		foreach ($query->rows as $result) {
			$this->allSeoUrls[$result['query']] = $result['keyword'];
		}

		return $this->allSeoUrls;
	}
}
