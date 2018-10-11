<?php

/**
 * aftership.php
 *
 * @copyright 2018 OpenCart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-07-03 09:15
 * @modified 2018-07-03 09:15
 */

use AfterShip\AfterShipException;

class ControllerExtensionModuleAftership extends Controller
{
    private $error = array();
    private $aftership_key;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->aftership_key = $this->config->get('module_aftership_tracking_key');

        $this->load->language('sale/order');
        $this->load->language('extension/module/aftership');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/order');
        $this->load->model('setting/setting');
        $this->load->model('extension/module/aftership');
    }

    public function index()
    {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_aftership', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

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

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/aftership', 'user_token=' . $this->session->data['user_token'], 'SSL'),
        );

        if (isset($this->request->post['module_aftership_tracking_key'])) {
            $data['module_aftership_tracking_key'] = $this->request->post['module_aftership_tracking_key'];
        } else {
            $data['module_aftership_tracking_key'] = $this->config->get('module_aftership_tracking_key');
        }

        if (isset($this->request->post['module_aftership_status'])) {
            $data['module_aftership_status'] = $this->request->post['module_aftership_status'];
        } else {
            $data['module_aftership_status'] = $this->config->get('module_aftership_status');
        }

        if (isset($this->request->post['module_aftership_data'])) {
            $data['modules'] = $this->request->post['module_aftership_data'];
        } elseif ($this->config->get('module_aftership_data')) {
            $data['modules'] = $this->config->get('module_aftership_data');
        } else {
            $data['modules'] = array();
        }

        $data['text_apply'] = sprintf(t('text_apply'), 'https://accounts.aftership.com/register');
        $data['text_api'] = sprintf(t('text_api'), 'https://secure.aftership.com/#/settings/api');
        $data['text_express'] = sprintf(t('text_express'), 'https://secure.aftership.com/#/settings/couriers');

        $data['user_token'] = $this->session->data['user_token'];
        $data['action'] = $this->url->link('extension/module/aftership', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $this->response->setOutput($this->load->view('extension/module/aftership', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/aftership')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['module_aftership_tracking_key']) {
            $this->error['key'] = $this->language->get('error_key');
        }

        return !$this->error;
    }

    public function add()
    {
        $json = array();

        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['tracking_code'] || !$this->request->post['tracking_number']) {
            $json['error'] = $this->language->get('error_tracking_code_number_required');
        }

        if (!isset($json['error'])) {
            $tracking = new AfterShip\Trackings($this->aftership_key);
            $trackingNumber = $this->request->post['tracking_number'];
            $existTrackingData = array();
            try {
                $existTrackingData = $tracking->get($this->request->post['tracking_code'], $this->request->post['tracking_number']);
            } catch (Exception $e) {

            }

            try {
                $courier = new AfterShip\Couriers($this->aftership_key);
                $courier->detect($trackingNumber);
                $this->model_extension_module_aftership->addOrderShippingTrack($this->request->get['order_id'], $this->request->post);

                if ($existTrackingData && $existTrackingData['meta']['code'] == 200) {
                    throw new Exception('Tracking number exist');
                }
                $tracking_info = array(
                    'slug' => $this->request->post['tracking_code']
                );
                $tracking->create($trackingNumber, $tracking_info);
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        }

        if (!isset($json['error'])) {
            $json['success'] = $this->language->get('text_add_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getTrace()
    {

        if (isset($this->request->get['slug'])) {
            $typeSlug = $this->request->get['slug'];
        } else {
            $typeSlug = 0;
        }

        if (isset($this->request->get['number'])) {
            $typeNu = $this->request->get['number'];
        } else {
            $typeNu = 0;
        }

        $json = array();
        try {
            $trackings = new AfterShip\Trackings($this->aftership_key);
            $response = $trackings->get($typeSlug, $typeNu);
        } catch (AftershipException $e) {
            $json['error'] = $e->getMessage();
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }
        if (isset($json['error'])) {
            $track = "<div id='errordiv' style='width:500px;border:#fe8d1d 1px solid;padding:20px;background:#FFFAE2;'>
                        <p style='line-height:28px;margin:0px;padding:0px;color:#F21818;'>" . $json['error'] . "</p>
                      </div>";
        } else {
            $track = "<table width='520px' border='0' cellspacing='0' cellpadding='0' id='showtablecontext' style='border-collapse:collapse;border-spacing:0;'>";

            $track .= "<tr>
                        <td width='163' style='background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;'>" . $this->language->get('text_time') . "</td>
                        <td width='354' style='background:#64AADB;border:1px solid #75C2EF;color:#FFFFFF;font-size:14px;font-weight:bold;height:28px;line-height:28px;text-indent:15px;'>" . $this->language->get('text_station') . "</td>
                       </tr>";

            foreach ($response['data']['tracking']['checkpoints'] as $trace) {
                $track .= "<tr>
                            <td width='163' style='border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;'>" . $trace['checkpoint_time'] . "</td>
                            <td width='354' style='border:1px solid #DDDDDD;font-size:12px;line-height:22px;padding:3px 5px;'>" . $trace['message'] . "</td>
                    </tr>";
            }
            $track .= "</table>";
        }
        $this->response->setOutput($track);
    }

    public function getList()
    {
        $limit = 10;

        $this->load->language('sale/order');
        $this->load->language('extension/module/tracking');

        $data['error'] = '';
        $data['success'] = '';

        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_view'] = $this->language->get('text_view');
        $data['button_delete'] = $this->language->get('button_delete');

        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_tracking_code'] = $this->language->get('column_tracking_code');
        $data['column_tracking_number'] = $this->language->get('column_tracking_number');
        $data['column_tracking_track'] = $this->language->get('column_tracking_track');
        $data['column_comment'] = $this->language->get('column_comment');
        $data['column_action'] = $this->language->get('column_action');
        $data['column_kuaidi_code'] = $this->language->get('column_kuaidi_code');
        $data['column_kuaidi_number'] = $this->language->get('column_kuaidi_number');
        $data['column_kuaidi_track'] = $this->language->get('column_kuaidi_track');

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['histories'] = array();

        $results = $this->model_extension_module_aftership->getOrderShippingTracks($this->request->get['order_id'], ($page - 1) * $limit, $limit);

        foreach ($results as $result) {
            $tracking_code = $result['tracking_code'];
            $tracking_number = $result['tracking_number'];

            $track = $this->url->link('extension/module/aftership/getTrace', 'slug=' . $tracking_code . '&number=' . $tracking_number . '&user_token=' . $this->session->data['user_token']);

            $data['histories'][] = array(
                'tracking_code' => $result['tracking_code'],
                'tracking_number' => $result['tracking_number'],
                'comment' => $result['comment'],
                'tracking_name' => $result['tracking_name'],
                'id' => $result['id'],
                'track' => $track,
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
            );
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['order_id'] = $this->request->get['order_id'];

        $history_total = $this->model_extension_module_aftership->getTotalOrderShippingTracks($this->request->get['order_id']);

        $pagination = new Pagination();
        $pagination->total = $history_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/tracking/getList', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $this->request->get['order_id'] . '&page={page}', 'SSL');

        $data['pagination'] = $pagination->render();

        $this->response->setOutput($this->load->view('extension/module/aftership_list', $data));
    }

    public function getCouriers()
    {

        $couriers = new AfterShip\Couriers($this->request->post['key']);
        try {
            $response = $couriers->get();
            $this->model_extension_module_aftership->clearSettingTracking();
            $data = $response['data']['couriers'];
            $sort_order = 1;
            $sql_data = array();
            foreach ($data as $item) {
                $sql_data[] = array(
                    'code' => $item['slug'],
                    'name' => $item['name'],
                    'other_name' => $item['other_name'],
                    'phone' => $item['phone'],
                    'status' => 1,
                    'sort_order' => $sort_order
                );
                $sort_order++;
            }

            $setting_tracking = $this->model_extension_module_aftership->updateSettingTracking($sql_data, $this->request->post['key']);
            if ($setting_tracking) {
                $data['text_sync'] = $this->language->get('text_sync_success');
            } else {
                $data['text_sync'] = $this->language->get('text_sync_fail');
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array(
                'code' => 1,
                'msg' => $data['text_sync'],
                'tracks' => $sql_data
            )));
        } catch (Exception $e) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array(
                'code' => 0,
                'err_msg' => $e->getMessage()
            )));
        }
    }

    public function install()
    {
        $this->model_extension_module_aftership->install();
    }

    public function uninstall()
    {
        $this->model_extension_module_aftership->uninstall();
    }

    public function delete()
    {
        $json = array();

        if (!$this->user->hasPermission('modify', 'extension/module/aftership')) {
            $json['error'] = $this->language->get('error_permission');
        }

        try {
            $this->model_extension_module_aftership->delOrderShippingTrack($this->request->get['id']);
            $trackings = new AfterShip\Trackings($this->aftership_key);
            $trackings->delete($this->request->get['code'], $this->request->get['number']);
        } catch (AftershipException $e) {
            $json['error'] = $e->getMessage();
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        if (!isset($json['error'])) {
            $json['success'] = $this->language->get('text_del_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}