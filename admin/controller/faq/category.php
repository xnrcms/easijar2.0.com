<?php

use Models\Faq\Category;
use Models\Faq\Category\Path;
use Models\Faq\Category\Store;
use Models\SeoUrl;

/**
 * description
 *
 * @copyright        2018/7/9 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Eric Yang <yangyw@opencart.cn>
 * @created          2018/7/9 下午2:51
 * @modified         2018/7/9 下午2:51
 */
class ControllerFaqCategory extends Controller
{
    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->language->load('faq/faq');
    }

    public function index()
    {
        $this->document->setTitle(t('text_faq_category'));
        $this->getList();
    }

    public function getList()
    {
        $sort = array_get($this->request->get, 'sort', 'category_id');
        $order = array_get($this->request->get, 'order', 'asc');
        $page = (int)array_get($this->request->get, 'page', 1);
        $limit = config('config_limit_admin');

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_faq_category'), $this->url->link('faq/category', $this->url->getQueriesOnly()));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);

        $data['add'] = $this->url->link('faq/category/add', $url);
        $data['delete'] = $this->url->link('faq/category/delete', $url);

        $category_total = Category::count();
        $filter_data = array(
            'sort' => $sort,
            'order' => $order,
            'offset' => ($page - 1) * $limit,
            'limit' => $limit,
        );

        $data['categories'] = Category::getCollection($filter_data);

        if ($this->session->get('error')) {
            $data['error'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        if ($this->session->get('success')) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        // Column Sort & Order links
        $url = [];
        if ($page > 1) {
            $url['page'] = $page;
        }
        foreach (['category_id', 'name', 'sort_order', 'date_added', 'date_modified'] as $column) {
            if ($sort == $column) {
                $url['order'] = $order == 'asc' ? 'desc' : 'asc';
            } else {
                $url['order'] = 'desc';
            }
            $url['sort'] = $column;
            $data["sort_{$column}"] = $this->url->link('faq/category', $url);
        }

        // Pagination
        $url = $this->url->getQueriesOnly(['sort', 'order']);
        $url['page'] = '{page}';

        $pagination = new Pagination();
        $pagination->total = $category_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('faq/category', $url);
        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf(t('text_pagination'), ($category_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($category_total - $limit)) ? $category_total : ((($page - 1) * $limit) + $limit), $category_total, ceil($category_total / $limit));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('faq/category_list', $data));
    }

    public function add()
    {
        $this->document->setTitle(t('text_faq_category'));
        if (($this->request->server['REQUEST_METHOD'] != 'POST') || !$this->validateForm()) {
            $this->getForm();
            return;
        }

        $category = Category::create([
            'status' => (int)array_get($this->request->post, 'status', 0),
            'sort_order' => (int)array_get($this->request->post, 'sort_order', 0),
            'parent_id' => (int)array_get($this->request->post, 'sort_order', 0)
        ]);

        foreach ($this->request->post['description'] as $language_id => $description) {
            $description['language_id'] = $language_id;
            $category->descriptions()->create($description);
        }

        foreach ($this->request->post['category_store'] as $store_id) {
            $store['store_id'] = $store_id;
            $category->stores()->create($store);
        }

        $this->session->data['success'] = t('text_faq_category_success');
        $this->response->redirect($this->url->link('faq/category', $this->url->getQueriesOnly(['sort', 'page', 'order'])));
    }

    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'faq/category')) {
            $this->error['warning'] = t('error_permission');
        }

        foreach ($this->request->post['description'] as $language_id => $value) {
            if ((utf8_strlen($value['name']) < 2) || (utf8_strlen($value['name']) > 255)) {
                $this->error['name'][$language_id] = t('error_name');
            }
        }

        if ($seo_urls = array_get($this->request->post, 'seo_url')) {
            foreach ($seo_urls as $language_id => $keyword) {
                if (empty($keyword)) {
                    continue;
                }
                if (!SeoUrl::available($keyword, 'faq_category', (int)array_get($this->request->get, 'category_id'))) {
                    $this->error['seo_url'][$language_id] = $this->language->get('error_keyword');
                }
            }
        }

        if (isset($this->request->get['category_id']) && $this->request->post['parent_id']) {
            $results = Path::find($this->request->post['parent_id']);

            foreach ($results as $result) {
                if ($result['path_id'] == $this->request->get['category_id']) {
                    $this->error['parent'] = $this->language->get('error_parent');
                    break;
                }
            }
        }

        return !$this->error;
    }

    public function getForm()
    {
        $category_id = array_get($this->request->get, 'category_id', 0);
        $data['error'] = $this->error;

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_faq_category'), $this->url->link('faq/category', $url));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page', 'category_id']);
        if ($category_id) {
            $this->document->setTitle(t('text_edit'));
            $data['action'] = $this->url->link('faq/category/edit', $url);
        } else {
            $this->document->setTitle(t('text_add'));
            $data['action'] = $this->url->link('faq/category/add', $url);
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);
        $data['cancel'] = $this->url->link('faq/category', $url);

        $data['category_store'] = array(0);

        if ($category_id && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $data['category'] = Category::find($category_id);
            $data['category_store'] = Store::find($category_id)->toArray();
        }

        $this->load->model('setting/store');
        $data['stores'] = array();
        $data['stores'][] = array(
            'store_id' => 0,
            'name' => $this->language->get('text_default')
        );

        $stores = $this->model_setting_store->getStores();
        foreach ($stores as $store) {
            $data['stores'][] = array(
                'store_id' => $store['store_id'],
                'name' => $store['name']
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $this->document->addScript('view/javascript/summernote/summernote.js', 'footer');
        $this->document->addScript('view/javascript/summernote/opencart.js', 'footer');
        $this->document->addStyle('view/javascript/summernote/summernote.css');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('faq/category_form', $data));
    }

    public function edit()
    {
        if (($this->request->server['REQUEST_METHOD'] != 'POST') || !$this->validateForm()) {
            $this->getForm();
            return;
        }
        $data = $this->request->post;

        $category_id = (int)array_get($this->request->get, 'category_id', 0);
        $category = Category::updateOrCreate(
            [
                'category_id' => $category_id
            ],
            [
                'status' => (int)array_get($data, 'status', 0),
                'sort_order' => (int)array_get($data, 'sort_order', 0)
            ]
        );

        $category->descriptions()->delete();
        foreach ($this->request->post['description'] as $language_id => $description) {
            $description['language_id'] = $language_id;
            $category->descriptions()->create($description);
        }

        // Redirect to list page
        $this->session->data['success'] = t('text_faq_category_success');
        $this->response->redirect($this->url->link('faq/category', $this->url->getQueriesOnly(['sort', 'page', 'order'])));
    }

    public function delete()
    {
        $url = $this->url->getQueriesOnly(['sort', 'page', 'order']);
        if (!$this->validateDelete()) {
            $this->session->data['error'] = $this->error['warning'];
            $this->response->redirect($this->url->link('faq/category' . $url));
        }

        if ($category_id = (int)array_get($this->request->get, 'category_id')) {
            $category = Category::find($category_id);
            if ($category) {
                $category->stores()->delete();
                $category->descriptions()->delete();
                $category->delete();
            }
        }

        $this->session->data['success'] = t('text_faq_category_success');
        $this->response->redirect($this->url->link('faq/category', $url));
    }

    protected function validateDelete()
    {
        if (!$this->user->hasPermission('modify', 'faq/category')) {
            $this->error['warning'] = t('error_permission');
        }

        return !$this->error;
    }
}