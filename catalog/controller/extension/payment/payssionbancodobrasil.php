<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionBancodobrasil extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'bancodobrasil_br';
}