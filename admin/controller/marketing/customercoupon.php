<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-10 18:12:00
 * @modified         2016-11-10 18:12:00
 */

class ControllerMarketingCustomercoupon extends Controller
{
    private $error = array();

    public function generateCoupon()
    {
        $json = array();
        try {
            $json['coupon_code'] = $this->generateRandomString(8);
        } catch (Exception $e) {
            echo $e;
        }
		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function generateRandomString($length = 10)
    {
        static $counter;
        /*Set counter to limit time callback function*/
        if ($counter) {
            ++$counter;
        } else {
            $counter = 0;
        }
        /*end here*/

        $this->load->model('marketing/coupon');

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; ++$i) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        $coupon_info = $this->model_marketing_coupon->getCouponByCode($randomString);
        $exists = false;
        if ($coupon_info) {
            if (!isset($this->request->get['coupon_id'])) {
                $exists = true;
            } elseif ($coupon_info['coupon_id'] != $this->request->get['coupon_id']) {
                $exists = true;
            }
        }

        if ($exists) {
            /*Limit callback the functioni 50 times*/
            if ($counter >= 50) {
                if ($length < 10) {
                    $length = (int) $length + 1;
                } else {
                    $length = 10;
                }
                $counter = 0;
            }
            $randomString = $this->generateRandomString($length);
        }

        return $randomString;
    }
}
