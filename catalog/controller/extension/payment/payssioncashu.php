<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionCashu extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'cashu';
}