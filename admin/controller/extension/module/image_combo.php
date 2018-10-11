<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-02-02 11:25:38
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-02-02 14:48:29
 */

require_once(DIR_APPLICATION . 'controller/extension/module_base_controller.php');

class ControllerExtensionModuleImageCombo extends GDModuleBaseController
{
    protected $module_code = 'image_combo';
    protected $for_layout = true;

    protected function overwriteDataForView($data)
    {
        $data['styles'] = [
            'single_one_row' => 1,
            'two_one_row' => 2,
            'three_one_row' => 3,
            'one_left_two_right' => 3,
            'four_one_row' => 4,
        ];

        return $data;
    }

    protected function validate_form()
    {
        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = t('error_name');
        }
    }
}
