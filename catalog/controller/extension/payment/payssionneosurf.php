<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionNeosurf extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'neosurf';
}