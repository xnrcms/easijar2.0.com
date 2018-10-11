<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionSOFORT extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'sofort';
}