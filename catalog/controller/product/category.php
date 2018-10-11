<?php
class ControllerProductCategory extends Controller {
	public function index() {
		$this->load->language('product/category');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/product_pro');
		$this->load->model('tool/image');

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		} else {
			$filter = '';
		}

		if (isset($this->request->get['brand'])) {
			$brandIds = parse_filters($this->request->get['brand']);
		} else {
			$brandIds = '';
		}

		if (isset($this->request->get['attr'])) {
			$attr = parse_attributes($this->request->get['attr']);
		} else {
			$attr = '';
		}

		if (isset($this->request->get['option'])) {
			$options = parse_filters($this->request->get['option']);
		} else {
			$options = '';
		}

		if (isset($this->request->get['variant'])) {
			$variants = parse_filters($this->request->get['variant']);
		} else {
			$variants = '';
		}

		if (isset($this->request->get['in_stock'])) {
			$inStock = array_get($this->request->get, 'in_stock');
		}

		if (isset($this->request->get['price'])) {
			$filterPrices = parse_filters($this->request->get['price']);
		} else {
			$filterPrices = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['path'])) {
			$url = $this->url->getQueriesExclude(['path']);

			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', array_merge(['path' => $path], $url))
					);
				}
			}
		} else {
			$category_id = 0;
		}

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if ($category_info) {
			$this->document->setTitle($category_info['meta_title']);
			$this->document->setDescription($category_info['meta_description']);
			$this->document->setKeywords($category_info['meta_keyword']);
			$this->document->setImage($category_info['image']);

			if($category_info['image']){
				$share_image = $this->model_tool_image->resize($category_info['image'], 600, 315);
			} else {
				$share_image = $this->model_tool_image->resize($this->config->get('config_image'), 600, 315);
			}
			$this->document->setImage($share_image);

			$data['heading_title'] = $category_info['name'];

			$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

			// Set the last category breadcrumb
			$data['breadcrumbs'][] = array(
				'text' => $category_info['name'],
				'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'])
			);

			$data['thumb'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'), false);

			$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
			$data['compare'] = $this->url->link('product/compare');

			$url = $this->url->getQueriesExclude(['path']);
			$data['categories'] = array();

			$results = $this->model_catalog_category->getCategories($category_id);

            $categoryIds = array();
            foreach ($results as $result) {
                $categoryIds[] = $result['category_id'];
            }
            $totals = $this->model_catalog_product_pro->getTotalProductsFromAllCategories();

			foreach ($results as $result) {
				$total = array_get($totals, $result['category_id'], 0);
				$data['categories'][] = array(
					'name' => $result['name'] . ($this->config->get('config_product_count') ? ' (' . $total . ')' : ''),
					'thumb' => $this->model_tool_image->resize($result['image']),
					'href' => $this->url->link('product/category', array_merge(['path' => $this->request->get['path'] . '_' . $result['category_id']], $url))
				);
			}

			$data['products'] = array();

			$filter_data = array(
				'filter_category_id'  => $category_id,
				'filter_sub_category' => $this->config->get('config_product_category') ? true : false,
				'filter_filter'       => $filter,
				'filter_brand_ids'    => $brandIds,
				'filter_attributes'   => $attr,
				'parent_id' => 0,
				'filter_option_value_ids'  => $options,
				'filter_variant_value_ids' => $variants,
				'filter_price'        => $filterPrices,
				'sort'                => $sort,
				'order'               => $order,
				'start'               => ($page - 1) * $limit,
				'limit'               => $limit
			);
			if (isset($inStock)) {
				$filter_data['filter_in_stock'] = $inStock;
			}

			$product_total = $this->model_catalog_product_pro->getTotalProducts($filter_data);

			$results = $this->model_catalog_product_pro->getProducts($filter_data);

			$url = $this->url->getQueriesOnly(['path']);
			foreach ($results as $result) {
				$href = $this->url->link('product/product', array_merge($url, ['product_id' => $result['product_id']]));
				$data['products'][] = $this->model_catalog_product->handleSingleProduct($result, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'), $href);
			}

			$url = $this->url->getQueriesExclude(['sort', 'order']);
			$data['sorts'] = array();
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/category', array_merge($url, ['sort' => 'p.sort_order', 'order' => 'ASC']))
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/category', array_merge($url, ['sort' => 'pd.name', 'order' => 'ASC']))
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/category', array_merge($url, ['sort' => 'pd.name', 'order' => 'DESC']))
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/category', array_merge($url, ['sort' => 'p.price', 'order' => 'ASC']))
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/category', array_merge($url, ['sort' => 'p.price', 'order' => 'DESC']))
			);

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/category', array_merge($url, ['sort' => 'rating', 'order' => 'DESC']))
				);

				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/category', array_merge($url, ['sort' => 'rating', 'order' => 'ASC']))
				);
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/category', array_merge($url, ['sort' => 'p.model', 'order' => 'ASC']))
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/category', array_merge($url, ['sort' => 'p.model', 'order' => 'DESC']))
			);

			$url = $this->url->getQueriesExclude(['limit']);

			$data['limits'] = array();
			for ($i = 1; $i <= 5; $i++) {
				$value = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit') * $i;
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/category', array_merge($url, ['limit' => $value]))
				);
			}

			$url = $this->url->getQueriesExclude(['page']);
			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/category', array_merge($url, ['page' => '{page}']));

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
			if ($page == 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id']), 'canonical');
			} else {
				$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. $page), 'canonical');
			}

			if ($page > 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . (($page - 2) ? '&page='. ($page - 1) : '')), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. ($page + 1)), 'next');
			}


			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			if (config('is_mobile')) {
				$data['style'] = 'grid';
			} else {
				if (array_get($this->request->get, 'style')) {
					$this->session->data['display_style'] = (array_get($this->request->get, 'style') == 'list' ? 'list' : 'grid');
				}
				$data['style'] = $this->session->get('display_style', 'grid');

				$url = $this->url->getQueriesExclude(['style']);
				$data['style_grid'] = $this->url->link('product/category', array_merge($url, ['style' => 'grid']));
				$data['style_list'] = $this->url->link('product/category', array_merge($url, ['style' => 'list']));
			}

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			// Category specific twig
			if ($category_info['twig']) {
				$twig = trim($category_info['twig']);
				$theme_twig = DIR_TEMPLATE . config('config_theme') . '/template/' . $twig . '.twig';
				$default_twig = DIR_TEMPLATE . 'default/template/' . $twig . '.twig';
				if (file_exists($theme_twig) || file_exists($default_twig)) {
					$this->response->setOutput($this->load->view($twig, $data));
					return;
				}
			}

			$this->response->setOutput($this->load->view('product/category', $data));
		} else {
			$url = $this->url->getQueries();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/category', $url)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}
