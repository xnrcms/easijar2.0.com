<?php
/**
 * Blog setting
 *
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Sam Chen <sam.chen@opencart.cn>
 * @created          2017-05-22 14:00:55
 * @modified         2017-08-09 15:14:15
 */

class ControllerBlogSetting extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('blog/blog');
        $this->document->setTitle(t('text_blog_setting'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('blog', $this->request->post);
            $this->session->data['success'] = t('text_blog_setting_success');
            $this->response->redirect($this->url->link('blog/setting'));
        }

        $data['error'] = $this->error;
        if (isset($this->session->data['success'])) {
            $data['text_success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['text_success'] = '';
        }

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/dashboard'));
        $breadcrumbs->add(t('text_blog_setting'), $this->url->link('blog/setting'));
        $data['breadcrumbs'] = $breadcrumbs->all();

        $data['action'] = $this->url->link('blog/setting');

        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $data['setting'] = $this->model_setting_setting->getSetting('blog');
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('blog/setting', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'blog/setting')) {
            $this->error['warning'] = t('error_permission');
        }

        foreach ($this->request->post['blog_menu_name'] as $language_id => $value) {
            if ((utf8_strlen($value) < 1) || (utf8_strlen($value) > 32)) {
                $this->error['menu_name'][$language_id] = t('error_menu_name');
            }
        }

        foreach ($this->request->post['blog_default_author'] as $language_id => $value) {
            if ((utf8_strlen($value) < 1) || (utf8_strlen($value) > 128)) {
                $this->error['default_author'][$language_id] = t('error_default_author');
            }
        }

        if (!$this->request->post['blog_post_limit']) {
            $this->error['post_limit'] = t('error_post_limit');
        }

        if (!$this->request->post['blog_category_image_width'] || !$this->request->post['blog_category_image_height']) {
            $this->error['image_category'] = t('error_image_category');
        }

        if (!$this->request->post['blog_post_image_width'] || !$this->request->post['blog_post_image_height']) {
            $this->error['image_post'] = t('error_image_post');
        }

        if (!$this->request->post['blog_post_description_length']) {
            $this->error['post_description_length'] = t('error_post_description_length');
        }

        foreach ($this->request->post['blog_post_read_more'] as $language_id => $value) {
            if ((utf8_strlen($value) < 1) || (utf8_strlen($value) > 128)) {
                $this->error['read_more'][$language_id] = t('error_read_more');
            }
        }

        return !$this->error;
    }
}
