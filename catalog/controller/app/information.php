<?php
class ControllerAppInformation extends Controller {
  // 打开单个文章页面
  public function index() {
    $data['error'] = '';

    if (isset($this->request->get['information_id']) && (int)$this->request->get['information_id']) {
      $information_id = $this->request->get['information_id'];

      $this->load->model('catalog/information');
      $information_info = $this->model_catalog_information->getInformation($information_id);

      if ($information_info) {
        if ($this->request->server['HTTPS']) {
          $server = $this->config->get('config_ssl');
        } else {
          $server = $this->config->get('config_url');
        }
        $data['base'] = $server;

        $data['title'] = $information_info['meta_title'];
        $data['html'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');
      } else {
        $data['title'] = '404';
        $data['error'] = '没有找到文章： information_id = ' . $information_id;
        $data['html'] = '';
      }
    } else {
      $data['title'] = '404';
      $data['error'] = '没有值入 information_id 值';
      $data['html'] = '';
    }

    $this->response->setOutput($this->load->view('app/template/information/information_info.tpl', $data));
  }
}