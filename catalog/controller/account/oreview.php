<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-10 14:12:00
 * @modified         2016-11-10 14:12:00
 */

class ControllerAccountOreview extends Controller
{
    private $error = array();

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->language('account/oreview');
        $this->load->model('account/oreview');
    }

    /*
    * 获取评论订单商品列表
    */
    public function index()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/oreview');
            $this->response->redirect($this->url->link('account/login'));
        }

        $this->document->setTitle(t('heading_title'));

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->addHome();
        $breadcrumbs->add(t('text_account'), $this->url->link('account/account'));
        $breadcrumbs->add(t('heading_title'), $this->url->link('account/oreview', $this->url->getQueriesOnly(['page'])));
        $data['breadcrumbs'] = $breadcrumbs->all();

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        if (isset($this->session->data['warning'])) {
            $data['warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }

        $page = (int)array_get($this->request->get, 'page', 1);
        $limit = 10;

        $data['oreview_products'] = array();
        $filter_data = array(
            'filter_customer_id' => $this->customer->getId(),
            'filter_reviewed' => (int)array_get($this->request->get, 'reviewed'),
            'start' => ($page - 1) * $limit,
            'limit' => $limit,
        );

        $oreview_total = $this->model_account_oreview->getTotalOreviews($filter_data);
        $results = $this->model_account_oreview->getOreviews($filter_data);

        $data['oreviews'] = array();
        foreach ($results as $result) {
            if (!$result['reviewed']) {
                $add = $this->url->link('account/oreview/add', 'order_id=' . $result['order_id'] . '&order_product_id=' . $result['order_product_id']);
                $info = '';
            } else {
                $add = '';
                $info = $this->url->link('account/oreview/info', 'order_id=' . $result['order_id'] . '&order_product_id=' . $result['order_product_id']);
            }

            $data['oreviews'][] = array(
                'order_id'   => $result['order_id'],
                'name'       => $result['name'],
                'product_link'=> $this->url->link('product/product', 'product_id=' . $result['product_id']),
                'text'       => $result['text'],
                'rating'     => $result['rating'],
                'date_added' => date(t('datetime_format'), strtotime($result['date_added'])),
                'reviewed'   => $result['reviewed'],
                'add'        => $add,
                'info'       => $info
            );
        }

        $pagination = new Pagination();
        $pagination->total = $oreview_total;
        $pagination->page = $page;
        $pagination->limit = 10;
        $pagination->url = $this->url->link('account/oreview', 'page={page}');

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($oreview_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($oreview_total - 10)) ? $oreview_total : ((($page - 1) * 10) + 10), $oreview_total, ceil($oreview_total / 10));

        $data['continue'] = $this->url->link('account/account');

        $data['active_tab'] = (int)array_get($this->request->get, 'reviewed') ? 'reviewed' : 'unreviewed';

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/oreview_product_list', $data));
    }

    /*
    * 添加评论的表单。
    */
    public function add()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/oreview');
            $this->response->redirect($this->url->link('account/login'));
        }

        if (!$order_id = (int)array_get($this->request->get, 'order_id')) {
            $this->session->data['warning'] = t('error_not_found');
            $this->response->redirect($this->url->link('account/oreview'));
        }

        if (!$order_product_id = (int)array_get($this->request->get, 'order_product_id')) {
            $this->session->data['warning'] = t('error_not_found');
            $this->response->redirect($this->url->link('account/oreview'));
        }

        // Customer can only review on his own orders
        $this->load->model('account/order');
        $order_product = $this->model_account_order->getOrderProduct($order_id, $order_product_id);
        if (!$order_product) {
            $this->session->data['warning'] = t('error_not_found');
            $this->response->redirect($this->url->link('account/oreview'));
        }

        // An order product can be reviewed only once
