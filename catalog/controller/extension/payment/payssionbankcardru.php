<?php

require_once(realpath(dirname(__FILE__)) . "/payssion.php");
class ControllerExtensionPaymentPayssionBankcardru extends ControllerExtensionPaymentPayssion {
	protected $pm_id = 'bankcard_ru';
}