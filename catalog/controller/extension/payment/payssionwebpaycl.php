<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionWebpaycl extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'webpay_cl';
}