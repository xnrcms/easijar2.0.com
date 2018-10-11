<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionItau extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'itau_br';
}