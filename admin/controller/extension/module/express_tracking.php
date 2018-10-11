<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-15 10:00:00
 * @modified         2016-11-15 10:00:00
 */

class ControllerExtensionModuleExpressTracking extends Controller
{
    private $error = array();

    /*
    * 后台模块首页
    */
    public function index()
    {

        //加载语言文件
        $this->load->language('extension/module/express_tracking');

        //设置titile
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            //编辑
            $this->model_setting_setting->editSetting('module_express_tracking', $this->request->post);

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module'));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['key'])) {
            $data['error_key'] = $this->error['key'];
        } else {
            $data['error_key'] = '';
        }

        if (isset($this->error['id'])) {
            $data['error_id'] = $this->error['id'];
        } else {
            $data['error_id'] = '';
        }

        if (isset($this->error['platform'])) {
            $data['error_platform'] = $this->error['platform'];
        } else {
            $data['error_platform'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token='.$this->session->data['user_token'])
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/express_tracking', 'user_token='.$this->session->data['user_token'])
        );

        $data['action'] = $this->url->link('extension/module/express_tracking', 'user_token='.$this->session->data['user_token']);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module');

        //获取KEY
        if (isset($this->request->post['module_express_tracking_key'])) {
            $data['module_express_tracking_key'] = $this->request->post['module_express_tracking_key'];
        } else {
            $data['module_express_tracking_key'] = $this->config->get('module_express_tracking_key');
        }

        if (isset($this->request->post['module_express_tracking_id'])) {
            $data['module_express_tracking_id'] = $this->request->post['module_express_tracking_id'];
        } else {
            $data['module_express_tracking_id'] = $this->config->get('module_express_tracking_id');
        }

        if (isset($this->request->post['module_express_tracking_status'])) {
            $data['module_express_tracking_status'] = $this->request->post['module_express_tracking_status'];
        } else {
            $data['module_express_tracking_status'] = $this->config->get('module_express_tracking_status');
        }

        if (isset($this->request->post['module_express_tracking_platform'])) {
            $data['module_express_tracking_platform'] = $this->request->post['module_express_tracking_platform'];
        } else {
            $data['module_express_tracking_platform'] = $this->config->get('module_express_tracking_platform');
        }

        $data['modules'] = array();

        //所有快递公司的信息
        if (isset($this->request->post['module_express_tracking_data'])) {
            $data['modules'] = $this->request->post['module_express_tracking_data'];
        } elseif ($this->config->get('module_express_tracking_data')) {
            $data['modules'] = $this->config->get('module_express_tracking_data');
        }

        $this->load->model('design/layout');

        $data['layouts'] = $this->model_design_layout->getLayouts();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/express_tracking', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/express_tracking')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['module_express_tracking_key']) {
            $this->error['key'] = $this->language->get('error_key');
        }

        if (!$this->request->post['module_express_tracking_platform']) {
            $this->error['platform'] = $this->language->get('error_platform');
        }

        if ($this->request->post['module_express_tracking_platform'] == 'kuaidi' && !$this->request->post['module_express_tracking_id']) {  //对于快递鸟id为必填，kuaidi表示快递鸟kuaidi100表示快递100
            $this->error['id'] = $this->language->get('error_id');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    public function add()
    {
        $this->load->language('sale/order');
        $this->load->language('extension/module/express_tracking');

        $json = array();

        $this->load->model('sale/order');
        $this->load->model('extension/module/express_tracking');

        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['tracking_code'] || !$this->request->post['tracking_number']) {
            $json['error'] = $this->language->get('error_express_code_number_required');
        }
        if (!isset($json['error'])) {
            $this->model_extension_module_express_tracking->addOrderShippingtrack($this->request->get['order_id'], $this->request->post);
            $json['success'] = $this->language->get('text_add_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /*
    * 后台订单详情页快递历史列表及添加快递单号
    */

    public function delete()
    {
        $this->load->language('sale/order');
        $this->load->language('extension/module/express_tracking');

        $json = array();

        $this->load->model('sale/order');
        $this->load->model('extension/module/express_tracking');

        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->model_extension_module_express_tracking->delOrderShippingtrack($this->request->get['id']);
            $json['success'] = $this->language->get('text_del_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getList()
    {
        $this->load->language('sale/order');
        $this->load->language('extension/module/express_tracking');

        $data['error'] = '';
        $data['success'] = '';

        $this->load->model('sale/order');
        $this->load->model('extension/module/express_tracking');

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['histories'] = array();

        $results = $this->model_extension_module_express_tracking->getOrderShippingtracks($this->request->get['order_id'], ($page - 1) * 10, 10);

        $tracking_datas = $this->config->get('module_express_tracking_data');

        foreach ($results as $result) {
            //快递code
            $express_code = $result['tracking_code'];
            //快递单号
            $tracking_number = $result['tracking_number'];

            $typeCom = $express_code; //快递公司
            $typeNu = $tracking_number;  //快递单号
            $track = $this->url->link('extension/module/express_tracking/getTrace', 'com='.$typeCom.'&nu='.$typeNu.'&user_token='.$this->session->data['user_token']);

            $name = '';
            foreach ($tracking_datas as $item) {
              if ($item['code'] == $result['tracking_code']) {
                $name = $item['name'];
              }

            }
            $data['histories'][] = array(
                'tracking_code' => $name,
                'tracking_number' => $result['tracking_number'],
                'kd_comment' => $result['comment'],
                'id' => $result['id'],
                'kd_track' => $track,
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
            );
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['order_id'] = $this->request->get['order_id'];

        $history_total = $this->model_extension_module_express_tracking->getTotalOrderShippingtracks($this->request->get['order_id']);

        $pagination = new Pagination();
        $pagination->total = $history_total;
        $pagination->page = $page;
        $pagination->limit = 10;
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/express_tracking/express', 'user_token='.$this->session->data['user_token'].'&order_id='.$this->request->get['order_id'].'&page={page}');

        $data['pagination'] = $pagination->render();

        $this->response->setOutput($this->load->view('extension/module/order_express', $data));
    }

    public function getTrace()
    {
        $this->load->language('extension/module/express_tracking');

        if (isset($this->request->get['com'])) {
            $typeCom = $this->request->get['com'];
        } else {
            $typeCom = 0;
        }
        if (isset($this->request->get['nu'])) {
            $typeNu = $this->request->get['nu'];
        } else {
            $typeNu = 0;
        }

        $key = $this->config->get('module_express_tracking_key');
        $id = $this->config->get('module_express_tracking_id');

        $class = $this->config->get('module_express_tracking_platform');
        $express = new $class($id, $key);
        $tracking = $express->getOrderTraces($typeCom, $typeNu);

        if (isset($tracking['message'])) { //查询出错
            $track = '<div id=errordiv style=width:500px;border:#fe8d1d 1px solid;padding:20px;background:#FFFAE2;>
									<p style=line-height:28px;margin:0px;padding:0px;color:#F21818;>' .$tracking['message'].'</p>
								</div>';
        } else {
            $track = "<table width='520px' border='0' cellspacing='0' cellpadding='0' id='showtablecontext' style='border-collapse:collapse;border-spacing:0;'>";

            $track .= "<tr>
					<td width='163' style='background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;'>" .$this->language->get('text_time')."</td>
					<td width='354' style='background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;'>" .$this->language->get('text_station').'</td>
				</tr>';
            foreach ($tracking['traces'] as $trace) {
                $track .= "<tr>
						<td width='163' style='border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;'>" .$trace['time']."</td>
						<td width='354' style='border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;'>" .$trace['station'].'</td>
					</tr>';
            }
            $track .= '</table>';
        }
        $this->response->setOutput($track);
    }

    public function install()
    {
        //在安装快递单插件的时候默认初始一些数据进去
        //自动添加一些基本数据
        $store_id = 0;
        $sql_tt = '[{"code":"STO","name":"申通","status":"1","sort_order":"1"},{"code":"ems","name":"EMS","status":"1","sort_order":"2"},{"code":"SF","name":"顺丰速递","status":"1","sort_order":"3"},{"code":"YTO","name":"圆通速递","status":"1","sort_order":"4"},{"code":"ZTO","name":"中通速递","status":"1","sort_order":"5"},{"code":"YD","name":"韵达快运","status":"1","sort_order":"6"},{"code":"HHTT","name":"天天快递","status":"1","sort_order":"7"},{"code":"HTKY","name":"百世汇通","status":"1","sort_order":"8"},{"code":"QFKD","name":"全峰快递","status":"1","sort_order":"9"},{"code":"DBL","name":"德邦物流","status":"1","sort_order":"10"},{"code":"ZJS","name":"宅急送","status":"1","sort_order":"11"}]';
        $this->db->query('INSERT INTO '.DB_PREFIX."setting SET store_id = '".(int) $store_id."', `code` = 'module_express_tracking', `key` = 'module_express_tracking_data', `value` = '".$sql_tt."' , `serialized` = '1' ");

        //增加快递code/name/number字段
        //$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "order_shippingtrack`;");
        $this->db->query('CREATE TABLE `'.DB_PREFIX.'order_shippingtrack` ( '.
                                              '`id` int(11) NOT NULL AUTO_INCREMENT, '.
                                              '`order_id` int(11) NOT NULL, '.
                                              '`tracking_code` varchar(64) NOT NULL, '.
                                              '`tracking_number` varchar(64) NOT NULL, '.
                                              '`tracking_name` varchar(64) NOT NULL, '.
                                              '`comment` varchar(128), '.
                                              '`date_added` datetime NOT NULL, '.
                                              'PRIMARY KEY (`id`) '.
                                            ') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;');
    }

    public function uninstall()
    {
        $store_id = 0;

        $this->db->query('DELETE FROM '.DB_PREFIX."setting WHERE store_id = '".(int) $store_id."' AND `code` = 'module_express_tracking'");
        $this->db->query('DROP TABLE IF EXISTS `'.DB_PREFIX.'order_shippingtrack`;');
    }
}