//        if ($this->model_account_oreview->isReviewed($order_product_id)) {
//            $this->session->data['warning'] = t('error_alredy_reviewed');
//            $this->response->redirect($this->url->link('account/oreview'));
//        }

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $result = $this->model_account_oreview->addOreview($order_product_id, $this->request->post);
            if ($result) {
                $this->session->data['success'] = $this->config->get('config_review_approve') ? t('text_success_unapproved') : t('text_success_approved');
            } else {
                if (array_get($this->request->get, 'additional', 0)) {
                    $this->session->data['warning'] = t('error_alredy_additional');
                } else {
                    $this->session->data['warning'] = t('error_alredy_reviewed');
                }
            }
            if (array_get($this->request->get, 'additional', 0)) {
                $redirect = $this->url->link('account/oreview/info', 'order_id=' . $order_id . '&order_product_id=' . $order_product_id);
                $this->response->redirect($redirect);
            }
            $this->response->redirect($this->url->link('account/oreview'));
        }

        $this->document->setTitle(t('heading_title'));

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->addHome();
        $breadcrumbs->add(t('text_account'), $this->url->link('account/account'));
        $breadcrumbs->add(t('heading_title'), $this->url->link('account/oreview', $this->url->getQueriesOnly(['order_id', 'order_product_id'])));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $data['error'] = $this->error;

        // Captcha
        if (config(config('config_captcha') . '_status') && in_array('review', (array)config('config_captcha_page'))) {
            $data['captcha'] = $this->load->controller('captcha/' . config('config_captcha'), $this->error);
        }

        $additional = array_get($this->request->get, 'additional', 0);
        $data['additional'] = (int)$additional;
        $data['action'] = $this->url->link('account/oreview/add', "order_id={$order_id}&order_product_id={$order_product_id}&additional={$additional}");

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/oreview_form', $data));
    }

    public function validate()
    {
        if (!(isset($this->request->get['additional']) && (int)$this->request->get['additional']) && $this->model_account_oreview->isReviewed($this->request->get['order_product_id'])) {
            $this->error['warning'] = $this->language->get('error_alredy_reviewed');
        }

        if ((utf8_strlen($this->request->post['text']) < 5) || (utf8_strlen($this->request->post['text']) > 1000)) {
            $this->error['text'] = $this->language->get('error_text');
        }

        if (!(isset($this->request->get['additional']) && (int)$this->request->get['additional'])) {
            if (empty($this->request->post['rating']) || $this->request->post['rating'] < 0 || $this->request->post['rating'] > 5) {
                $this->error['rating'] = $this->language->get('error_rating');
            }
        }

        // Captcha
        if (config(config('config_captcha') . '_status') && in_array('review', (array) config('config_captcha_page'))) {
            $captcha = $this->load->controller('captcha/' . config('config_captcha') . '/validate');

            if ($captcha) {
                $this->error['captcha'] = $captcha;
            }
        }

        return !$this->error;
    }

    public function info()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/oreview');
            $this->response->redirect($this->url->link('account/login'));
        }

        if (!(int)array_get($this->request->get, 'order_product_id') || !(int)array_get($this->request->get, 'order_id')) {
            $this->session->data['warning'] = t('error_not_found');
            $this->response->redirect($this->url->link('account/oreview'));
        }
        $order_product_id = (int)array_get($this->request->get, 'order_product_id');
        $order_id = (int)array_get($this->request->get, 'order_id');

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        if (isset($this->session->data['warning'])) {
            $data['warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }

        $reviews = $this->model_account_oreview->getOreviewsByOrderProductId($order_product_id);
        if (!$reviews) {
            $this->session->data['warning'] = t('error_not_found');
            $this->response->redirect($this->url->link('account/oreview'));
        }

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->addHome();
        $breadcrumbs->add(t('text_account'), $this->url->link('account/account'));
        $breadcrumbs->add(t('heading_title'), $this->url->link('account/oreview', $this->url->getQueriesOnly(['page'])));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $this->document->addStyle('catalog/view/javascript/jquery/fancybox/jquery.fancybox.css');
        $this->document->addScript('catalog/view/javascript/jquery/fancybox/jquery.fancybox.pack.js');
        $this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.min.js');

        $data['reviews'] = array();
        foreach ($reviews as $review) {
            $reply_info = $this->model_account_oreview->getReviewReply($review['order_product_review_id']);
            $data['reviews'][] = array(
                'author'     => $review['author'],
                'rating'     => $review['rating'],
                'date_added' => $review['date_added'],
                'text'       => $review['text'],
                'date_added' => $review['date_added'],
                'status'     => $review['status'],
                'is_additional' => $review['parent_id'] == 0 ? false : true,
                'images'     => $this->model_account_oreview->getOreviewImages($review['order_product_review_id']),
                'reply'      => isset($reply_info['content']) ? $reply_info['content'] : ''
            );
        }

        //print_r($reviews);exit();
        //是否可以继续评价
        $data['is_reviews']     = 0;
        $review1                =  $this->model_account_oreview->getMasterReviewByOrderProductId($order_product_id);
        if (!empty($review1)) {
            $review2                   = $this->model_account_oreview->isAdditionalReviewed($review1['order_product_review_id']);
            $data['is_reviews']        = !empty($review2) ? 1 : 0;
        }

        $data['add_review'] = $this->url->link('account/oreview/add', 'order_id=' . $order_id . '&order_product_id=' . $order_product_id . '&additional=1');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/order_review_info', $data));
    }

    public function review()
    {
        $this->load->language('product/product');

        $this->load->model('account/oreview');

        $data['text_no_reviews'] = $this->language->get('text_no_reviews');

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['reviews'] = array();

        $review_total = $this->model_account_oreview->getTotalOreviewsByProductId($this->request->get['product_id']);

        $results = $this->model_account_oreview->getOreviewsByProductId($this->request->get['product_id'], ($page - 1) * 5, 5);

        $this->load->model('account/customer');
        foreach ($results as $result) {
            $additionals = array();
            $additional_reviews = $this->model_account_oreview->getAdditionalReviews($result['order_product_review_id']);
            foreach ($additional_reviews as $additional_review) {
                $result_images = $this->model_account_oreview->getOreviewImages($additional_review['order_product_review_id']);
                $additional_images = array();
                foreach ($result_images as $result_image) {
                    if (image_exists($result_image['filename'])) {
                        $additional_images[] = $this->url->imageLink($result_image['filename']);
                    }
                }
                $additional_reply_info = $this->model_account_oreview->getReviewReply($additional_review['order_product_review_id']);
                $additionals[] = array(
                    'text'       => nl2br($additional_review['text']),
                    'images'     => $additional_images,
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'reply'      => isset($additional_reply_info['content']) ? $additional_reply_info['content'] : ''
                );
            }

            $result_images = $this->model_account_oreview->getOreviewImages($result['order_product_review_id']);
            $images = array();
            foreach ($result_images as $result_image) {
                if (image_exists($result_image['filename'])) {
                    $images[] = $this->url->imageLink($result_image['filename']);
                }
            }
            $customer_info = $this->model_account_customer->getCustomer($result['customer_id']);
            $customer_name = $customer_info ? $customer_info['fullname'] : '';
            $reply_info = $this->model_account_oreview->getReviewReply($result['order_product_review_id']);
            $data['reviews'][] = array(
                'author'     => $result['author'] ? $result['author'] : $customer_name,
                'text'       => nl2br($result['text']),
                'images'     => $images,
                'rating'     => (int) $result['rating'],
                'additionals'=> $additionals,
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'reply'      => isset($reply_info['content']) ? $reply_info['content'] : ''
            );
        }

        $pagination = new Pagination();
        $pagination->total = $review_total;
        $pagination->page = $page;
        $pagination->limit = 5;
        $pagination->url = $this->url->link('account/oreview/review', 'product_id='.$this->request->get['product_id'].'&page={page}');

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

        $this->response->setOutput($this->load->view('account/oreview', $data));
    }
}
