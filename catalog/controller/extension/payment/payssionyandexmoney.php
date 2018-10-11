<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionYandexmoney extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'yamoney';
}