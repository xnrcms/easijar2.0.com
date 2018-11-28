<?php
class ControllerMultisellerSeller extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('multiseller/seller');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller');

		$this->getList();
	}

	public function add() {
		$this->load->language('multiseller/seller');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_multiseller_seller->addSeller($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_store_name'])) {
				$url .= '&filter_store_name=' . urlencode(html_entity_decode($this->request->get['filter_store_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_company'])) {
				$url .= '&filter_company=' . urlencode(html_entity_decode($this->request->get['filter_company'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_seller_group_id'])) {
				$url .= '&filter_seller_group_id=' . $this->request->get['filter_seller_group_id'];
			}

			if (isset($this->request->get['filter_status'])) {
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('multiseller/seller');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_multiseller_seller->editSeller($this->request->get['seller_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_store_name'])) {
				$url .= '&filter_store_name=' . urlencode(html_entity_decode($this->request->get['filter_store_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_company'])) {
				$url .= '&filter_company=' . urlencode(html_entity_decode($this->request->get['filter_company'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_seller_group_id'])) {
				$url .= '&filter_seller_group_id=' . $this->request->get['filter_seller_group_id'];
			}

			if (isset($this->request->get['filter_status'])) {
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('multiseller/seller');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/seller');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $seller_id) {
				$this->model_multiseller_seller->deleteSeller($seller_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_store_name'])) {
				$url .= '&filter_store_name=' . urlencode(html_entity_decode($this->request->get['filter_store_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_company'])) {
				$url .= '&filter_company=' . urlencode(html_entity_decode($this->request->get['filter_company'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_seller_group_id'])) {
				$url .= '&filter_seller_group_id=' . $this->request->get['filter_seller_group_id'];
			}

			if (isset($this->request->get['filter_status'])) {
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_store_name'])) {
			$filter_store_name = $this->request->get['filter_store_name'];
		} else {
			$filter_store_name = '';
		}

		if (isset($this->request->get['filter_company'])) {
			$filter_company = $this->request->get['filter_company'];
		} else {
			$filter_company = '';
		}

		if (isset($this->request->get['filter_seller_group_id'])) {
			$filter_seller_group_id = $this->request->get['filter_seller_group_id'];
		} else {
			$filter_seller_group_id = '';
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = '';
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'c.date_added';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_store_name'])) {
			$url .= '&filter_store_name=' . urlencode(html_entity_decode($this->request->get['filter_store_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_company'])) {
			$url .= '&filter_company=' . urlencode(html_entity_decode($this->request->get['filter_company'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_seller_group_id'])) {
			$url .= '&filter_seller_group_id=' . $this->request->get['filter_seller_group_id'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

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
			'href' => $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		$data['add'] = $this->url->link('multiseller/seller/add', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['delete'] = $this->url->link('multiseller/seller/delete', 'user_token=' . $this->session->data['user_token'] . $url);

		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();

		$data['sellers'] = array();

		$filter_data = array(
			'filter_name'              => $filter_name,
			'filter_store_name'        => $filter_store_name,
			'filter_company'           => $filter_company,
			'filter_seller_group_id'   => $filter_seller_group_id,
			'filter_status'            => $filter_status,
			'filter_date_added'        => $filter_date_added,
			'sort'                     => $sort,
			'order'                    => $order,
			'start'                    => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                    => $this->config->get('config_limit_admin')
		);

		$seller_total = $this->model_multiseller_seller->getTotalSellers($filter_data);

		$results = $this->model_multiseller_seller->getSellers($filter_data);

		foreach ($results as $result) {
			$store_data = array();

			$store_data[] = array(
				'name' => $this->config->get('config_name'),
				'href' => $this->url->link('customer/customer/login', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['seller_id'] . '&store_id=0')
			);

			foreach ($stores as $store) {
				$store_data[] = array(
					'name' => $store['name'],
					'href' => $this->url->link('customer/customer/login', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['seller_id'] . '&store_id=' . $result['store_id'])
				);
			}

			$data['sellers'][] = array(
				'seller_id'      => $result['seller_id'],
				'name'           => $result['name'],
				'customer_href'  => $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['seller_id']),
				'store_name'     => $result['store_name'],
				'company'        => $result['company'],
				'seller_group'   => $result['seller_group'],
				'status'         => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
				'date_added'     => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'store'          => $store_data,
				'edit'           => $this->url->link('multiseller/seller/edit', 'user_token=' . $this->session->data['user_token'] . '&seller_id=' . $result['seller_id'] . $url)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

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

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_store_name'])) {
			$url .= '&filter_store_name=' . urlencode(html_entity_decode($this->request->get['filter_store_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_company'])) {
			$url .= '&filter_company=' . urlencode(html_entity_decode($this->request->get['filter_company'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_seller_group_id'])) {
			$url .= '&filter_seller_group_id=' . $this->request->get['filter_seller_group_id'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . '&sort=fullname' . $url);
		$data['sort_store_name'] = $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . '&sort=store_name' . $url);
		$data['sort_company'] = $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . '&sort=company' . $url);
		$data['sort_seller_group'] = $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . '&sort=seller_group' . $url);
		$data['sort_status'] = $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url);
		$data['sort_date_added'] = $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . '&sort=c.date_added' . $url);

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_store_name'])) {
			$url .= '&filter_store_name=' . urlencode(html_entity_decode($this->request->get['filter_store_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_company'])) {
			$url .= '&filter_company=' . urlencode(html_entity_decode($this->request->get['filter_company'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_seller_group_id'])) {
			$url .= '&filter_seller_group_id=' . $this->request->get['filter_seller_group_id'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $seller_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($seller_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($seller_total - $this->config->get('config_limit_admin'))) ? $seller_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $seller_total, ceil($seller_total / $this->config->get('config_limit_admin')));

		$data['filter_name'] = $filter_name;
		$data['filter_store_name'] = $filter_store_name;
		$data['filter_company'] = $filter_company;
		$data['filter_seller_group_id'] = $filter_seller_group_id;
		$data['filter_status'] = $filter_status;
		$data['filter_date_added'] = $filter_date_added;

		$this->load->model('multiseller/seller_group');

		$data['seller_groups'] = $this->model_multiseller_seller_group->getSellerGroups();

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('multiseller/seller_list', $data));
	}

	protected function getForm() {
        $data['store_url'] = HTTP_CATALOG;
		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['seller_id'])) {
			$data['seller_id'] = (int)$this->request->get['seller_id'];
		} else {
			$data['seller_id'] = 0;
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

        if (!isset($this->request->get['seller_id'])) {
            if (isset($this->error['customer'])) {
                $data['error_customer'] = $this->error['customer'];
            } else {
                $data['error_customer'] = '';
            }
        }

		if (isset($this->error['store_name'])) {
			$data['error_store_name'] = $this->error['store_name'];
		} else {
			$data['error_store_name'] = '';
		}

		if (isset($this->error['company'])) {
			$data['error_company'] = $this->error['company'];
		} else {
			$data['error_company'] = '';
		}

		if (isset($this->error['alipay'])) {
			$data['error_alipay'] = $this->error['alipay'];
		} else {
			$data['error_alipay'] = '';
		}

		if (isset($this->error['country'])) {
			$data['error_country'] = $this->error['country'];
		} else {
			$data['error_country'] = '';
		}

		if (isset($this->error['zone'])) {
			$data['error_zone'] = $this->error['zone'];
		} else {
			$data['error_zone'] = '';
		}

		if (isset($this->error['city'])) {
			$data['error_city'] = $this->error['city'];
		} else {
			$data['error_city'] = '';
		}

		if (isset($this->error['county'])) {
			$data['error_county'] = $this->error['county'];
		} else {
			$data['error_county'] = '';
		}

		$url = '';

		if (isset($this->request->get['filter_store_name'])) {
			$url .= '&filter_store_name=' . urlencode(html_entity_decode($this->request->get['filter_store_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_company'])) {
			$url .= '&filter_company=' . urlencode(html_entity_decode($this->request->get['filter_company'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_seller_group_id'])) {
			$url .= '&filter_seller_group_id=' . $this->request->get['filter_seller_group_id'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'],'')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		if (!isset($this->request->get['seller_id'])) {
			$data['action'] = $this->url->link('multiseller/seller/add', 'user_token=' . $this->session->data['user_token'] . $url);
		} else {
			$data['action'] = $this->url->link('multiseller/seller/edit', 'user_token=' . $this->session->data['user_token'] . '&seller_id=' . $this->request->get['seller_id'] . $url);
		}

		$data['cancel'] = $this->url->link('multiseller/seller', 'user_token=' . $this->session->data['user_token'] . $url);

		if (isset($this->request->get['seller_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$seller_info = $this->model_multiseller_seller->getSeller($this->request->get['seller_id']);
		}

		$this->load->model('multiseller/seller_group');

		$data['seller_groups'] = $this->model_multiseller_seller_group->getSellerGroups();

		if (isset($this->request->post['seller_group_id'])) {
			$data['seller_group_id'] = $this->request->post['seller_group_id'];
		} elseif (!empty($seller_info)) {
			$data['seller_group_id'] = $seller_info['seller_group_id'];
		} else {
			$data['seller_group_id'] = $this->config->get('config_seller_group_id');
		}

        if (!isset($this->request->get['seller_id'])) {
            if (isset($this->request->post['seller_name'])) {
                $data['seller_name'] = $this->request->post['seller_name'];
            } else {
                $data['seller_name'] = '';
            }
            if (isset($this->request->post['seller_id'])) {
                $data['customer_id'] = $this->request->post['seller_id'];  // 由于模板中的seller_id表示当前是新建还是编辑，所以这里改用customer_id
            } elseif (!empty($seller_info)) {
                $data['customer_id'] = $seller_info['seller_id'];
            } else {
                $data['customer_id'] = '';
            }
        }

		if (isset($this->request->post['store_name'])) {
			$data['store_name'] = $this->request->post['store_name'];
		} elseif (!empty($seller_info)) {
			$data['store_name'] = $seller_info['store_name'];
		} else {
			$data['store_name'] = '';
		}

		if (isset($this->request->post['company'])) {
			$data['company'] = $this->request->post['company'];
		} elseif (!empty($seller_info)) {
			$data['company'] = $seller_info['company'];
		} else {
			$data['company'] = '';
		}

		if (isset($this->request->post['description'])) {
			$data['description'] = $this->request->post['description'];
		} elseif (!empty($seller_info)) {
			$data['description'] = $seller_info['description'];
		} else {
			$data['description'] = '';
		}

		if (isset($this->request->post['country_id'])) {
			$data['country_id'] = $this->request->post['country_id'];
		} elseif (!empty($seller_info)) {
			$data['country_id'] = $seller_info['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}

		if (isset($this->request->post['zone_id'])) {
			$data['zone_id'] = $this->request->post['zone_id'];
		} elseif (!empty($seller_info)) {
			$data['zone_id'] = $seller_info['zone_id'];
		} else {
			$data['zone_id'] = $this->config->get('config_zone_id');
		}

		if (isset($this->request->post['city_id'])) {
			$data['city_id'] = $this->request->post['city_id'];
		} elseif (!empty($seller_info)) {
			$data['city_id'] = $seller_info['city_id'];
		} else {
			$data['city_id'] = '';
		}

		if (isset($this->request->post['county_id'])) {
			$data['county_id'] = $this->request->post['county_id'];
		} elseif (!empty($seller_info)) {
			$data['county_id'] = $seller_info['county_id'];
		} else {
			$data['county_id'] = '';
		}

		if (isset($this->request->post['avatar'])) {
			$data['avatar'] = $this->request->post['avatar'];
		} elseif (!empty($seller_info)) {
			$data['avatar'] = $seller_info['avatar'];
		} else {
			$data['avatar'] = '';
		}

        $this->load->model('tool/image');

		if (isset($this->request->post['avatar']) && is_file(DIR_IMAGE . $this->request->post['avatar'])) {
			$data['avatar'] = $this->model_tool_image->resize($this->request->post['avatar'], 100, 100);
		} elseif (!empty($seller_info) && is_file(DIR_IMAGE . $seller_info['avatar'])) {
			$data['avatar'] = $this->model_tool_image->resize($seller_info['avatar'], 100, 100);
		} else {
			$data['avatar'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		if (isset($this->request->post['banner'])) {
			$data['banner'] = $this->request->post['banner'];
		} elseif (!empty($seller_info)) {
			$data['banner'] = $seller_info['banner'];
		} else {
			$data['banner'] = 0;
		}

		if (isset($this->request->post['banner']) && is_file(DIR_IMAGE . $this->request->post['banner'])) {
			$data['banner'] = $this->model_tool_image->resize($this->request->post['banner'], 100, 100);
		} elseif (!empty($seller_info) && is_file(DIR_IMAGE . $seller_info['banner'])) {
			$data['banner'] = $this->model_tool_image->resize($seller_info['banner'], 100, 100);
		} else {
			$data['banner'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		if (isset($this->request->post['alipay'])) {
			$data['alipay'] = $this->request->post['alipay'];
		} elseif (!empty($seller_info)) {
			$data['alipay'] = $seller_info['alipay'];
		} else {
			$data['alipay'] = 0;
		}

		if (isset($this->request->post['chat_key'])) {
			$data['chat_key'] = $this->request->post['chat_key'];
		} elseif (!empty($seller_info)) {
			$data['chat_key'] = $seller_info['chat_key'];
		} else {
			$data['chat_key'] = '';
		}

		if (isset($this->request->post['product_validation'])) {
			$data['product_validation'] = $this->request->post['product_validation'];
		} elseif (!empty($seller_info)) {
			$data['product_validation'] = $seller_info['product_validation'];
		} else {
			$data['product_validation'] = 0;
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($seller_info)) {
			$data['status'] = $seller_info['status'];
		} else {
			$data['status'] = true;
		}

		$ext_field 	= ['ext_source','ext_true_name','ext_address','ext_experience','ext_company_type','ext_license','ext_legal_person','ext_idnum','ext_image'];
		foreach ($ext_field as $value) {
			if (!empty($seller_info) && isset($seller_info[$value])) {
				if ($value == 'ext_image') {
					$ext_image 		= explode(',', $seller_info[$value]);
					for ($i=0; $i < 4; $i++) { 
						$data['ext_image_' . $i] 	= isset($ext_image[$i]) ? $this->model_tool_image->resize($ext_image[$i], 100, 100) : '';
					}
				}elseif ($value == 'ext_company_type') {
					$ctype 			= ['生产厂商','品牌商','代理商','经销商'];
					$data[$value] 	= isset($ctype[$seller_info[$value]]) ? $ctype[$seller_info[$value]] : '';
				}
				else{
					$data[$value] 	= $seller_info[$value];
				}
			}else{
				if ($value == 'ext_image') {
					for ($i=0; $i < 4; $i++) { 
						$data['ext_image_' . $i] 	= '';
					}
				}else{
					$data[$value] 	= '';
				}
			}
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('multiseller/seller_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'multiseller/seller')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->get['seller_id'])) {
            if ($this->request->post['seller_id'] == '' || $this->request->post['seller_id'] == 0 || !is_numeric($this->request->post['seller_id'])) {
                $this->error['customer'] = $this->language->get('error_customer');
            } elseif ($this->model_multiseller_seller->getSeller($this->request->post['seller_id'])) {
                $this->error['customer'] = $this->language->get('error_customer_exits');
            }
        }

		if ((utf8_strlen($this->request->post['store_name']) < 2) || (utf8_strlen(trim($this->request->post['store_name'])) > 64)) {
			$this->error['store_name'] = $this->language->get('error_store_name');
		}

		if ((utf8_strlen($this->request->post['company']) < 2) || (utf8_strlen(trim($this->request->post['company'])) > 64)) {
            $this->error['company'] = $this->language->get('error_company');
        }

        if ((utf8_strlen($this->request->post['alipay']) < 3) || (utf8_strlen($this->request->post['alipay']) > 32)) {
            $this->error['alipay'] = $this->language->get('error_alipay');
        }

		if ($this->request->post['country_id'] == '' || !is_numeric($this->request->post['country_id'])) {
			$this->error['country'] = $this->language->get('error_country');
		}

		if (!isset($this->request->post['zone_id']) || $this->request->post['zone_id'] == '' || !is_numeric($this->request->post['zone_id'])) {
			$this->error['zone'] = $this->language->get('error_zone');
		}

		if (!isset($this->request->post['city_id']) || $this->request->post['city_id'] == '' || !is_numeric($this->request->post['city_id'])) {
			$this->error['city'] = $this->language->get('error_city');
		}

		if (!isset($this->request->post['county_id']) || $this->request->post['county_id'] == '' || !is_numeric($this->request->post['county_id'])) {
			$this->error['county'] = $this->language->get('error_county');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'multiseller/seller')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function history() {
		$this->load->language('multiseller/seller');

		$this->load->model('multiseller/seller');
		$this->load->model('customer/customer');

		if (isset($this->request->get['seller_id'])) {
			$seller_id = $this->request->get['seller_id'];
		} else {
			$seller_id = 0;
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['histories'] = array();

		$results = $this->model_customer_customer->getHistories($seller_id, ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['histories'][] = array(
				'comment'    => $result['comment'],
				'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added']))
			);
		}

		$history_total = $this->model_customer_customer->getTotalHistories($seller_id);

		$pagination = new Pagination();
		$pagination->total = $history_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('customer/customer/history', 'user_token=' . $this->session->data['user_token'] . '&seller_id=' . $seller_id . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));

		$this->response->setOutput($this->load->view('customer/customer_history', $data));
	}

	public function addHistory() {
		$this->load->language('multiseller/seller');

		$json = array();

		if (isset($this->request->get['seller_id'])) {
			$seller_id = $this->request->get['seller_id'];
		} else {
			$seller_id = 0;
		}

		if (!$this->user->hasPermission('modify', 'multiseller/seller')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('multiseller/seller');

			$this->model_multiseller_seller->addHistory($seller_id, $this->request->post['comment']);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function transaction() {
		$this->load->language('multiseller/seller');

		if (isset($this->request->get['seller_id'])) {
			$seller_id = $this->request->get['seller_id'];
		} else {
			$seller_id = 0;
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$this->load->model('multiseller/seller');

		$data['transactions'] = array();

		$results = $this->model_multiseller_seller->getTransactions($seller_id, ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['transactions'][] = array(
				'amount'      => $this->currency->format($result['amount'], $this->config->get('config_currency')),
				'description' => $result['description'],
				'date_added'  => date($this->language->get('datetime_format'), strtotime($result['date_added']))
			);
		}

		$data['balance'] = $this->currency->format($this->model_multiseller_seller->getTransactionTotal($seller_id), $this->config->get('config_currency'));

		$transaction_total = $this->model_multiseller_seller->getTotalTransactions($seller_id);

		$pagination = new Pagination();
		$pagination->total = $transaction_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('multiseller/seller/transaction', 'user_token=' . $this->session->data['user_token'] . '&seller_id=' . $seller_id . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($transaction_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($transaction_total - 10)) ? $transaction_total : ((($page - 1) * 10) + 10), $transaction_total, ceil($transaction_total / 10));

		$this->response->setOutput($this->load->view('multiseller/seller_transaction', $data));
	}

	public function addTransaction() {
		$this->load->language('multiseller/seller');

		$json = array();

		if (isset($this->request->get['seller_id'])) {
			$seller_id = $this->request->get['seller_id'];
		} else {
			$seller_id = 0;
		}

		if (!$this->user->hasPermission('modify', 'multiseller/seller')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('multiseller/seller');

			$this->model_multiseller_seller->addTransaction($seller_id, $this->request->post['description'], $this->request->post['amount']);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function autocomplete() {
		$json = array();

        if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_seller']) || isset($this->request->get['filter_email'])) {
            if (isset($this->request->get['filter_seller'])) {
              $filter_seller = $this->request->get['filter_seller'];
            } else {
              $filter_seller = '';
            }

			if (isset($this->request->get['filter_name'])) {
				$filter_name = $this->request->get['filter_name'];
			} else {
				$filter_name = '';
			}

			if (isset($this->request->get['filter_email'])) {
				$filter_email = $this->request->get['filter_email'];
			} else {
				$filter_email = '';
			}

			if (isset($this->request->get['filter_affiliate'])) {
				$filter_affiliate = $this->request->get['filter_affiliate'];
			} else {
				$filter_affiliate = '';
			}

			$this->load->model('multiseller/seller');

			$filter_data = array(
				'filter_name'      => $filter_name,
                'filter_store_name'  => $filter_seller,
                'sort'             => 'c.date_added',
                'order'            => 'DESC',
				'filter_email'     => $filter_email,
				'filter_affiliate' => $filter_affiliate,
				'start'            => 0,
				'limit'            => 5
			);

			$results = $this->model_multiseller_seller->getSellers($filter_data);

			foreach ($results as $result) {
                if(isset($this->request->get['filter_seller'])){
                    $name = $result['store_name'] ? $result['store_name'] : ($result['email'] ? $result['email'] : ($result['telephone'] ? $result['telephone'] : 'ID'.$result['seller_id']));
                } else {
                    $name = $result['store_name'];
                }
				$json[] = array(
					'seller_id'       => $result['seller_id'],
					'seller_group_id' => $result['seller_group_id'],
					'name'              => strip_tags(html_entity_decode($name, ENT_QUOTES, 'UTF-8')),
					'seller_group'    => $result['seller_group'],
					'fullname'         => $result['fullname'],
					'email'             => $result['email'],
					'telephone'         => $result['telephone']
				);
			}
		}

		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
