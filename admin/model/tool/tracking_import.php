<?php
/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2018-07-26 11:22:04
 * @modified         2018-07-26 17:37:17
 */

class ModelToolTrackingImport extends Model {
    public function importTracking($file_path) {
        $this->load->language('tool/tracking_import');

        $this->load->model('extension/module/express_tracking');

        $file = fopen($file_path,"r");
        $first = true;
        while(!feof($file)) {
            $tracking = fgetcsv($file); // 上传文件按格式： 订单ID， 快递公司编号， 快递单号， 附言

            if ($tracking) {
                return true;
            }
            $tracking = $this->utf8($tracking);

            if ($first) {
                $first = false;
                $template = explode(',', $this->language->get('text_csv_template'));

                if ($tracking[0] != $template[0] || $tracking[1] != $template[1] || $tracking[2] != $template[2] || $tracking[3] != $template[3]) {
                    return sprintf($this->language->get('error_csv_template'), $this->language->get('text_csv_template'));
                }
                continue;
            }

            if (!$this->isTrackingCode($tracking[1])) {
                return sprintf($this->language->get('error_tracking_code'), $tracking[1]);
            }

            $order_id = $tracking[0];
            $data = array(
                'tracking_code' => $tracking[1],
                'tracking_number' => $tracking[2],
                'kd_comment' => $tracking[3]
            );
            $this->model_extension_module_express_tracking->addOrderShippingtrack($order_id, $data);
        }

        fclose($file);

        return true;
    }

    private function utf8($data) {
        if (is_array($data)) {
            foreach ($data as $key=>$item) {
                $encode = mb_detect_encoding($item, array("GB2312","GBK","BIG5","ASCII","UTF-8"));
                if( $encode != "UTF-8") {
                    $data[$key] = mb_convert_encoding($item, 'UTF-8', $encode);
                }
            }

        } else {
            $encode = mb_detect_encoding($data, array("GB2312","GBK","BIG5","ASCII","UTF-8"));
            if( $encode != "UTF-8") {
                $data = mb_convert_encoding($data, 'UTF-8', $encode);
            }
        }

        return $data;
    }

    private function isTrackingCode($code) {
        $kd_tracking_data = $this->config->get('module_express_tracking_data');

        $result = false;
        foreach ($kd_tracking_data as $express) {
            if ($express['code'] == $code) {
                $result = true;
                break;
            }
        }

        return($result);
    }
}
