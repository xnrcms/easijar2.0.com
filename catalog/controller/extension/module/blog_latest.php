<?php

use Models\Blog\Post;

class ControllerExtensionModuleBlogLatest extends Controller
{
	public function index($setting)
	{
		if (!config('blog_status')) {
			return;
		}

		$data['posts'] = Post::orderBy('sort_order', 'asc')->orderBy('post_id', 'desc')->take(config('module_blog_latest_limit'))->get();
		if (!$data['posts']) {
			return;
		}

		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
		$this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.min.js');

		$data['position'] = $setting['position'];
		$data['heading_title'] = config('module_blog_latest_title.' . current_language_id());

		$position = array_get($setting, 'position', 'column_right');
		$style = in_array($position, ['content_top', 'content_bottom']) ? 'grid' : 'list';

		return $this->load->view('extension/module/blog_latest_' . $style, $data);
	}
}
