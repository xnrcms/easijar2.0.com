<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionFPXmy extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'fpx_my';
}