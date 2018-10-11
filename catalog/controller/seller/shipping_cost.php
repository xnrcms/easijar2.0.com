<?php
class ControllerSellerShippingCost extends Controller {
	private $error = array();
	const TYPE = array(
	    'by_weight'  => 1,
        'by_volume'  => 2,
        'by_count'   => 3
    );

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('seller/shipping_cost');

			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
		}

		$this->load->language('seller/shipping_cost');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/shipping_cost');

		$this->getList();
	}

	public function add() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('seller/shipping_cost');

			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
		}

		$this->load->language('seller/shipping_cost');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/shipping_cost');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_multiseller_shipping_cost->addShippingCost($this->customer->getId(), $this->request->post);

			$this->session->data['success'] = $this->language->get('text_add');

			$this->response->redirect($this->url->link('seller/shipping_cost'));
		}

		$this->getForm();
	}

	public function edit() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('seller/shipping_cost');

			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
		}

		$this->load->language('seller/shipping_cost');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/shipping_cost');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_multiseller_shipping_cost->editShippingCost($this->request->get['shipping_cost_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_edit_success');

			$this->response->redirect($this->url->link('seller/shipping_cost'));
		}

		$this->getForm();
	}

	public function delete() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('seller/shipping_cost');

			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
		}

		$this->load->language('seller/shipping_cost');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('multiseller/shipping_cost');

		if (isset($this->request->get['shipping_cost_id']) && $this->validateDelete()) {
			$this->model_multiseller_shipping_cost->deleteShippingCost($this->request->get['shipping_cost_id']);

			$this->session->data['success'] = $this->language->get('text_delete');

			$this->response->redirect($this->url->link('seller/shipping_cost'));
		}

		$this->getList();
	}

	protected function getList() {
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_seller'),
			'href' => $this->url->link('seller/account')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('seller/shipping_cost')
		);

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

		$data['shipping_costs'] = array();

		$results = $this->model_multiseller_shipping_cost->getShippingCosts();

		foreach ($results as $result) {
			$data['shipping_costs'][] = array(
				'shipping_cost_id' => $result['shipping_cost_id'],
				'title'            => $result['title'],
				'type'             => $this->language->get('text_type_' . array_flip(self::TYPE)[$result['type']]),
				'initial'          => $result['initial'],
				'initial_cost'     => $result['initial_cost'],
				'continue'         => $result['continue'],
				'continue_cost'    => $result['continue_cost'],
				'update'           => $this->url->link('seller/shipping_cost/edit', 'shipping_cost_id=' . $result['shipping_cost_id']),
				'delete'           => $this->url->link('seller/shipping_cost/delete', 'shipping_cost_id=' . $result['shipping_cost_id'])
			);
		}

		$data['add'] = $this->url->link('seller/shipping_cost/add');
		$data['back'] = $this->url->link('seller/account');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('seller/shipping_cost_list', $data));
	}

	protected function getForm() {
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_seller'),
			'href' => $this->url->link('seller/account')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('seller/shipping_cost')
		);

		if (!isset($this->request->get['shipping_cost_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_shipping_cost_add'),
				'href' => $this->url->link('seller/shipping_cost/add')
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_shipping_cost_edit'),
				'href' => $this->url->link('seller/shipping_cost/edit', 'shipping_cost_id=' . $this->request->get['shipping_cost_id'])
			);
		}

		$data['text_shipping_cost'] = !isset($this->request->get['shipping_cost_id']) ? $this->language->get('text_shipping_cost_add') : $this->language->get('text_shipping_cost_edit');

		if (isset($this->error['title'])) {
			$data['error_title'] = $this->error['title'];
		} else {
			$data['error_title'] = '';
		}

		if (isset($this->error['type'])) {
			$data['error_type'] = $this->error['type'];
		} else {
			$data['error_type'] = '';
		}

		if (isset($this->error['geo_zone_id'])) {
			$data['error_geo_zone_id'] = $this->error['geo_zone_id'];
		} else {
			$data['error_geo_zone_id'] = '';
		}

		if (isset($this->error['continue'])) {
			$data['error_continue'] = $this->error['continue'];
		} else {
			$data['error_continue'] = '';
		}

		if (!isset($this->request->get['shipping_cost_id'])) {
			$data['action'] = $this->url->link('seller/shipping_cost/add');
		} else {
			$data['action'] = $this->url->link('seller/shipping_cost/edit', 'shipping_cost_id=' . $this->request->get['shipping_cost_id']);
		}

        $data['country_status'] = !is_free_or_pro();

		if (isset($this->request->get['shipping_cost_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$shipping_cost_info = $this->model_multiseller_shipping_cost->getShippingCost($this->request->get['shipping_cost_id']);
		}

		if (isset($this->request->post['title'])) {
			$data['title'] = $this->request->post['title'];
		} elseif (!empty($shipping_cost_info)) {
			$data['title'] = $shipping_cost_info['title'];
		} else {
			$data['title'] = '';
		}

		if (isset($this->request->post['geo_zone_id'])) {
			$data['geo_zone_id'] = $this->request->post['geo_zone_id'];
		} elseif (!empty($shipping_cost_info)) {
			$data['geo_zone_id'] = $shipping_cost_info['geo_zone_id'];
		} else {
			$data['geo_zone_id'] = 0;
		}

		if (isset($this->request->post['type'])) {
			$data['type'] = $this->request->post['type'];
		} elseif (!empty($shipping_cost_info)) {
			$data['type'] = $shipping_cost_info['type'];
		} else {
			$data['type'] = 0;
		}

		if (isset($this->request->post['unit_weight'])) {
			$data['unit_weight'] = $this->request->post['unit_weight'];
		} elseif (!empty($shipping_cost_info)) {
			$data['unit_weight'] = $shipping_cost_info['unit_weight'];
		} else {
			$data['unit_weight'] = 0;
		}

        if (isset($this->request->post['unit_volume'])) {
            $data['unit_volume'] = $this->request->post['unit_volume'];
        } elseif (!empty($shipping_cost_info)) {
            $data['unit_volume'] = $shipping_cost_info['unit_volume'];
        } else {
            $data['unit_volume'] = 0;
        }

        if (isset($this->request->post['initial'])) {
            $data['initial'] = $this->request->post['initial'];
        } elseif (!empty($shipping_cost_info)) {
            $data['initial'] =  $shipping_cost_info['initial'];
        } else {
            $data['initial'] = 0.0;
        }

		if (isset($this->request->post['initial_cost'])) {
			$data['initial_cost'] = $this->request->post['initial_cost'];
		} elseif (!empty($shipping_cost_info)) {
			$data['initial_cost'] = $shipping_cost_info['initial_cost'];
		} else {
			$data['initial_cost'] = 0.0;
		}

        if (isset($this->request->post['continue'])) {
            $data['continue'] = $this->request->post['continue'];
        } elseif (!empty($shipping_cost_info)) {
            $data['continue'] =  $shipping_cost_info['continue'];
        } else {
            $data['continue'] = 0.0;
        }

		if (isset($this->request->post['continue_cost'])) {
			$data['continue_cost'] = $this->request->post['continue_cost'];
		} elseif (!empty($shipping_cost_info)) {
			$data['continue_cost'] = $shipping_cost_info['continue_cost'];
		} else {
			$data['continue_cost'] = 0.0;
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($shipping_cost_info)) {
			$data['sort_order'] = $shipping_cost_info['sort_order'];
		} else {
			$data['sort_order'] = 0;
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$types = array();
		foreach (self::TYPE as $key => $value) {
		    $types[] = array(
		        'type_id' => $value,
                'name'    => $this->language->get('text_type_' . $key)
            );
        }
		$data['types'] = $types;

		$data['const_type'] = self::TYPE;
		$this->load->model('localisation/length_class');

		$data['unit_volumes'] = $this->model_localisation_length_class->getLengthClasses();

		$this->load->model('localisation/weight_class');

		$data['unit_weights'] = $this->model_localisation_weight_class->getWeightClasses();

		$data['back'] = $this->url->link('seller/shipping_cost');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('seller/shipping_cost_form', $data));
	}

	protected function validateForm() {
		if ((utf8_strlen(trim($this->request->post['title'])) < 2) || (utf8_strlen(trim($this->request->post['title'])) > 40)) {
			$this->error['title'] = $this->language->get('error_title');
		}

		$this->load->model('multiseller/shipping_cost');
		$shipping_costs = $this->model_multiseller_shipping_cost->getShippingCosts();
		foreach ($shipping_costs as $shipping_cost) {
		    if (isset($this->request->get['shipping_cost_id']) && $this->request->get['shipping_cost_id'] == $shipping_cost['shipping_cost_id']){
		        // 该运费模板为当前编辑的模板
		        continue;
            }
		    if ($shipping_cost['geo_zone_id'] == $this->request->post['geo_zone_id']) {
			    $this->error['geo_zone_id'] = $this->language->get('error_geo_zone_id');
            }
        }

		if (!isset($this->request->post['type']) || $this->request->post['type'] == 0 ) {
			$this->error['type'] = $this->language->get('error_type');
		}

		if (!isset($this->request->post['continue']) || $this->request->post['continue'] == 0.0) {
			$this->error['continue'] = $this->language->get('error_continue');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if ($this->model_multiseller_shipping_cost->getTotalShippingCosts() == 1) {
			$this->error['warning'] = $this->language->get('error_delete');
		}

		return !$this->error;
	}
}
