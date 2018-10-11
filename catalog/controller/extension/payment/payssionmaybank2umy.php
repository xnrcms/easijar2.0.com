<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionMaybank2umy extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'maybank2u_my';
}