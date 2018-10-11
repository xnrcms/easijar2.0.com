<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionDotpay extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'dotpay_pl';
}