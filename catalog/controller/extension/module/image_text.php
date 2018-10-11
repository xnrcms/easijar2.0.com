<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-02-02 11:27:56
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-07-23 17:16:38
 */

class ControllerExtensionModuleImageText extends Controller
{
    public function index($setting)
    {
        if (config('is_mobile')) {
            return;
        }

        if (!$items = array_get($setting, 'item')) {
            return;
        }

        $data['items'] = array();
        foreach ($items as $item) {
            $image = array_get($item, 'image');
            $title = array_get($item, 'title.' . current_language_id());
            $text = array_get($item, 'text.'. current_language_id());

            if (empty($image) && empty($title) && empty($text)) {
                continue;
            }

            $href = array_get($item, 'href.' . current_language_id());
            $direction = array_get($item, 'direction', 'img-left');

            $data['items'][] = array(
                'image'     => $this->url->imageLink($image),
                'href'      => $href,
                'title'     => $title,
                'text'     => $text,
                'direction' => $direction
            );
        }

        if ($data['items']) {
            return $this->load->view('extension/module/image_text', $data);
        }
    }
}
