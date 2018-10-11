<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionEsapaymy extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'esapay_my';
}