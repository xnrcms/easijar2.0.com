<?php

use Models\Blog\Category;

class ControllerExtensionModuleBlogCategory extends Controller
{
	public function index()
	{
		if (!config('blog_status')) {
			return;
		}

		$this->load->language('extension/module/blog_category');

		$data['categories'] = Category::all();
		if (!$data['categories']) {
			return;
		}

		return $this->load->view('extension/module/blog_category', $data);
	}
}
