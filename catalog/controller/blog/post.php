<?php
/**
 * Blog post
 *
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Sam Chen <sam.chen@opencart.cn>
 * @created          2017-05-22 14:10:24
 * @modified         2017-05-26 17:17:27
 */

use Models\Blog\Post;

class ControllerBlogPost extends Controller {
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

		if (isset($this->request->get['route'])) {
			$this->document->addLink(HTTP_SERVER, 'canonical');
		}

		$breadcrumbs = new Breadcrumb;
		$breadcrumbs->add(t('text_home'), $this->url->link('common/home'));
		$breadcrumbs->add(config("blog_menu_name.{current_language_id()}") ?: t('text_blog'), $this->url->link('blog/home'));
		$breadcrumbs->add($description->name, $this->url->link('blog/post', 'blog_post_id=' . $post_id));
		$data['breadcrumbs'] = $breadcrumbs->all();

		$this->document->setTitle($description->meta_title ?: $description->name);
		$this->document->setDescription($description->meta_description);
		$this->document->setKeywords($description->meta_keyword);

		$data['author'] = $description->author ?: config('blog_default_author.' . current_language_id(), config('config_name'));
		$data['post'] = $post;

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('/blog/post', $data));
	}

	private function notFound() {
		$breadcrumbs = new Breadcrumb;
		$breadcrumbs->add(t('text_home'), $this->url->link('common/home'));
		$breadcrumbs->add(config("blog_menu_name.{current_language_id()}") ?: t('text_blog'), $this->url->link('blog/home'));
		$data['breadcrumbs'] = $breadcrumbs->all();

		$this->document->setTitle(t('text_error'));
		$data['heading_title'] = t('text_error');
		$data['continue'] = $this->url->link('common/home');

		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('error/not_found', $data));
	}
}
