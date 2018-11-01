<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-02-02 11:27:56
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-07-23 17:15:31
 */

class ControllerExtensionModuleIcon extends Controller
{
    public function index($setting,$api=false)
    {
        if (!$items = array_get($setting, 'item')) {
            return;
        }

        $sort_order = array();
        foreach ($items as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }
        array_multisort($sort_order, SORT_ASC, $items);

        $data['items'] = array();
        foreach ($items as $item) {
            $image = array_get($item, 'image');
            if (image_exists($image)) {
                $path       = explode('path=', $item['href']);
                $data['items'][] = array(
                    'image'     => $this->url->imageLink($image),
                    'title'     => array_get($item, 'title.' . current_language_id()),
                    'href'      => $item['href'],
                    'cid'      => isset($path[1]) ? $path[1] : 0,
                );
            }
        }

        if ($api) {
            return $data;
        }

        if ($data['items']) {
            $data['module_id'] = $setting['module_id'];
            return $this->load->view('extension/module/icon', $data);
        }
    }
}
