<?php
/**
 * Blog post
 *
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Sam Chen <sam.chen@opencart.cn>
 * @created          2017-05-22 12:37:05
 * @modified         2017-08-09 15:33:47
 */

use Models\Blog\Category;
use Models\Blog\Post;
use Models\SeoUrl;

class ControllerBlogPost extends Controller
{
    private $error = array();

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->language->load('blog/blog');
        $this->validateSetup();
        $this->hasCategories();
    }

    protected function validateSetup()
    {
        $this->language->load('blog/blog');
        $this->load->model('setting/setting');
        $setting = $this->model_setting_setting->getSetting('blog');
        if (!$setting) {
            $this->session->data['success'] = t('error_not_install');
            $this->response->redirect($this->url->link('blog/setting'));
        }
    }

    protected function hasCategories()
    {
        if (!Category::count()) {
            $this->session->data['error'] = t('error_no_catgory');
            $this->response->redirect($this->url->link('blog/category'));
        }
    }

    public function index()
    {
        $this->document->setTitle(t('text_blog_post'));
        $this->getList();
    }

    protected function getList()
    {
        $sort = array_get($this->request->get, 'sort', 'date_modified');
        $order = array_get($this->request->get, 'order', 'DESC');
        $page = array_get($this->request->get, 'page', 1);
        $limit = config('config_limit_admin');

        $breadcrumb = new Breadcrumb();
        $breadcrumb->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumb->add(t('text_blog_post'), $this->url->link('blog/post', $this->url->getQueriesOnly()));
        $data['breadcrumbs'] = $breadcrumb->all();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);

        $data['add'] = $this->url->link('blog/post/add', $url);
        $data['delete'] = $this->url->link('blog/post/delete', $url);

        $post_total = Post::count();
        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'offset' => ($page - 1) * $limit,
            'limit' => $limit,
        );
        $data['posts'] = Post::getCollection($filter_data);

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

        // Column Sort & Order links
        $url = [];
        if ($page > 1) {
            $url['page'] = $page;
        }
        foreach (['post_id', 'name', 'sort_order', 'date_added', 'date_modified'] as $column) {
            if ($sort == $column) {
                $url['order'] = $order == 'asc' ? 'desc' : 'asc';
            } else {
                $url['order'] = 'desc';
            }
            $url['sort'] = $column;
            $data["sort_{$column}"] = $this->url->link('blog/post', $url);
        }

        // Pagination
        $url = $this->url->getQueriesOnly(['sort', 'order']);
        $url['page'] = '{page}';

        $pagination = new Pagination();
        $pagination->total = $post_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('blog/post', $url);
        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf(t('text_pagination'), ($post_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($post_total - $limit)) ? $post_total : ((($page - 1) * $limit) + $limit), $post_total, ceil($post_total / $limit));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('blog/post_list', $data));
    }

    public function add()
    {
        if (($this->request->server['REQUEST_METHOD'] != 'POST')|| !$this->validateForm()) {
            $this->getForm();
            return;
        }

        $data = $this->request->post;
        $post = Post::create([
            'status' => (int)array_get($data, 'status', 0),
            'sort_order' => (int)array_get($data, 'sort_order', 0)
        ]);

        foreach ($data['description'] as $language_id => $description) {
            $description['language_id'] = $language_id;
            $post->descriptions()->create($description);
        }

        if ($keywords = array_get($data, 'seo_url')) {
            foreach ($keywords as $language_id => $keyword) {
                SeoUrl::createKeyword('blog_post', $post->post_id, $keyword, $language_id);
            }
        }

        //Attach to categories
        $post->categories()->sync(array_get($data, 'post_to_categories', []));

        // Redirect to list page
        $this->session->data['success'] = t('text_blog_post_success');
        $this->response->redirect($this->url->link('blog/post', $this->url->getQueriesOnly(['sort', 'page', 'order'])));
    }

    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'blog/post')) {
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
                if (!SeoUrl::available($keyword, 'blog_post', (int)array_get($this->request->get, 'post_id'))) {
                    $this->error['seo_url'][$language_id] = $this->language->get('error_keyword');
                }
            }
        }

        return !$this->error;
    }

    protected function getForm()
    {
        $post_id = array_get($this->request->get, 'post_id', 0);
        $data['error'] = $this->error;

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_blog_post'), $this->url->link('blog/post', $url));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page', 'post_id']);
        if ($post_id) {
            $this->document->setTitle(t('text_edit'));
            $data['action'] = $this->url->link('blog/post/edit', $url);
        } else {
            $this->document->setTitle(t('text_add'));
            $data['action'] = $this->url->link('blog/post/add', $url);
        }

        $url = $this->url->getQueriesOnly(['sort', 'order', 'page']);
        $data['cancel'] = $this->url->link('blog/category', $url);

        if ($post_id && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $data['post'] = $post = Post::find($post_id);
            $data['post_to_categories'] = $post->categories->pluck('category_id')->toArray();
            $data['seo_url'] = SeoUrl::allKeywords('blog_post', $post_id);
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['categories'] = Category::all();

        if (is_free_or_pro()) {
            $this->document->addScript('view/javascript/tinymce/opencart.js', 'footer');
            $this->document->addScript('view/javascript/ckfinder/ckfinder.js', 'footer');
        } else {
            $this->document->addScript('view/javascript/summernote/summernote.js', 'footer');
            $this->document->addScript('view/javascript/summernote/opencart.js', 'footer');
            $this->document->addStyle('view/javascript/summernote/summernote.css');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('blog/post_form', $data));
    }

    public function edit()
    {
        if (($this->request->server['REQUEST_METHOD'] != 'POST') || !$this->validateForm()) {
            $this->getForm();
            return;
        }

        $post_id = (int)array_get($this->request->get, 'post_id');
        $data = $this->request->post;
        $post = Post::updateOrCreate(
            ['post_id' => $post_id],
            [
                'status' => (int)array_get($data, 'status', 0),
                'sort_order' => (int)array_get($data, 'sort_order', 0)
            ]
        );

        $post->descriptions()->delete();
        foreach ($data['description'] as $language_id => $description) {
            $description['language_id'] = $language_id;
            $post->descriptions()->create($description);
        }

        SeoUrl::deleteAllKeywords('blog_post', $post->post_id);
        if (array_get($data, 'seo_url')) {
            foreach ($data['seo_url'] as $language_id => $keyword) {
                if (empty(trim($keyword))) {
                    continue;
                }
                SeoUrl::createKeyword('blog_post', $post_id, $keyword, $language_id);
            }
        }

        //Attach to categories
        $post->categories()->sync(array_get($data, 'post_to_categories', []));

        // Redirect to list page
        $this->session->data['success'] = t('text_blog_post_success');
        $this->response->redirect($this->url->link('blog/post', $this->url->getQueriesOnly(['sort', 'page', 'order'])));
    }

    public function delete()
    {
        $url = $this->url->getQueriesOnly(['sort', 'page', 'order']);
        if (!$this->validateDelete()) {
            $this->session->data['error'] = $this->error['warning'];
            $this->response->redirect($this->url->link('blog/post' . $url));
        }

        $post_id = (int)array_get($this->request->get, 'post_id');
        if ($post_id > 0) {
            $post = Post::find($post_id);
            $post->categories()->detach();
            $post->descriptions()->delete();
            $post->delete();
            SeoUrl::deleteAllKeywords("blog_post", $post->post_id);
        }

        // Redirect to list page
        $this->session->data['success'] = t('text_blog_post_success');
        $this->response->redirect($this->url->link('blog/post', $url));
    }

    protected function validateDelete()
    {
        if (!$this->user->hasPermission('modify', 'blog/post')) {
            $this->error['warning'] = t('error_permission');
        }

        return !$this->error;
    }
}

