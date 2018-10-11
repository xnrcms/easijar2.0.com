<?php
/**
 * multi_filter.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-04-09 14:46
 * @modified   2018-04-09 14:46
 */

require_once(DIR_APPLICATION . 'controller/extension/module_base_controller.php');

class ControllerExtensionModuleMultiFilter extends GDModuleBaseController
{
    protected $module_code = 'multi_filter';

    public function index()
    {
        $this->load->language('extension/module/filter');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (array_get($this->request->post, 'action') == 'refresh-cache') {
                $this->cache->delete('product');
                $this->session->data['success'] = 'Clear all filter product cache!';
                return $this->response->redirect($this->url->link('extension/module/multi_filter', 'token=' . $this->session->data['user_token'], true));
            }
        }
        parent::index();
    }

    protected function overwriteDataForView($data)
    {
        if (isset($this->request->post['module_multi_filter_status'])) {
            $data['module_multi_filter_status'] = $this->request->post['module_multi_filter_status'];
        } else {
            $data['module_multi_filter_status'] = $this->config->get('module_multi_filter_status');
        }
        if (isset($this->request->post['module_multi_filter_cache_status'])) {
            $data['module_multi_filter_cache_status'] = $this->request->post['module_multi_filter_cache_status'];
        } else {
            $data['module_multi_filter_cache_status'] = $this->config->get('module_multi_filter_cache_status');
        }
        if (isset($this->request->post['module_multi_filter_cache_expired'])) {
            $data['module_multi_filter_cache_expired'] = $this->request->post['module_multi_filter_cache_expired'];
        } else {
            $data['module_multi_filter_cache_expired'] = $this->config->get('module_multi_filter_cache_expired');
        }

        return $data;
    }
}