<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssioniDEALnl extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'ideal_nl';
}