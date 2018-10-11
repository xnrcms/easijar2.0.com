<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-02-02 11:25:38
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-02-02 11:31:38
 */

require_once(DIR_APPLICATION . 'controller/extension/module_base_controller.php');

class ControllerExtensionModuleImageText extends GDModuleBaseController
{
    protected $module_code = 'image_text';
    protected $for_layout = true;

    protected function validate_form()
    {
        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = t('error_name');
        }
    }
}
