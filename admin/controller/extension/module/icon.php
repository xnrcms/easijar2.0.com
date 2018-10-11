<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-02-02 11:25:38
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-04-23 14:35:07
 */

require_once(DIR_APPLICATION . 'controller/extension/module_base_controller.php');

class ControllerExtensionModuleIcon extends GDModuleBaseController
{
    protected $module_code = 'icon';
    protected $for_layout = true;

    protected function validate_form()
    {
        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = t('error_name');
        }

        $items = array_get($this->request->post, 'item');
        if (!$items) {
            return;
        }

		$index = 0;
        foreach ($items as $item) {
        	if (!$item['image']) {
        	    $this->error['image'][$index] = t('error_image');
        	}

        	foreach ($item['title'] as $language_id => $value) {
        	    if (!utf8_strlen($value)) {
        	        $this->error['title'][$index][$language_id] = t('error_title');
        	    }
        	}

			$index++;
        }
    }
}
