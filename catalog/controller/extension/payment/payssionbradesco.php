<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionBradesco extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'bradesco_br';
}