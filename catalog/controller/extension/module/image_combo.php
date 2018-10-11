<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-02-02 15:33:14
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-07-23 17:16:10
 */

class ControllerExtensionModuleImageCombo extends Controller
{
    public function index($setting)
    {
        if (config('is_mobile')) {
            return;
        }

        if (!$items = array_get($setting, 'item')) {
            return;
        }

        $styles = [
            'single_one_row' => 1,
            'two_one_row' => 2,
            'three_one_row' => 3,
            'one_left_two_right' => 3,
            'four_one_row' => 4,
        ];

        $style = array_get($setting, 'style');

        $data['items'] = [];
        foreach ($items as $item) {
            $image = array_get($item, 'image');
            $image = $image ? $image : 'placeholder.png';
            $href = array_get($item, 'href.' . current_language_id());

            $data['items'][] = array(
                'image' => $this->url->imageLink($image),
                'href' => $href
            );

            if (count($data['items']) >= $styles[$style]) {
                break;
            }
        }

        if ($data['items']) {
            $data['style'] = $style;

            switch ($style) {
              case 'single_one_row':
                $data['class'] = 'col-sm-12';
                break;
              case 'two_one_row':
                $data['class'] = 'col-sm-6 col-xs-6';
                break;
              case 'three_one_row':
                $data['class'] = 'col-sm-4 col-xs-12';
                break;
              case 'four_one_row':
                $data['class'] = 'col-sm-3 col-xs-6';
                break;
            }

            return $this->load->view('extension/module/image_combo', $data);
        }
    }
}
