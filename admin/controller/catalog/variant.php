<?php
class ControllerCatalogVariant extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/variant');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/variant');

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'od.name';
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

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		$data['add'] = $this->url->link('catalog/variant/add', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['delete'] = $this->url->link('catalog/variant/delete', 'user_token=' . $this->session->data['user_token'] . $url);

		$data['variants'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$variant_total = $this->model_catalog_variant->getTotalVariants();

		$results = $this->model_catalog_variant->getVariants($filter_data);

		foreach ($results as $result) {
			$data['variants'][] = array(
				'variant_id'  => $result['variant_id'],
				'name'       => $result['name'],
				'sort_order' => $result['sort_order'],
				'edit'       => $this->url->link('catalog/variant/edit', 'user_token=' . $this->session->data['user_token'] . '&variant_id=' . $result['variant_id'] . $url)
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . '&sort=od.name' . $url);
		$data['sort_sort_order'] = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . '&sort=o.sort_order' . $url);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $variant_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($variant_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($variant_total - $this->config->get('config_limit_admin'))) ? $variant_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $variant_total, ceil($variant_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/variant_list', $data));
	}

	public function add() {
		$this->load->language('catalog/variant');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/variant');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_variant->addVariant($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/variant')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['variant_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 128)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		if (!isset($this->request->post['variant_value'])) {
			$this->error['warning'] = $this->language->get('error_type');
		}

		if (isset($this->request->post['variant_value'])) {
			foreach ($this->request->post['variant_value'] as $variant_value_id => $variant_value) {
				foreach ($variant_value['variant_value_description'] as $language_id => $variant_value_description) {
					if ((utf8_strlen($variant_value_description['name']) < 1) || (utf8_strlen($variant_value_description['name']) > 128)) {
						$this->error['variant_value'][$variant_value_id][$language_id] = $this->language->get('error_variant_value');
					}
				}
			}
		}

		return !$this->error;
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['variant_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['variant_value'])) {
			$data['error_variant_value'] = $this->error['variant_value'];
		} else {
			$data['error_variant_value'] = array();
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		if (!isset($this->request->get['variant_id'])) {
			$data['action'] = $this->url->link('catalog/variant/add', 'user_token=' . $this->session->data['user_token'] . $url);
		} else {
			$data['action'] = $this->url->link('catalog/variant/edit', 'user_token=' . $this->session->data['user_token'] . '&variant_id=' . $this->request->get['variant_id'] . $url);
		}

		$data['cancel'] = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . $url);

		if (isset($this->request->get['variant_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$variant_info = $this->model_catalog_variant->getVariant($this->request->get['variant_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['variant_description'])) {
			$data['variant_description'] = $this->request->post['variant_description'];
		} elseif (isset($this->request->get['variant_id'])) {
			$data['variant_description'] = $this->model_catalog_variant->getVariantDescriptions($this->request->get['variant_id']);
		} else {
			$data['variant_description'] = array();
		}

		if (isset($this->request->post['allow_rename'])) {
			$data['allow_rename'] = $this->request->post['allow_rename'];
		} elseif (!empty($variant_info)) {
			$data['allow_rename'] = $variant_info['allow_rename'];
		} else {
			$data['allow_rename'] = '';
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($variant_info)) {
			$data['sort_order'] = $variant_info['sort_order'];
		} else {
			$data['sort_order'] = '';
		}

		if (isset($this->request->post['variant_value'])) {
			$variant_values = $this->request->post['variant_value'];
		} elseif (isset($this->request->get['variant_id'])) {
			$variant_values = $this->model_catalog_variant->getVariantValueDescriptions($this->request->get['variant_id']);
		} else {
			$variant_values = array();
		}

		$this->load->model('tool/image');

		$data['variant_values'] = array();

		foreach ($variant_values as $variant_value) {
			if (is_file(DIR_IMAGE . $variant_value['image'])) {
				$image = $variant_value['image'];
				$thumb = $variant_value['image'];
			} else {
				$image = '';
				$thumb = 'no_image.png';
			}

			$data['variant_values'][] = array(
				'variant_value_id'          => $variant_value['variant_value_id'],
				'variant_value_description' => $variant_value['variant_value_description'],
				'image'                    => $image,
				'thumb'                    => $this->model_tool_image->resize($thumb, 100, 100),
				'sort_order'               => $variant_value['sort_order']
			);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/variant_form', $data));
	}

	public function edit() {
		$this->load->language('catalog/variant');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/variant');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_variant->editVariant($this->request->get['variant_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('catalog/variant');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/variant');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $variant_id) {
				$this->model_catalog_variant->deleteVariant($variant_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getList();
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/variant')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['selected'] as $variant_id) {
			$product_total = \Models\Product\Variant::byVariantId($variant_id)->count();

			if ($product_total) {
				$this->error['warning'] = sprintf($this->language->get('error_product'), $product_total);
			}
		}

		return !$this->error;
	}

	public function addGroup()
	{
		$variantGroup = \Models\Variant\Group::createGroup($this->request->post);
		$json['status'] = 1;
		$json['message'] = 'OK';
		$json['data'] = array(
			'group_id' => $variantGroup->variant_group_id
		);
		$this->jsonOutput($json);
	}

	public function deleteGroup()
	{
		$groupId = array_get($this->request->post, 'group_id');
		$variantGroup = \Models\Variant\Group::find($groupId);
		$variantGroup->variantRelation()->delete();
		$variantGroup->delete();

		$json['status'] = 1;
		$json['message'] = 'OK';
		$this->jsonOutput($json);
	}
}
