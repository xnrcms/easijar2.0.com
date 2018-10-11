<?php
/**
 * Blog category
 *
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Sam Chen <sam.chen@opencart.cn>
 * @created          2017-05-22 14:10:19
 * @modified         2017-05-26 17:17:23
 */

use Models\Blog\Category;

class ControllerBlogCategory extends Controller {
	public function __construct($registry) {
		parent::__construct($registry);

		if (!config('blog_status')) {
			return $this->notFound();
		}
		$this->load->language('blog/blog');
	}

	private function notFound() {
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => t('text_home'),
			'href' => $this->url->link('common/home')
		);

		$this->document->setTitle(t('text_error'));
		$data['heading_title'] = t('text_error');
		$data['text_error'] = t('text_error');
		$data['button_continue'] = t('button_continue');
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

	public function index() {
		$category_id = (int)array_get($this->request->get, 'blog_category_id', 0);

		$category = Category::find($category_id);
		if (!$category) {
			return $this->notFound();
		}

		$data['category'] = $category;

		$page = array_get($this->request->get, 'page', 1);
		$page = (int)$page > 1 ? $page : 1;

		$breadcrumbs = new Breadcrumb;
		$breadcrumbs->add(t('text_home'), $this->url->link('common/home'));
		$breadcrumbs->add(config("blog_menu_name.{current_language_id()}", t('text_blog')), $this->url->link('blog/home'));
		$breadcrumbs->add($category->localizedDescription()->name, $category->href('show'));
		$data['breadcrumbs'] = $breadcrumbs->all();

		$this->document->setTitle($category->localizedDescription()->meta_title ?: $category->localizedDescription()->name);
		$this->document->setDescription($category->localizedDescription()->meta_description);
		$this->document->setKeywords($category->localizedDescription()->meta_keyword);

		// Posts
		$limit = config('blog_post_limit', 15);
		$post_total = $category->posts()->count();
		$posts = $category->posts()->orderBy('sort_order', 'asc')->orderBy('post_id', 'desc')->skip(($page - 1) * $limit)->take($limit)->get();

		if ($posts->count()) {
			$data['posts'] = $posts;

			$pagination = new Pagination();
			$pagination->total = $post_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('blog/category', 'blog_category_id=' . $this->request->get['blog_category_id'] . '&page={page}');
			$data['pagination'] = $pagination->render();
		} else {
			$data['text_empty'] = t('text_empty');
			$data['button_continue'] = t('button_continue');
			$data['continue'] = $this->url->link('common/home');
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('blog/category', $data));
	}
}
