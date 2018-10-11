<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionAlipaycn extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'alipay_cn';
}