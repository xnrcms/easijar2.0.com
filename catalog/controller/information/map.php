<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-01-19 11:56:57
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-01-19 12:23:52
 */

class ControllerInformationMap extends Controller
{
    const KEY = '4OS1ut0EX72MCu0R3P1lqCTu';

    public function index()
    {
        $key = self::KEY;

        if ($geocode = array_get($this->request->get, 'geocode')) {
            $geocode = urldecode($geocode);
        } else {
            $geocode = config('config_geocode');
        }

        $address = nl2br(config('config_address'));
        $store_name = config('config_name');

        $this->response->setOutput($this->load->view('information/map', compact('key', 'geocode', 'address', 'store_name')));
    }
}
