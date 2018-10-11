<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionCherryCredits extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'cherrycredits';
}