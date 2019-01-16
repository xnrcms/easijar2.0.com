<?php
class ControllerSellerReturn extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('seller/return');

			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
		}

		$this->load->language('seller/return');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
		
		$this->load->model('multiseller/return');

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['filter_return_id'])) {
			$filter_return_id = $this->request->get['filter_return_id'];
		} else {
			$filter_return_id = '';
		}

		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = '';
		}

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = '';
		}

		if (isset($this->request->get['filter_product'])) {
			$filter_product = $this->request->get['filter_product'];
		} else {
			$filter_product = '';
		}

		if (isset($this->request->get['filter_model'])) {
			$filter_model = $this->request->get['filter_model'];
		} else {
			$filter_model = '';
		}

		if (isset($this->request->get['filter_return_status_id'])) {
			$filter_return_status_id = $this->request->get['filter_return_status_id'];
		} else {
			$filter_return_status_id = '';
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = '';
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$filter_date_modified = $this->request->get['filter_date_modified'];
		} else {
			$filter_date_modified = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'r.return_id';
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

		if (isset($this->request->get['filter_return_id'])) {
			$url .= '&filter_return_id=' . $this->request->get['filter_return_id'];
		}

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_product'])) {
			$url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_return_status_id'])) {
			$url .= '&filter_return_status_id=' . $this->request->get['filter_return_status_id'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
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
			'href' => $this->url->link('common/dashboard')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('seller/return', $url)
		);

		$data['add'] = $this->url->link('seller/return/add', $url);
		$data['delete'] = $this->url->link('seller/return/delete', $url);

		$data['returns'] = array();

		$filter_data = array(
			'filter_return_id'        => $filter_return_id,
			'filter_order_id'         => $filter_order_id,
			'filter_customer'         => $filter_customer,
			'filter_product'          => $filter_product,
			'filter_model'            => $filter_model,
			'filter_return_status_id' => $filter_return_status_id,
			'filter_date_added'       => $filter_date_added,
			'filter_date_modified'    => $filter_date_modified,
			'sort'                    => $sort,
			'order'                   => $order,
			'start'                   => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                   => $this->config->get('config_limit_admin')
		);

		$return_total = $this->model_multiseller_return->getTotalReturns($filter_data);

		$results = $this->model_multiseller_return->getReturns($filter_data);

		foreach ($results as $result) {
			$data['returns'][] = array(
				'return_id'     => $result['return_id'],
				'order_id'      => $result['order_id'],
				'customer'      => $result['customer'],
				'product'       => $result['product'],
				'model'         => $result['model'],
				'return_status' => $result['return_status'],
				'date_added'    => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'date_modified' => date($this->language->get('datetime_format'), strtotime($result['date_modified'])),
				'info'          => $this->url->link('seller/return/info', 'return_id=' . $result['return_id'] . $url)
			);
		}

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} elseif (isset($this->error['warning'])) {
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

		if (isset($this->request->get['filter_return_id'])) {
			$url .= '&filter_return_id=' . $this->request->get['filter_return_id'];
		}

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_product'])) {
			$url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_return_status_id'])) {
			$url .= '&filter_return_status_id=' . $this->request->get['filter_return_status_id'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_return_id'] = $this->url->link('seller/return', 'sort=r.return_id' . $url);
		$data['sort_order_id'] = $this->url->link('seller/return', 'sort=r.order_id' . $url);
		$data['sort_customer'] = $this->url->link('seller/return', 'sort=customer' . $url);
		$data['sort_product'] = $this->url->link('seller/return', 'sort=r.product' . $url);
		$data['sort_model'] = $this->url->link('seller/return', 'sort=r.model' . $url);
		$data['sort_status'] = $this->url->link('seller/return', 'sort=status' . $url);
		$data['sort_date_added'] = $this->url->link('seller/return', 'sort=r.date_added' . $url);
		$data['sort_date_modified'] = $this->url->link('seller/return', 'sort=r.date_modified' . $url);

		$url = '';

		if (isset($this->request->get['filter_return_id'])) {
			$url .= '&filter_return_id=' . $this->request->get['filter_return_id'];
		}

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_product'])) {
			$url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_return_status_id'])) {
			$url .= '&filter_return_status_id=' . $this->request->get['filter_return_status_id'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $return_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('seller/return', $url . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($return_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($return_total - $this->config->get('config_limit_admin'))) ? $return_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $return_total, ceil($return_total / $this->config->get('config_limit_admin')));

		$data['filter_return_id'] = $filter_return_id;
		$data['filter_order_id'] = $filter_order_id;
		$data['filter_customer'] = $filter_customer;
		$data['filter_product'] = $filter_product;
		$data['filter_model'] = $filter_model;
		$data['filter_return_status_id'] = $filter_return_status_id;
		$data['filter_date_added'] = $filter_date_added;
		$data['filter_date_modified'] = $filter_date_modified;

		$data['return_statuses'] = $this->model_multiseller_return->getReturnStatuses();

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('seller/return_list', $data));
	}

	public function info() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('seller/order');

			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()) {
            $this->response->redirect($this->url->link('seller/add'));
		}

        $this->load->language('seller/layout');

		$this->load->model('multiseller/return');

		if (isset($this->request->get['return_id'])) {
			$return_id = (int)$this->request->get['return_id'];
		} else {
			$return_id = 0;
		}
		$data['return_id'] = $return_id;

        $return_info = $this->model_multiseller_return->getReturn($this->request->get['return_id']);

		if ($return_info) {
            $this->load->language('seller/return');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['text_return'] = sprintf($this->language->get('text_return'), $return_id);

            $url = '';

            if (isset($this->request->get['filter_return_id'])) {
                $url .= '&filter_return_id=' . $this->request->get['filter_return_id'];
            }

            if (isset($this->request->get['filter_order_id'])) {
                $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
            }

            if (isset($this->request->get['filter_customer'])) {
                $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'],
                        ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_product'])) {
                $url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'],
                        ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES,
                        'UTF-8'));
            }

            if (isset($this->request->get['filter_return_status_id'])) {
                $url .= '&filter_return_status_id=' . $this->request->get['filter_return_status_id'];
            }

            if (isset($this->request->get['filter_date_added'])) {
                $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
            }

            if (isset($this->request->get['filter_date_modified'])) {
                $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
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
                'href' => $this->url->link('common/home')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('seller/return', $url)
            );

            $data['cancel'] = $this->url->link('seller/return', $url);

            if (!empty($return_info)) {
                $data['order_id'] = $return_info['order_id'];
            } else {
                $data['order_id'] = '';
            }

            if (!empty($return_info)) {
                $data['date_ordered'] = ($return_info['date_ordered'] != '0000-00-00' ? $return_info['date_ordered'] : '');
            } else {
                $data['date_ordered'] = '';
            }

            if (!empty($return_info)) {
                $data['customer'] = $return_info['customer'];
            } else {
                $data['customer'] = '';
            }

            if (!empty($return_info)) {
                $data['customer_id'] = $return_info['customer_id'];
            } else {
                $data['customer_id'] = '';
            }

            if (!empty($return_info)) {
                $data['fullname'] = $return_info['fullname'];
            } else {
                $data['fullname'] = '';
            }

            if (!empty($return_info)) {
                $data['email'] = $return_info['email'];
            } else {
                $data['email'] = '';
            }

            if (!empty($return_info)) {
                $data['telephone'] = $return_info['telephone'];
            } else {
                $data['telephone'] = '';
            }

            if (!empty($return_info)) {
                $data['product'] = $return_info['product'];
            } else {
                $data['product'] = '';
            }

            if (!empty($return_info)) {
                $data['product_id'] = $return_info['product_id'];
            } else {
                $data['product_id'] = '';
            }

            if (!empty($return_info)) {
                $data['model'] = $return_info['model'];
            } else {
                $data['model'] = '';
            }

            if (!empty($return_info)) {
                $data['quantity'] = $return_info['quantity'];
            } else {
                $data['quantity'] = '';
            }

            if (!empty($return_info)) {
                $data['opened'] = $return_info['opened'];
            } else {
                $data['opened'] = '';
            }

            if (!empty($return_info)) {
                $data['return_reason_id'] = $return_info['return_reason_id'];
            } else {
                $data['return_reason_id'] = '';
            }

            $data['return_reasons'] = $this->model_multiseller_return->getReturnReasons();

            if (!empty($return_info)) {
                $data['return_action_id'] = $return_info['return_action_id'];
            } else {
                $data['return_action_id'] = '';
            }

            $data['return_actions'] = $this->model_multiseller_return->getReturnActions();

            if (!empty($return_info)) {
                $data['comment'] = $return_info['comment'];
            } else {
                $data['comment'] = '';
            }

            if (!empty($return_info)) {
                $data['return_status_id'] = $return_info['return_status_id'];
            } else {
                $data['return_status_id'] = '';
            }

            $return_status_id 			= isset($return_info['return_status_id']) ? (int)$return_info['return_status_id'] : 0;
            $is_platform 				= isset($return_info['is_platform']) ? (int)$return_info['is_platform'] : 0;
            $return_statuses 			= $this->getReturnStatuses($return_status_id,-1,$is_platform);


			$data['back'] 				= $this->url->link('seller/return');
            $data['return_statuses'] 	= $return_statuses;
            $data['column_left'] 		= $this->load->controller('common/column_left');
            $data['column_right'] 		= $this->load->controller('common/column_right');
            $data['content_top'] 		= $this->load->controller('common/content_top');
            $data['content_bottom'] 	= $this->load->controller('common/content_bottom');
            $data['footer'] 			= $this->load->controller('common/footer');
            $data['header'] 			= $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('seller/return_info', $data));
        } else {
			return new Action('error/not_found');
        }
	}

	private function getReturnStatuses($return_status_id = 0,$update_status_id = -1,$is_platform = 0)
	{
		if($return_status_id <= 0 || $update_status_id == 0) return [];

        $notAllow 					= [5,7,8,9];
        switch ($return_status_id) {
        	case 1: $notAllow 		= array_merge($notAllow,[1,3]);break;
        	case 2: $notAllow 		= array_merge($notAllow,[1,2,4]);break;
        	case 6: $notAllow 		= array_merge($notAllow,[1,2,4,6,10]);break;
        	case 10: $notAllow 		= array_merge($notAllow,[1,2,3,4,6,10]);break;
        	default:$return_status_id = 0;break;
        }

        //平台处理后的选项显示
        if ($is_platform == 1 ) {
        	if ($return_status_id == 2) {
        		$notAllow 		= array_merge($notAllow,[1,2,3,4,10]);
        	}else{
        		$notAllow 		= array_merge($notAllow,[1,2,3,4,6,10]);
        	}
        }

        $return_statuses 			= $this->model_multiseller_return->getReturnStatuses();

        $text_status 				= [4=>'拒绝'];

        foreach ($return_statuses as $key => $value) {
        	if (in_array($value['return_status_id'], $notAllow) || $return_status_id <= 0) {
        		unset($return_statuses[$key]);
        	}else{
        		$return_statuses[$key]['name'] = isset($text_status[$value['return_status_id']]) ? $text_status[$value['return_status_id']] : $value['name'];
        	}
        }

        if ($update_status_id > 0) {
        	foreach ($return_statuses as $kk => $vv) {
        		if ($vv['return_status_id'] == $update_status_id) {
        			return true;
        		}
        	}

        	return true;
        }

        return $return_statuses;
	}

	public function history() {
		$this->load->language('seller/return');

		$this->load->model('multiseller/return');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['histories'] = array();

		$results = $this->model_multiseller_return->getReturnHistories($this->request->get['return_id'], ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['histories'][] = array(
				'notify'     => $result['notify'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'status'     => $result['status'],
				'comment'    => nl2br($result['comment']),
				'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added']))
			);
		}

		$history_total = $this->model_multiseller_return->getTotalReturnHistories($this->request->get['return_id']);

		$pagination = new Pagination();
		$pagination->total = $history_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('seller/return/history', 'return_id=' . $this->request->get['return_id'] . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));

		$this->response->setOutput($this->load->view('seller/return_history', $data));
	}

	public function addHistory() {
		$this->load->language('seller/return');

		$json = array();

		if (!$this->customer->isLogged() || !$this->customer->isSeller()) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('multiseller/return');
			if (!isset($this->request->post['comment']) || empty($this->request->post['comment'])) {
				$json['error'] = $this->language->get('error_return_comment');
			}

			$return_id 				= isset($this->request->get['return_id']) ? (int)$this->request->get['return_id'] : 0;
			$return_status_id 		= isset($this->request->post['return_status_id']) ? (int)$this->request->post['return_status_id'] : 0;
			$return_info 			= $this->model_multiseller_return->getReturn($return_id);
            $is_platform 			= isset($return_info['is_platform']) ? (int)$return_info['is_platform'] : 0;

			if (empty($return_info)) {
				$json['error'] = $this->language->get('error_return_exists');
			}

			if (!$this->getReturnStatuses($return_info['return_status_id'],$return_status_id,$is_platform)) {
				$json['error'] = $this->language->get('error_return_status');
			}

			if ($return_info['return_status_id'] == 3) {
				$json['error'] = $this->language->get('error_return_status_3');
			}

			//如果是平台处理中 则不能操作
			if ($return_info['return_status_id'] == 9) {
				$json['error'] = $this->language->get('error_return_status_9');
			}

			if ($return_info['return_status_id'] == 10) {
				$json['error'] = $this->language->get('error_return_status_10');
			}

			$this->load->model('multiseller/order');
        	$order_info       = $this->model_multiseller_order->getSuborderByCustomerIdForMs($return_info['order_id'],$return_info['seller_id'],$return_info['customer_id']);

        	if (empty($return_info)) {
				$json['error'] = $this->language->get('error_order_exists');
			}

			if ( !isset($json['error']) || empty($json['error'])) {
				
				$overtime 		= 0;
				$payment_code 	= isset($order_info['payment_code']) ? $order_info['payment_code'] : '';
				//根据状态处理
				switch ($return_status_id) {
					case 2: //等待商品寄回
						$overtime 		= 10;//提示用户10天内将物品寄回
						break;
					case 3: //已退款
						$overtime 		= 0;
						break;
					case 4: //拒绝
						//判断拒绝次数 次数到达2次 需要上升平台仲裁
						$this->load->model('account/return');
						$refuse_nums    = $this->model_account_return->getReturnHistoryForRefuseNums($return_id);
						if (($refuse_nums + 1) >= 2) {
							$is_platform 	= 1;//置为平台处理状态
							$overtime 		= 0;
						}
						break;
					case 6://已收到退货，退款
						$return_status_id 	= 10;
					case 10://退款中
						//执行退款
						$pay_code 		= isset($order_info['pay_code']) ? $order_info['pay_code'] : '';
						$amount 		= isset($return_info['return_money']) ? $return_info['return_money'] : '';
						$currency 		= isset($order_info['currency_code']) ? $order_info['currency_code'] : '';

						if (empty($pay_code)) {
							$json['error'] = $this->language->get('error_pay_code');
						}

						if (empty($currency)) {
							$json['error'] = $this->language->get('error_pay_currency');
						}

						if ( !isset($json['error']) || empty($json['error']))
						{
							$rdata 				= [];
							$rdata['pay_code'] 	= $pay_code;
							$rdata['amount'] 	= $amount;
							$rdata['currency'] 	= $currency;

							$res 		= $this->load->controller('extension/payment/' . $payment_code . '/returnPay',$rdata);
							if (empty($res) || $res == 'fail') {
								$json['error'] = $this->language->get('error_return_api');
							}else if ($res == 'success') {
								//设置退款数量
								$this->model_multiseller_order->setReturnByOrderProductId($return_info['product_id']);

								//设置部分退款
								$this->model_multiseller_order->setReturnByOrderSn($order_info['order_sn'],1);

								$notReturn 	= $this->model_multiseller_order->getNotReturnCount($return_info['order_id'],$return_info['seller_id']);
								if ($notReturn <= 0) {
									//设置全部退款
									$this->model_multiseller_order->setReturnByOrderSn($order_info['order_sn'],2);
								}
							}
						}

						break;
					default:
						$json['error'] = $this->language->get('error_return_status'); break;
				}

			}
			
			if (!isset($json['error']) || empty($json['error'])) {
				
        		$this->model_multiseller_return->editReturnOvertime($return_id, ($overtime > 0 ? (time() + 86400*$overtime) : 0));

        		$history_data                           = [];
		        $history_data['return_id']              = $return_id;
		        $history_data['return_status_id']       = $return_status_id;
		        $history_data['comment']                = $this->request->post['comment'];
		        $history_data['customer_id']            = $this->customer->getId();
		        $history_data['utype']                  = 2;

                $this->model_multiseller_return->addReturnHistoryForMs($history_data);
                
                if ($is_platform == 1) {
                	sleep(2);
        			$this->model_multiseller_return->editReturnIsPlatform($return_id, 1);

        			$history_data                           = [];
			        $history_data['return_id']              = $return_id;
			        $history_data['return_status_id']       = 9;
			        $history_data['comment']                = 'Platform Intervention Processing';
			        $history_data['customer_id']            = 0;
			        $history_data['utype']                  = 3;

                	$this->model_multiseller_return->addReturnHistoryForMs($history_data);
				}

				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}