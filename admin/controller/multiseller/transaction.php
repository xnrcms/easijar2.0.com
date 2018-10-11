<?php
class ControllerMultisellerTransaction extends Controller {
	public function index() {
		$this->load->language('multiseller/transaction');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/transaction');

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

		if (isset($this->request->get['filter_description'])) {
			$filter_description = $this->request->get['filter_description'];
		} else {
			$filter_description = '';
		}

		if (isset($this->request->get['filter_amount'])) {
			$filter_amount = $this->request->get['filter_amount'];
		} else {
			$filter_amount = '';
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 't.date_added';
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

		if (isset($this->request->get['filter_description'])) {
			$url .= '&filter_description=' . urlencode(html_entity_decode($this->request->get['filter_description'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_amount'])) {
			$url .= '&filter_amount=' . $this->request->get['filter_amount'];
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
			'href' => $this->url->link('multiseller/transaction', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();

		$data['transactions'] = array();

		$filter_data = array(
			'filter_name'              => $filter_name,
			'filter_store_name'        => $filter_store_name,
			'filter_description'       => $filter_description,
			'filter_amount'            => $filter_amount,
			'filter_date_added'        => $filter_date_added,
			'sort'                     => $sort,
			'order'                    => $order,
			'start'                    => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                    => $this->config->get('config_limit_admin')
		);

		$seller_total = $this->model_multiseller_transaction->getTotalTransactions($filter_data);

		$results = $this->model_multiseller_transaction->getTransactions($filter_data);

		foreach ($results as $result) {
			$data['transactions'][] = array(
				'seller_transaction_id' => $result['seller_transaction_id'],
				'name'                  => $result['name'],
				'store_name'            => $result['store_name'],
				'description'           => $result['description'],
				'amount'                => $result['amount'],
				'date_added'            => date($this->language->get('datetime_format'), strtotime($result['date_added']))
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_store_name'])) {
			$url .= '&filter_store_name=' . urlencode(html_entity_decode($this->request->get['filter_store_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_description'])) {
			$url .= '&filter_description=' . urlencode(html_entity_decode($this->request->get['filter_description'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_amount'])) {
			$url .= '&filter_amount=' . $this->request->get['filter_amount'];
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

		$data['sort_name'] = $this->url->link('multiseller/transaction', 'user_token=' . $this->session->data['user_token'] . '&sort=c.fullname' . $url);
		$data['sort_store_name'] = $this->url->link('multiseller/transaction', 'user_token=' . $this->session->data['user_token'] . '&sort=s.store_name' . $url);
		$data['sort_description'] = $this->url->link('multiseller/transaction', 'user_token=' . $this->session->data['user_token'] . '&sort=t.description' . $url);
		$data['sort_amount'] = $this->url->link('multiseller/transaction', 'user_token=' . $this->session->data['user_token'] . '&sort=t.amount' . $url);
		$data['sort_date_added'] = $this->url->link('multiseller/transaction', 'user_token=' . $this->session->data['user_token'] . '&sort=t.date_added' . $url);

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_store_name'])) {
			$url .= '&filter_store_name=' . urlencode(html_entity_decode($this->request->get['filter_store_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_description'])) {
			$url .= '&filter_description=' . urlencode(html_entity_decode($this->request->get['filter_description'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_amount'])) {
			$url .= '&filter_amount=' . $this->request->get['filter_amount'];
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
		$pagination->url = $this->url->link('multiseller/transaction', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($seller_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($seller_total - $this->config->get('config_limit_admin'))) ? $seller_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $seller_total, ceil($seller_total / $this->config->get('config_limit_admin')));

		$data['filter_name'] = $filter_name;
		$data['filter_store_name'] = $filter_store_name;
		$data['filter_description'] = $filter_description;
		$data['filter_amount'] = $filter_amount;
		$data['filter_date_added'] = $filter_date_added;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('multiseller/transaction_list', $data));
	}
}
