<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionTrustpay extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'trustpay';
}