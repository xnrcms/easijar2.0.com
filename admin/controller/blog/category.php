<?php
/**
 * Blog category
 *
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Sam Chen <sam.chen@opencart.cn>
 * @created          2017-05-22 10:02:31
 * @modified         2017-08-09 15:31:29
 */

use Models\Blog\Category;
use Models\SeoUrl;

class ControllerBlogCategory extends Controller
{
    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->language->load('blog/blog');
        $this->validateSetup();
    }

    protected function validateSetup()
    {
        $this->load->model('setting/setting');
        $setting = $this->model_setting_setting->getSetting('blog');
        if (!$setting) {
            $this->session->data['success'] = t('error_not_install');
            $this->response->redirect($this->url->link('blog/setting'));
        }
    }

    public function index()
    {
        $this->document->setTitle(t('text_blog_category'));
        $this->getList();
    }

    protected function getList()
    {
        $sort = array_get($this->request->get, 'sort', 'category_id');
        $order = array_get($this->request->get, 'order', 'desc');
        $page = (int)array_get($this->request->get, 'page', 1);
        $limit = config('config_limit_admin');

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_blog_category'), $this->url->link('blog/category', $this->url->getQueriesOnly()));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);

        $data['add'] = $this->url->link('blog/category/add', $url);
        $data['delete'] = $this->url->link('blog/category/delete', $url);

        $category_total = Category::count();
        $filter_data = array(
            'sort'  => $sort,
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
            $data["sort_{$column}"] = $this->url->link('blog/category', $url);
        }

        // Pagination
        $url = $this->url->getQueriesOnly(['sort', 'order']);
        $url['page'] = '{page}';

        $pagination = new Pagination();
        $pagination->total = $category_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('blog/category', $url);
        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf(t('text_pagination'), ($category_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($category_total - $limit)) ? $category_total : ((($page - 1) * $limit) + $limit), $category_total, ceil($category_total / $limit));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('blog/category_list', $data));
    }

    public function add()
    {
        $this->document->setTitle(t('text_blog_category'));

        if (($this->request->server['REQUEST_METHOD'] != 'POST') || !$this->validateForm()) {
            $this->getForm();
            return;
        }

        $category = Category::create([
            'status' => (int)array_get($this->request->post, 'status', 0),
            'sort_order' => (int)array_get($this->request->post, 'sort_order', 0),
            'top' => (int)array_get($this->request->post, 'top', 0)
        ]);

        foreach ($this->request->post['description'] as $language_id => $description) {
            $description['language_id'] = $language_id;
            $category->descriptions()->create($description);
        }

        if ($keywords = array_get($this->request->post, 'seo_url')) {
            foreach ($keywords as $language_id => $keyword) {
                SeoUrl::createKeyword('blog_category', $category->category_id, $keyword, $language_id);
            }
        }

        // Redirect to list page
        $this->session->data['success'] = t('text_blog_category_success');
        $this->response->redirect($this->url->link('blog/category', $this->url->getQueriesOnly(['sort', 'page', 'order'])));
    }

    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'blog/category')) {
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
                if (!SeoUrl::available($keyword, 'blog_category', (int)array_get($this->request->get, 'category_id'))) {
                    $this->error['seo_url'][$language_id] = $this->language->get('error_keyword');
                }
            }
        }

        return !$this->error;
    }

    protected function getForm()
    {
        $category_id = array_get($this->request->get, 'category_id', 0);
        $data['error'] = $this->error;

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_blog_category'), $this->url->link('blog/category', $url));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page', 'category_id']);
        if ($category_id) {
            $this->document->setTitle(t('text_edit'));
            $data['action'] = $this->url->link('blog/category/edit', $url);
        } else {
            $this->document->setTitle(t('text_add'));
            $data['action'] = $this->url->link('blog/category/add', $url);
        }

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);
        $data['cancel'] = $this->url->link('blog/category', $url);

        if ($category_id && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $data['category'] = Category::find($category_id);
            $data['seo_url'] = SeoUrl::allKeywords('blog_category', $category_id);
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $this->document->addScript('view/javascript/summernote/summernote.js', 'footer');
        $this->document->addScript('view/javascript/summernote/opencart.js', 'footer');
        $this->document->addStyle('view/javascript/summernote/summernote.css');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('blog/category_form', $data));
    }

    public function edit()
    {
        if (($this->request->server['REQUEST_METHOD'] != 'POST') || !$this->validateForm()) {
            $this->getForm();
            return;
        }

        $category_id = (int)array_get($this->request->get, 'category_id', 0);
        $category = Category::updateOrCreate(
            ['category_id' => $category_id],
            [
                'status' => (int)array_get($this->request->post, 'status', 0),
                'sort_order' => (int)array_get($this->request->post, 'sort_order', 0),
                'top' => (int)array_get($this->request->post, 'top', 0)
            ]
        );

        $category->descriptions()->delete();
        foreach ($this->request->post['description'] as $language_id => $description) {
            $description['language_id'] = $language_id;
            $category->descriptions()->create($description);
        }

        SeoUrl::deleteAllKeywords('blog_category', $category->category_id);
        if (array_get($this->request->post, 'seo_url')) {
            foreach ($this->request->post['seo_url'] as $language_id => $keyword) {
                if (empty(trim($keyword))) {
                    continue;
                }
                SeoUrl::createKeyword('blog_category', $category->category_id, $keyword, $language_id);
            }
        }

        // Redirect to list page
        $this->session->data['success'] = t('text_blog_category_success');
        $this->response->redirect($this->url->link('blog/category', $this->url->getQueriesOnly(['sort', 'page', 'order'])));
    }

    public function delete()
    {
        $url = $this->url->getQueriesOnly(['sort', 'page', 'order']);
        if (!$this->validateDelete()) {
            $this->session->data['error'] = $this->error['warning'];
            $this->response->redirect($this->url->link('blog/category' . $url));
        }

        if ($category_id = (int)array_get($this->request->get, 'category_id')) {
            $category = Category::find($category_id);
            if ($category) {
                $category->posts()->detach();
                $category->descriptions()->delete();
                $category->delete();
                SeoUrl::deleteAllKeywords("blog_category", $category->category_id);
            }
        }

        $this->session->data['success'] = t('text_blog_category_success');
        $this->response->redirect($this->url->link('blog/category', $url));
    }

    protected function validateDelete()
    {
        if (!$this->user->hasPermission('modify', 'blog/category')) {
            $this->error['warning'] = t('error_permission');
        }

        return !$this->error;
    }
}
