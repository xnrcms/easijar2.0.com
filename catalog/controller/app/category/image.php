<?php

class ControllerAppCategoryImage extends Controller
{
    /**
     * Request URL: http://app.opencartdemo.cn/index.php?route=app/category/image&category_id=20&width=100&height=100
     * @return mixed
     */
    public function index()
    {
        $data['error'] = '';

        if ($this->request->server['HTTPS']) {
            $server = $this->config->get('config_ssl');
        } else {
            $server = $this->config->get('config_url');
        }

        $data['base'] = $server;
        if (!isset($this->request->get['category_id']) || !(int)$this->request->get['category_id']) {
            return $this->response->setOutput('No category id');
        }

        $category_id = (int)$this->request->get['category_id'];
        $this->load->model('catalog/category');
        $category_info = $this->model_catalog_category->getCategory($category_id);
        if (!$category_info) {
            return $this->response->setOutput('Invalid category id');
        }

        $width = $height = 100;
        if (isset($this->request->get['width']) && (int)$this->request->get['width']) {
            $width = (int)$this->request->get['width'];
        }
        if (isset($this->request->get['height']) && (int)$this->request->get['height']) {
            $height = (int)$this->request->get['height'];
        }

        $this->load->model('tool/image');
        if (is_file(DIR_IMAGE . $category_info['image'])) {
            $image = $this->model_tool_image->resize($category_info['image'], $width, $height);
        } else {
            $image = $this->model_tool_image->resize('placeholder.png', $width, $height);
        }
        //var_dump($image);exit;
        $this->response->redirect($image);
    }
}
