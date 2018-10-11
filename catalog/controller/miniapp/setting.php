<?php

/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-04-28 15:00:16
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-05-30 14:32:14
 */

final class ControllerMiniAppSetting extends Controller
{
    public function index()
    {
        $data['keywords'] = $this->getKeywords();

        $this->jsonOutput($data);
    }

    private function getKeywords()
    {
        if ($keywords = config('miniapp_keywords.' . current_language_id())) {
            return explode(',', $keywords);
        }
    }
}
