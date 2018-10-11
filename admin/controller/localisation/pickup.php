<?php
class ControllerLocalisationPickup extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('localisation/pickup');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/pickup');

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
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

		$data['breadcrumbs'] =   array();

		$data['breadcrumbs'][] =   array(
			'text' =>  $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] =   array(
			'text' =>  $this->language->get('heading_title'),
			'href' =>  $this->url->link('localisation/pickup', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		$data['add'] = $this->url->link('localisation/pickup/add', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['delete'] = $this->url->link('localisation/pickup/delete', 'user_token=' . $this->session->data['user_token'] . $url);

		$data['pickup'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$pickup_total = $this->model_localisation_pickup->getTotalPickups();

		$results = $this->model_localisation_pickup->getPickups($filter_data);

		foreach ($results as $result) {
			$data['pickup'][] =   array(
				'pickup_id' => $result['pickup_id'],
				'name'        => $result['name'],
				'address'     => $result['address'],
				'edit'        => $this->url->link('localisation/pickup/edit', 'user_token=' . $this->session->data['user_token'] . '&pickup_id=' . $result['pickup_id'] . $url)
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

		$data['sort_name'] = $this->url->link('localisation/pickup', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url);
		$data['sort_address'] = $this->url->link('localisation/pickup', 'user_token=' . $this->session->data['user_token'] . '&sort=address' . $url);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $pickup_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('localisation/pickup', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($pickup_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($pickup_total - $this->config->get('config_limit_admin'))) ? $pickup_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $pickup_total, ceil($pickup_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('localisation/pickup_list', $data));
	}

	public function add() {
		$this->load->language('localisation/pickup');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/pickup');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_localisation_pickup->addPickup($this->request->post);

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

			$this->response->redirect($this->url->link('localisation/pickup', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'localisation/pickup')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 32)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if ((utf8_strlen($this->request->post['address']) < 3) || (utf8_strlen($this->request->post['address']) > 128)) {
			$this->error['address'] = $this->language->get('error_address');
		}

		if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		return !$this->error;
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['pickup_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['address'])) {
			$data['error_address'] = $this->error['address'];
		} else {
			$data['error_address'] = '';
		}

		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = '';
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
			'href' => $this->url->link('localisation/pickup', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		if (!isset($this->request->get['pickup_id'])) {
			$data['action'] = $this->url->link('localisation/pickup/add', 'user_token=' . $this->session->data['user_token'] . $url);
		} else {
			$data['action'] = $this->url->link('localisation/pickup/edit', 'user_token=' . $this->session->data['user_token'] .  '&pickup_id=' . $this->request->get['pickup_id'] . $url);
		}

		$data['cancel'] = $this->url->link('localisation/pickup', 'user_token=' . $this->session->data['user_token'] . $url);

		if (isset($this->request->get['pickup_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$pickup_info = $this->model_localisation_pickup->getPickup($this->request->get['pickup_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('setting/store');

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($pickup_info)) {
			$data['name'] = $pickup_info['name'];
		} else {
			$data['name'] =   '';
		}

		if (isset($this->request->post['country_id'])) {
			$data['country_id'] = $this->request->post['country_id'];
		} elseif (!empty($pickup_info)) {
			$data['country_id'] = $pickup_info['country_id'];
		} else {
			$data['country_id'] = $this->config->get('country_id');
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		if (isset($this->request->post['zone_id'])) {
			$data['zone_id'] = $this->request->post['zone_id'];
		} elseif (!empty($pickup_info)) {
			$data['zone_id'] = $pickup_info['zone_id'];
		} else {
			$data['zone_id'] = $this->config->get('zone_id');
		}

		if (isset($this->request->post['address'])) {
			$data['address'] = $this->request->post['address'];
		} elseif (!empty($pickup_info)) {
			$data['address'] = $pickup_info['address'];
		} else {
			$data['address'] = '';
		}

		if (isset($this->request->post['geocode'])) {
			$data['geocode'] = $this->request->post['geocode'];
		} elseif (!empty($pickup_info)) {
			$data['geocode'] = $pickup_info['geocode'];
		} else {
			$data['geocode'] = '';
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} elseif (!empty($pickup_info)) {
			$data['telephone'] = $pickup_info['telephone'];
		} else {
			$data['telephone'] = '';
		}

		if (isset($this->request->post['open'])) {
			$data['open'] = $this->request->post['open'];
		} elseif (!empty($pickup_info)) {
			$data['open'] = $pickup_info['open'];
		} else {
			$data['open'] = '';
		}

		if (isset($this->request->post['comment'])) {
			$data['comment'] = $this->request->post['comment'];
		} elseif (!empty($pickup_info)) {
			$data['comment'] = $pickup_info['comment'];
		} else {
			$data['comment'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('localisation/pickup_form', $data));
	}

	public function edit() {
		$this->load->language('localisation/pickup');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/pickup');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_localisation_pickup->editPickup($this->request->get['pickup_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('localisation/pickup', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('localisation/pickup');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/pickup');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $pickup_id) {
				$this->model_localisation_pickup->deletePickup($pickup_id);
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

			$this->response->redirect($this->url->link('localisation/pickup', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getList();
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'localisation/pickup')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}