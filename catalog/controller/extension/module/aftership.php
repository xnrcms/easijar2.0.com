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
 * @created 2018-07-03 17:42
 * @modified 2018-07-03 17:42
 */

class ControllerExtensionModuleAftership extends Controller
{
    public function getTrace()
    {
        $this->load->language('extension/module/aftership');

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
            $trace = new AfterShip\Trackings($this->config->get('tracking_key'));
            $response = $trace->get($typeSlug, $typeNu);
        } catch (AftershipException $e) {
            $json['error'] = $e->getMessage();
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        if (isset($json['error'])) {
            $track = "<div id=errordiv style=width:500px;border:#fe8d1d 1px solid;padding:20px;background:#FFFAE2;>
                      <p style=line-height:28px;margin:0px;padding:0px;color:#F21818;>" . $json['error'] . "</p>
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

    public function getTraceForApi()
    {
        $this->load->language('extension/module/aftership');

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
            $trace = new AfterShip\Trackings($this->config->get('tracking_key'));
            $response = $trace->get($typeSlug, $typeNu);
        } catch (AftershipException $e) {
            $json['error'] = $e->getMessage();
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        if (isset($json['error'])) {
            return $json;
        } else {
            $json['text_time']      = $this->language->get('text_time');
            $json['text_time']      = $this->language->get('text_station');
            $json['data']           = isset($response['data']['tracking']['checkpoints']) ? $response['data']['tracking']['checkpoints'] : [];
        }
        
        return $json;
    }
}