<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionNetCashJP extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'netcash_jp';
}