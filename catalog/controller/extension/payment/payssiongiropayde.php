<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionGiropayde extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'giropay_de';
}