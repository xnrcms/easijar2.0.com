<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionAffinepgmy extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'affinepg_my';
}