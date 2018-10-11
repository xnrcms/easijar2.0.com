<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionBanamex extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'banamex_mx';
}