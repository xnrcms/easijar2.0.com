<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-01-25 12:54:43
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-02-02 11:33:54
 */

class ControllerExtensionModuleCustomerService extends Controller
{
    public function index($setting)
    {
        if (config('is_mobile')) {
            return;
        }

        if (!$items = array_get($setting, 'item')) {
            return;
        }

        $data['items'] = [];
        foreach ($items as $item) {
            $image = array_get($item, 'image');
            $title = array_get($item, 'title.' . current_language_id());
            $subtitle = array_get($item, 'subtitle.' . current_language_id());

            if (empty($title) && empty($subtitle)) {
                continue;
            }

            $data['items'][] = array(
                'image'    => $image,
                'title'    => $title,
                'subtitle' => $subtitle
            );
        }

        if ($data['items']) {
            return $this->load->view('extension/module/customer_service', $data);
        }
    }
}
