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
    public function index()
    {
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
            $track = '<div id=errordiv style=width:100%;border:#fe8d1d 1px solid;padding:20px;background:#FFFAE2;>
									<p style=line-height:28px;margin:0px;padding:0px;color:#F21818;>' .$tracking['message'].'</p>
								</div>';
        } else {
            $track = "<table width='100%' border='0' cellspacing='0' cellpadding='0' id='showtablecontext' style='border-collapse:collapse;border-spacing:0;'>";

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
}
