<?php

/**
 * after_ship.php
 *
 * @copyright 2018 OpenCart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-09-18 16:17
 * @modified 2018-09-18 16:17
 */

use Seller\MsSeller;
use Seller\Aftership;
use AfterShip\Trackings as AfterShipTrackings;
use AfterShip\Couriers as AfterShipCouriers;

class ControllerSellerAftership extends Controller
{
    private $seller = null;
    private $aftership = null;
    private $aftership_key;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->seller = MsSeller::getInstance($registry);
        $this->aftership = Aftership::getInstance();
        if (!$this->seller->isSeller()) {
            die('Error: Access Denied!');
        }
        $this->aftership_key = $this->config->get('module_aftership_tracking_key');
        $this->load->language('seller/order');
    }

    public function getList()
    {
        $order_id = array_get($this->request->get, 'order_id', 0);
        if (!$order_id) {
            die('Error: order id is required!');
        }

        $this->load->language('seller/order');

        $limit = 10;
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['error'] = '';
        $data['success'] = '';

        $data['histories'] = array();

        $results = $this->aftership->getOrderShippingTracks($order_id, ($page - 1) * $limit, $limit);
        foreach ($results as $result) {
            $tracking_code = $result['tracking_code'];
            $tracking_number = $result['tracking_number'];

            $track = $this->url->link('seller/aftership/getTrace', 'slug=' . $tracking_code . '&number=' . $tracking_number);

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

        $data['order_id'] = $order_id;
        $history_total = $this->aftership->getTotalOrderShippingTracks($order_id);

        $pagination = new Pagination();
        $pagination->total = $history_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/tracking/getList', 'order_id=' . $order_id . '&page={page}', 'SSL');

        $data['pagination'] = $pagination->render();

        $this->response->setOutput($this->load->view('multiseller/aftership_list', $data));
    }

    public function add()
    {
        $json = array();
        $tracking_number = array_get($this->request->post, 'tracking_number', '');
        $tracking_code = array_get($this->request->post, 'tracking_code', '');
        $order_id = array_get($this->request->get, 'order_id', '');
        if (!$tracking_number || !$tracking_code) {
            $json['error'] = $this->language->get('error_tracking_code_number_required');
        }

        if (!isset($json['error'])) {
            $tracking = new AfterShipTrackings($this->aftership_key);
            $existTrackingData = array();
            try {
                $existTrackingData = $tracking->get($tracking_code, $tracking_number);
            } catch (Exception $e) {

            }
            try {
                $courier = new AfterShipCouriers($this->aftership_key);
                $courier->detect($tracking_number);
                $this->aftership->addOrderShippingTrack($order_id, $this->request->post);

                if ($existTrackingData && $existTrackingData['meta']['code'] == 200) {
                    throw new Exception('Tracking number exist');
                }
                $tracking_info = array(
                    'slug' => $this->request->post['tracking_code']
                );
                $tracking->create($tracking_number, $tracking_info);
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

    public function delete()
    {
        $json = array();
        $id = array_get($this->request->get, 'id', '');
        $seller_shipping = $this->aftership->getSellerShipping($id);
        if (!$seller_shipping) {
            $json['error'] = 'This shipping is not belongs to you!';
        }
        if (!$json) {
            try {
                $this->aftership->delOrderShippingTrack($id);
                $trackings = new AfterShipTrackings($this->aftership_key);
                $trackings->delete($this->request->get['code'], $this->request->get['number']);
            } catch (AftershipException $e) {
                $json['error'] = $e->getMessage();
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }

            if (!isset($json['error'])) {
                $json['success'] = $this->language->get('text_del_success');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getTrace()
    {
        $typeSlug = array_get($this->request->get, 'slug', 0);
        $typeNu = array_get($this->request->get, 'number', 0);

        $json = array();
        try {
            $trackings = new AfterShipTrackings($this->aftership_key);
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
}