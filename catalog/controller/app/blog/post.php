<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Sam Chen <sam.chen@opencart.cn>
 * @created          2018-06-13 12:28:20
 * @modified         2018-06-13 13:20:39
 */

use Models\Blog\Post;

class ControllerAppBlogPost extends Controller {
    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->language('blog/blog');
    }

    public function index() {
        if (!config('blog_status')) {
            return $this->notFound();
        }

        $post_id = array_get($this->request->get, 'blog_post_id', 0);
        $post = Post::find($post_id);

        if (!$post) {
            return $this->notFound();
        }

        $post->incrementViewCountBy(1);
        $description = $post->localizedDescription();

        $this->document->setTitle($description->meta_title ?: $description->name);

        $data['author'] = $description->author ?: config('blog_default_author.' . current_language_id(), config('config_name'));
        $data['post'] = $post;

        $data['base'] = config('config_url');

        $this->response->setOutput($this->load->view('app/template/blog/post', $data));
    }

    private function notFound()
    {
        echo 'not found';
    }
}
