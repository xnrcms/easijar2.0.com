<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionDragonpayph extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'dragonpay_ph';
}