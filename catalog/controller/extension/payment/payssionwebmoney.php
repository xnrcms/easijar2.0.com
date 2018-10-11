<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionWebMoney extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'webmoney';
}