<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionBitcoin extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'bitcoin';
}