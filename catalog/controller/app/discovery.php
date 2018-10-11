<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-07-20 15:52:07
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-07-20 15:59:54
 */

use Models\Blog\Post;

class ControllerAppDiscovery extends Controller
{
    private $debug = false;

    public function index()
    {
        if (!config('blog_status')) {
            return;
        }

        $data['base'] = config('config_url');
        $data['html'] = $this->blogs();
        $data['styles'] = $this->document->getStyles();
        $data['scripts'] = $this->document->getScripts('header');

        $this->response->setOutput($this->load->view('app/template/home/index', $data));
    }

    private function blogs()
    {
        $data['posts'] = Post::orderBy('sort_order', 'asc')->orderBy('post_id', 'desc')->take(config('module_blog_latest_limit'))->get();
        if (!$data['posts']) {
            return;
        }

        $this->load->language('blog/blog', 'blog');
            $data['text_view_count'] = t('blog')->get('text_view_count');

        return $this->load->view('app/template/home/parts/blog_latest', $data);
    }
}
