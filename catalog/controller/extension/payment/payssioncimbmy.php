<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionCIMBmy extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'cimb_my';
}