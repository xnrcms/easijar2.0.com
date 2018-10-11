<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionMOLPay extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'molpay';
}