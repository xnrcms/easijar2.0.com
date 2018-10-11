<?php
class ControllerMultisellerSellerGroup extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('multiseller/seller_group');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller_group');

		$this->getList();
	}

	public function add() {
		$this->load->language('multiseller/seller_group');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller_group');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_multiseller_seller_group->addSellerGroup($this->request->post);

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

			$this->response->redirect($this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('multiseller/seller_group');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller_group');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_multiseller_seller_group->editSellerGroup($this->request->get['seller_group_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('multiseller/seller_group');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller_group');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $seller_group_id) {
				$this->model_multiseller_seller_group->deleteSellerGroup($seller_group_id);
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

			$this->response->redirect($this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'sgd.name';
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
			'href' => $this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		$data['add'] = $this->url->link('multiseller/seller_group/add', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['delete'] = $this->url->link('multiseller/seller_group/delete', 'user_token=' . $this->session->data['user_token'] . $url);

		$data['seller_groups'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$seller_group_total = $this->model_multiseller_seller_group->getTotalSellerGroups();

		$results = $this->model_multiseller_seller_group->getSellerGroups($filter_data);

		foreach ($results as $result) {
			$data['seller_groups'][] = array(
				'seller_group_id'   => $result['seller_group_id'],
				'name'              => $result['name'],
				'fee_show'          => $result['fee_show_flat'] . '+' . $result['fee_show_percent'] . '%',
                'fee_sale'          => $result['fee_sale_flat'] . '+' . $result['fee_sale_percent'] . '%',
                'product_quantity'  => $result['product_quantity'],
				'sort_order'        => $result['sort_order'],
				'edit'              => $this->url->link('multiseller/seller_group/edit', 'user_token=' . $this->session->data['user_token'] . '&seller_group_id=' . $result['seller_group_id'] . $url)
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

		$data['sort_name'] = $this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token'] . '&sort=sgd.name' . $url);
		$data['sort_sort_order'] = $this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token'] . '&sort=sg.sort_order' . $url);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $seller_group_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($seller_group_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($seller_group_total - $this->config->get('config_limit_admin'))) ? $seller_group_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $seller_group_total, ceil($seller_group_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('multiseller/seller_group_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['seller_group_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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
			'href' => $this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		if (!isset($this->request->get['seller_group_id'])) {
			$data['action'] = $this->url->link('multiseller/seller_group/add', 'user_token=' . $this->session->data['user_token'] . $url);
		} else {
			$data['action'] = $this->url->link('multiseller/seller_group/edit', 'user_token=' . $this->session->data['user_token'] . '&seller_group_id=' . $this->request->get['seller_group_id'] . $url);
		}

		$data['cancel'] = $this->url->link('multiseller/seller_group', 'user_token=' . $this->session->data['user_token'] . $url);

		if (isset($this->request->get['seller_group_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$seller_group_info = $this->model_multiseller_seller_group->getSellerGroup($this->request->get['seller_group_id']);
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['seller_group_description'])) {
			$data['seller_group_description'] = $this->request->post['seller_group_description'];
		} elseif (isset($this->request->get['seller_group_id'])) {
			$data['seller_group_description'] = $this->model_multiseller_seller_group->getSellerGroupDescriptions($this->request->get['seller_group_id']);
		} else {
			$data['seller_group_description'] = array();
		}

		if (isset($this->request->post['fee_show_flat'])) {
			$data['fee_show_flat'] = $this->request->post['fee_show_flat'];
		} elseif (!empty($seller_group_info)) {
			$data['fee_show_flat'] = $seller_group_info['fee_show_flat'];
		} else {
			$data['fee_show_flat'] = '';
		}

		if (isset($this->request->post['fee_show_percent'])) {
			$data['fee_show_percent'] = $this->request->post['fee_show_percent'];
		} elseif (!empty($seller_group_info)) {
			$data['fee_show_percent'] = $seller_group_info['fee_show_percent'];
		} else {
			$data['fee_show_percent'] = '';
		}

		if (isset($this->request->post['fee_sale_flat'])) {
			$data['fee_sale_flat'] = $this->request->post['fee_sale_flat'];
		} elseif (!empty($seller_group_info)) {
			$data['fee_sale_flat'] = $seller_group_info['fee_sale_flat'];
		} else {
			$data['fee_sale_flat'] = '';
		}

		if (isset($this->request->post['fee_sale_percent'])) {
			$data['fee_sale_percent'] = $this->request->post['fee_sale_percent'];
		} elseif (!empty($seller_group_info)) {
			$data['fee_sale_percent'] = $seller_group_info['fee_sale_percent'];
		} else {
			$data['fee_sale_percent'] = '';
		}

		if (isset($this->request->post['product_quantity'])) {
			$data['product_quantity'] = $this->request->post['product_quantity'];
		} elseif (!empty($seller_group_info)) {
			$data['product_quantity'] = $seller_group_info['product_quantity'];
		} else {
			$data['product_quantity'] = '';
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($seller_group_info)) {
			$data['sort_order'] = $seller_group_info['sort_order'];
		} else {
			$data['sort_order'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('multiseller/seller_group_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'multiseller/seller_group')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['seller_group_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 3) || (utf8_strlen($value['name']) > 32)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'multiseller/seller_group')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('setting/store');
		$this->load->model('multiseller/seller');

		foreach ($this->request->post['selected'] as $seller_group_id) {
			if ($this->config->get('config_seller_group_id') == $seller_group_id) {
				$this->error['warning'] = $this->language->get('error_default');
			}

			$seller_total = $this->model_multiseller_seller->getTotalSellersBySellerGroupId($seller_group_id);

			if ($seller_total) {
				$this->error['warning'] = sprintf($this->language->get('error_seller'), $seller_total);
			}
		}

		return !$this->error;
	}
}