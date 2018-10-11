<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionOxxo extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'oxxo_mx';
}