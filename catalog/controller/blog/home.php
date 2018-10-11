<?php
/**
 * Blog home
 *
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Sam Chen <sam.chen@opencart.cn>
 * @created          2017-05-26 10:10:19
 * @modified         2017-05-26 17:17:20
 */

use Models\Blog\Post;

class ControllerBlogHome extends Controller {
	public function index() {
		if (!config('blog_status')) {
			$this->response->redirect($this->url->link('common/home'));
		}

		$this->load->language('blog/blog');

		$page = (int)array_get($this->request->get, 'page', 1);

		$breadcrumbs = new Breadcrumb;
		$breadcrumbs->add(t('text_home'), $this->url->link('common/home'));
		$breadcrumbs->add(config("blog_menu_name.{current_language_id()}", t('text_blog')), $this->url->link('blog/home'));

		$this->document->setTitle(config("blog_meta_title.{current_language_id()}", t('text_blog')));
		$this->document->setDescription(config("blog_meta_description.{current_language_id()}"));
		$this->document->setKeywords(config("blog_meta_keyword.{current_language_id()}"));

		// Posts
		$limit = config('blog_post_limit', 15);

		$post_total = Post::count();
		$posts = Post::skip(($page - 1) * $limit)->take($limit)->get();

		if (!$posts->count()) {
			$data['continue'] = $this->url->link('common/home');
		} else {
			$data['posts'] = $posts;
			$pagination = new Pagination();
			$pagination->total = $post_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('blog/home', 'page={page}');
			$data['pagination'] = $pagination->render();
		}

		$data['breadcrumbs'] = $breadcrumbs->all();

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('blog/category', $data));
	}
}
