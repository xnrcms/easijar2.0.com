<?php

use Models\Faq\Category;
use Models\Faq\Faq;

/**
 * description
 *
 * @copyright        2018/7/10 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Eric Yang <yangyw@opencart.cn>
 * @created          2018/7/10 上午10:37
 * @modified         2018/7/10 上午10:37
 */
class ControllerFaqFaq extends Controller
{

    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->language->load('faq/faq');
    }

    public function index()
    {
        $this->document->setTitle(t('text_faq'));
        $this->getList();
    }

    public function getList()
    {
        $sort = array_get($this->request->get, 'sort', 'category_id');
        $order = array_get($this->request->get, 'order', 'desc');
        $page = (int)array_get($this->request->get, 'page', 1);
        $limit = config('config_limit_admin');

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_faq'), $this->url->link('faq/faq', $this->url->getQueriesOnly()));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);

        $data['add'] = $this->url->link('faq/faq/add', $url);
        $data['delete'] = $this->url->link('faq/faq/delete', $url);

        $faq_total = Faq::count();
        $filter_data = array(
            'sort' => $sort,
            'order' => $order,
            'offset' => ($page - 1) * $limit,
            'limit' => $limit,
        );

        $data['faqs'] = Faq::getCollection($filter_data);

        if ($this->session->get('error')) {
            $data['error'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        if ($this->session->get('success')) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $url = [];
        if ($page > 1) {
            $url['page'] = $page;
        }

        foreach (['faq_id', 'question', 'sort_order'] as $column) {
            if ($sort == $column) {
                $url['order'] = $order == 'asc' ? 'desc' : 'asc';
            } else {
                $url['order'] = 'desc';
            }
            $url['sort'] = $column;
            $data["sort_{$column}"] = $this->url->link('faq/faq', $url);
        }

        // Pagination
        $url = $this->url->getQueriesOnly(['sort', 'order']);
        $url['page'] = '{page}';

        $pagination = new Pagination();
        $pagination->total = $faq_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('faq/faq', $url);
        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf(t('text_pagination'), ($faq_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($faq_total - $limit)) ? $faq_total : ((($page - 1) * $limit) + $limit), $faq_total, ceil($faq_total / $limit));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('faq/faq_list', $data));
    }

    public function add()
    {
        $this->document->setTitle(t('text_faq'));
        if (($this->request->server['REQUEST_METHOD'] != 'POST') || !$this->validateForm()) {
            $this->getForm();
            return;
        }

        $faq = Faq::create([
            'category_id' => array_get($this->request->post, 'category_id', '0'),
            'status' => array_get($this->request->post, 'status', '0'),
            'sort_order' => array_get($this->request->post, 'sort_order', '0')
        ]);

        foreach ($this->request->post['description'] as $language_id => $description) {
            $description['language_id'] = $language_id;
            $faq->descriptions()->create($description);
        }

        $this->session->data['success'] = t('text_faq_success');
        $this->response->redirect($this->url->link('faq/faq', $this->url->getQueriesOnly(['sort', 'page', 'order'])));
    }

    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'faq/faq')) {
            $this->error['warning'] = t('error_permission');
        }

        foreach ($this->request->post['description'] as $language_id => $value) {
            if ((utf8_strlen($value['question']) < 2) || (utf8_strlen($value['question']) > 255)) {
                $this->error['question'][$language_id] = t('error_name');
            }
        }

        if ($this->request->post['category_id'] == 0) {
            $this->error['category_id'] = t('error_category');
        }

        return !$this->error;
    }

    public function getForm()
    {
        $faq_id = array_get($this->request->get, 'faq_id', 0);
        $data['error'] = $this->error;

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_faq'), $this->url->link('faq/faq', $url));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page', 'faq_id']);
        if ($faq_id) {
            $this->document->setTitle(t('text_edit'));
            $data['action'] = $this->url->link('faq/faq/edit', $url);
        } else {
            $this->document->setTitle(t('text_add'));
            $data['action'] = $this->url->link('faq/faq/add', $url);
        }

        if ($faq_id && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $data['faq'] = Faq::find($faq_id);
        }

        $data['categories'] = Category::getCollection(array());

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);
        $data['cancel'] = $this->url->link('faq/faq', $url);

        $this->document->addScript('view/javascript/summernote/summernote.js', 'footer');
        $this->document->addScript('view/javascript/summernote/opencart.js', 'footer');
        $this->document->addStyle('view/javascript/summernote/summernote.css');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('faq/faq_form', $data));
    }

    public function edit()
    {
        $this->document->setTitle(t('text_faq'));
        if (($this->request->server['REQUEST_METHOD'] != 'POST') || !$this->validateForm()) {
            $this->getForm();
            return;
        }

        $faq_id = (int)array_get($this->request->get, 'faq_id', 0);
        $faq = Faq::updateOrCreate(
            ['faq_id' => $faq_id],
            [
                'status' => array_get($this->request->post, 'status', '0'),
                'category_id' => array_get($this->request->post, 'category_id', '0'),
                'sort_order' => array_get($this->request->post, 'sort_order', '0')
            ]
        );

        $faq->descriptions()->delete();
        foreach ($this->request->post['description'] as $language_id => $description) {
            $description['language_id'] = $language_id;
            $faq->descriptions()->create($description);
        }

        // Redirect to list page
        $this->session->data['success'] = t('text_faq_success');
        $this->response->redirect($this->url->link('faq/faq', $this->url->getQueriesOnly(['sort', 'page', 'order'])));
    }

    public function delete()
    {
        $url = $this->url->getQueriesOnly(['sort', 'page', 'order']);
        if (!$this->validateDelete()) {
            $this->session->data['error'] = $this->error['warning'];
            $this->response->redirect($this->url->link('faq/faq' . $url));
        }

        if ($faq_id = (int)array_get($this->request->get, 'faq_id')) {
            $faq = Faq::find($faq_id);
            if ($faq) {
                $faq->descriptions()->delete();
                $faq->delete();
            }
        }

        if (isset($this->request->post['selected'])) {
            $selected = $this->request->post['selected'];
            foreach ($selected as $item) {
                $faq = Faq::find($item);
                if ($faq) {
                    $faq->descriptions()->delete();
                    $faq->delete();
                }
            }
        }

        $this->session->data['success'] = t('text_faq_success');
        $this->response->redirect($this->url->link('faq/faq', $url));
    }

    protected function validateDelete()
    {
        if (!$this->user->hasPermission('modify', 'faq/category')) {
            $this->error['warning'] = t('error_permission');
        }

        return !$this->error;
    }
}