<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-10 14:12:00
 * @modified         2016-11-10 14:12:00
 */

class ControllerCatalogOreview extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('catalog/review');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/oreview');

        $this->getList();
    }

	public function add() {
		$this->load->language('catalog/review');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/oreview');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_oreview->addReview($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_product'])) {
				$url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_author'])) {
				$url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
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

			$this->response->redirect($this->url->link('catalog/oreview', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

    protected function getList()
    {
        $this->document->addScript('view/javascript/layer/layer.js');
        if (isset($this->request->get['filter_product'])) {
            $filter_product = $this->request->get['filter_product'];
        } else {
            $filter_product = null;
        }

        if (isset($this->request->get['filter_customer'])) {
            $filter_customer = $this->request->get['filter_customer'];
        } else {
            $filter_customer = null;
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = null;
        }

        if (isset($this->request->get['filter_date_added'])) {
            $filter_date_added = $this->request->get['filter_date_added'];
        } else {
            $filter_date_added = null;
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'r.date_added';
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product='.urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer='.urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status='.$this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added='.$this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort='.$this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order='.$this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page='.$this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token']),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].$url),
        );

		$data['add'] = $this->url->link('catalog/oreview/add', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['delete'] = $this->url->link('catalog/oreview/delete', 'user_token='.$this->session->data['user_token'].$url);

        $data['reviews'] = array();

        $filter_data = array(
            'filter_product' => $filter_product,
            'filter_customer' => $filter_customer,
            'filter_status' => $filter_status,
            'filter_date_added' => $filter_date_added,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin'),
        );

        $review_total = $this->model_catalog_oreview->getTotalReviews($filter_data);

        $results = $this->model_catalog_oreview->getReviews($filter_data);

        $this->load->model('customer/customer');
        foreach ($results as $result) {
            $customer_info = $this->model_customer_customer->getCustomer($result['customer_id']);
            $data['reviews'][] = array(
                'order_product_review_id' => $result['order_product_review_id'],
                'name' => $result['name'],
                'author' => $customer_info ? '(#' . $result['customer_id'] . ')' . $customer_info['fullname'] : $result['author'],
                'rating' => $result['rating'],
                'status' => ($result['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'replied' => $this->model_catalog_oreview->getReviewReply($result['order_product_review_id']) ? true : false,
                'edit' => $this->url->link('catalog/oreview/edit', 'user_token='.$this->session->data['user_token'].'&order_product_review_id='.$result['order_product_review_id'].$url),
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
            $data['selected'] = (array) $this->request->post['selected'];
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
            $url .= '&page='.$this->request->get['page'];
        }

        $data['sort_product'] = $this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].'&sort=p.name'.$url);
        $data['sort_customer'] = $this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].'&sort=customer'.$url);
        $data['sort_rating'] = $this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].'&sort=r.rating'.$url);
        $data['sort_status'] = $this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].'&sort=r.status'.$url);
        $data['sort_date_added'] = $this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].'&sort=r.date_added'.$url);

        $url = '';

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product='.urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer='.urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status='.$this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added='.$this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort='.$this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order='.$this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $review_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].$url.'&page={page}');

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($review_total - $this->config->get('config_limit_admin'))) ? $review_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $review_total, ceil($review_total / $this->config->get('config_limit_admin')));

        $data['filter_product'] = $filter_product;
        $data['filter_customer'] = $filter_customer;
        $data['filter_status'] = $filter_status;
        $data['filter_date_added'] = $filter_date_added;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/oreview_list', $data));
    }

    public function edit()
    {
        $this->load->language('catalog/review');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/oreview');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_oreview->editReview($this->request->get['order_product_review_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

			if (isset($this->request->get['filter_product'])) {
				$url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_author'])) {
				$url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
			}

            if (isset($this->request->get['filter_product'])) {
                $url .= '&filter_product='.urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_customer'])) {
                $url .= '&filter_customer='.urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status='.$this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_added'])) {
                $url .= '&filter_date_added='.$this->request->get['filter_date_added'];
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort='.$this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order='.$this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page='.$this->request->get['page'];
            }

            $this->response->redirect($this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].$url));
        }

        $this->getForm();
    }

    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'catalog/oreview')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (utf8_strlen($this->request->post['text']) < 1) {
            $this->error['text'] = $this->language->get('error_text');
        }

        if (!isset($this->request->post['rating']) || $this->request->post['rating'] < 0 || $this->request->post['rating'] > 5) {
            $this->error['rating'] = $this->language->get('error_rating');
        }

        return !$this->error;
    }

    protected function getForm()
    {
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_form'] = !isset($this->request->get['order_product_review_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['author'])) {
            $data['error_author'] = $this->error['author'];
        } else {
            $data['error_author'] = '';
        }

        if (isset($this->error['product'])) {
            $data['error_product'] = $this->error['product'];
        } else {
            $data['error_product'] = '';
        }

        if (isset($this->error['date_added'])) {
            $data['error_date_added'] = $this->error['date_added'];
        } else {
            $data['error_date_added'] = '';
        }

        if (isset($this->error['text'])) {
            $data['error_text'] = $this->error['text'];
        } else {
            $data['error_text'] = '';
        }

        if (isset($this->error['rating'])) {
            $data['error_rating'] = $this->error['rating'];
        } else {
            $data['error_rating'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product='.urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer='.urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status='.$this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added='.$this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort='.$this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order='.$this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page='.$this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token']),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].$url),
        );

		if (!isset($this->request->get['order_product_review_id'])) {
			$data['action'] = $this->url->link('catalog/oreview/add', 'user_token=' . $this->session->data['user_token'] . $url);
		} else {
            $data['action'] = $this->url->link('catalog/oreview/edit', 'user_token='.$this->session->data['user_token'].'&order_product_review_id='.$this->request->get['order_product_review_id'].$url);
		}

        $data['cancel'] = $this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].$url);

        if (isset($this->request->get['order_product_review_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $review_info = $this->model_catalog_oreview->getReview($this->request->get['order_product_review_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('catalog/product');

        if (isset($this->request->post['author'])) {
            $data['author'] = $this->request->post['author'];
        } elseif (!empty($review_info)) {
            $data['author'] = $review_info['author'];
        } else {
            $data['author'] = '';
        }

        if (isset($this->request->post['customer_id'])) {
            $data['customer_id'] = $this->request->post['customer_id'];
        } elseif (!empty($review_info)) {
            $data['customer_id'] = $review_info['customer_id'];
        } else {
            $data['customer_id'] = 0;
        }

        if (isset($this->request->post['product'])) {
            $data['product'] = $this->request->post['product'];
        } elseif (!empty($review_info)) {
            $data['product'] = $review_info['product'];
        } else {
            $data['product'] = '';
        }

        if (isset($this->request->post['product_id'])) {
            $data['product_id'] = $this->request->post['product_id'];
        } elseif (!empty($review_info)) {
            $data['product_id'] = $review_info['product_id'];
        } else {
            $data['product_id'] = '';
        }

        if (isset($this->request->post['date_added'])) {
            $data['date_added'] = $this->request->post['date_added'];
        } elseif (!empty($review_info)) {
            $data['date_added'] = $review_info['date_added'];
        } else {
            $data['date_added'] = '';
        }

        if (isset($this->request->post['text'])) {
            $data['text'] = $this->request->post['text'];
        } elseif (!empty($review_info)) {
            $data['text'] = $review_info['text'];
        } else {
            $data['text'] = '';
        }

        if (isset($this->request->post['rating'])) {
            $data['rating'] = $this->request->post['rating'];
        } elseif (!empty($review_info)) {
            $data['rating'] = $review_info['rating'];
        } else {
            $data['rating'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($review_info)) {
            $data['status'] = $review_info['status'];
        } else {
            $data['status'] = '';
        }

        if (!empty($review_info)) {
            $data['images'] = $this->model_catalog_oreview->getReviewImage($this->request->get['order_product_review_id']);
        } else {
            $data['images'] = array();
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/oreview_form', $data));
    }

    public function delete()
    {
        $this->load->language('catalog/review');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/oreview');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $order_product_review_id) {
                $this->model_catalog_oreview->deleteReview($order_product_review_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_product'])) {
                $url .= '&filter_product='.urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_customer'])) {
                $url .= '&filter_customer='.urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status='.$this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_added'])) {
                $url .= '&filter_date_added='.$this->request->get['filter_date_added'];
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort='.$this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order='.$this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page='.$this->request->get['page'];
            }

            $this->response->redirect($this->url->link('catalog/oreview', 'user_token='.$this->session->data['user_token'].$url));
        }

        $this->getList();
    }

	public function import() {
		$this->load->language('catalog/oreview');

		$json = array();

		// Check user has permission
		if (!$this->user->hasPermission('modify', 'catalog/oreview')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (empty($this->request->files['import']['name']) || !is_file($this->request->files['import']['tmp_name'])) {
			$json['error'] = $this->language->get('error_upload');
		}

		if (!$json) {
			// Sanitize the filename
			$filename = basename(html_entity_decode($this->request->files['import']['name'], ENT_QUOTES, 'UTF-8'));

			if ((utf8_strlen($filename) < 2) || (utf8_strlen($filename) > 64)) {
				$json['error'] = $this->language->get('error_filename');
			}

			// Allowed file extension types
			if (strtolower(substr(strrchr($filename, '.'), 1)) != 'csv') {
				$json['error'] = $this->language->get('error_filetype');
			}
		}

		if (!$json) {
			move_uploaded_file($this->request->files['import']['tmp_name'], DIR_STORAGE . 'upload/' . $filename);

			$this->load->model('catalog/oreview');

            $file = DIR_STORAGE . 'upload/' . $filename;
			$result = $this->model_catalog_oreview->import($file);

            if ($result === true) {
                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error'] = $result;
            }
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

    protected function validateDelete()
    {
        if (!$this->user->hasPermission('modify', 'catalog/oreview')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function reply() {
        $this->load->language('catalog/review');

        $review_id = array_get($this->request->get, 'review_id', 0);

        $data['review_id'] = $review_id;

        $data['base'] = HTTP_SERVER;
        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('catalog/oreview_reply', $data));
    }

    public function reply_save() {
		$this->load->language('catalog/review');

		$json = array();

        if (!$this->user->hasPermission('modify', 'catalog/oreview')) {
            $json['error'] = t('error_permission');
        }

        $this->load->model('catalog/oreview');
        if (!$this->model_catalog_oreview->getReview(array_get($this->request->get, 'review_id', 0))) {
            $json['error'] = t('error_no_review');
        }

        if ($this->model_catalog_oreview->getReviewReply(array_get($this->request->get, 'review_id', 0))) {
            $json['error'] = t('error_already_reply');
        }

        if (!$json) {
            $this->model_catalog_oreview->addReviewReply(array_get($this->request->get, 'review_id', 0), $this->request->post);
            $json['success'] = t('text_reply_success');
        }

        $this->jsonOutput($json);
    }
}
