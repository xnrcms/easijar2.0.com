<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionBancomer extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'bancomer_mx';
}