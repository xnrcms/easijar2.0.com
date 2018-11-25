<?php

/**
 *  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

require_once 'Customweb/Payment/Authorization/Widget/IConfirmationRequiredAdapter.php';
require_once 'Customweb/Util/Rand.php';

require_once 'OPPCw/Language.php';
require_once 'OPPCw/Util.php';
require_once 'OPPCw/Adapter/AbstractAdapter.php';



/**
 *
 * @author Thomas Hunziker
 * @Bean
 */
class OPPCw_Adapter_WidgetAdapter extends OPPCw_Adapter_AbstractAdapter {
	
	/**
	 *
	 * @var OPPCw_Entity_Transaction
	 */
	private $transaction = null;
	private $visibleFormFields = array();
	private $formActionUrl = null;
	private $widgetHtml = null;
	private $errorMessage = '';

	public function getPaymentAdapterInterfaceName(){
		return 'Customweb_Payment_Authorization_Widget_IAdapter';
	}

	/**
	 *
	 * @return Customweb_Payment_Authorization_Widget_IAdapter
	 */
	public function getInterfaceAdapter(){
		return parent::getInterfaceAdapter();
	}

	protected function preparePaymentFormPane(){
		$this->transaction = $this->createNewTransaction();
		$this->visibleFormFields = $this->getInterfaceAdapter()->getVisibleFormFields($this->getOrderContext(), $this->getAliasTransactionObject(), 
				$this->getFailedTransactionObject(), $this->transaction->getTransactionObject()->getPaymentCustomerContext());
		
		if ($this->visibleFormFields !== null && count($this->visibleFormFields) > 0) {
			$this->formActionUrl = OPPCw_Util::getUrl('process', 'widget', 
					array(
						'cw_transaction_id' => $this->transaction->getTransactionId() 
					));
		}
		else {
			$this->prepareWithFormData(array(), $this->transaction);
		}
		OPPCw_Util::getEntityManager()->persist($this->transaction);
	}

	public function prepareWithFormData(array $formData, OPPCw_Entity_Transaction $transaction){
		$adapter = $this->getInterfaceAdapter();
		if ($adapter instanceof Customweb_Payment_Authorization_Widget_IConfirmationRequiredAdapter) {
			$this->widgetHtml = $adapter->getWidgetConfirmationHTML($transaction->getTransactionObject(), $formData);
		}
		else {
			$this->widgetHtml = $adapter->getWidgetHTML($transaction->getTransactionObject(), $formData);
		}
		
		OPPCw_Util::getEntityManager()->persist($transaction);
		if ($transaction->getTransactionObject()->isAuthorizationFailed()) {
			$this->widgetHtml = null;
			$errorMessage = current($transaction->getTransactionObject()->getErrorMessages());
			/* @var $errorMessage Customweb_Payment_Authorization_IErrorMessage */
			if (is_object($errorMessage)) {
				$this->errorMessage = $errorMessage->getUserMessage();
			}
			else {
				$this->errorMessage = OPPCw_Language::_("Failed to initialize transaction with an unkown error");
			}
		}
	}

	public function getWidget(){
		if ($this->widgetHtml !== null) {
			$id = 'oppcw-a' . Customweb_Util_Rand::getRandomString(50);
			return '<div class="oppcw-widget" id="' . $id . '"></div> <script type="text/javascript">
					var oppcwCallback = function() {
					var html = "' . urlencode($this->widgetHtml) . '"; 
					html = decodeURIComponent(html.replace(/\+/g, \' \')); 
					var jQueryAjaxSettingsCache = jQuery.ajaxSettings.cache; 
					jQuery.ajaxSettings.cache = true; 
					jQuery("#' . $id . '").replaceWith(html); 
					jQuery.ajaxSettings.cache = jQueryAjaxSettingsCache;
				};
				oppcwCallback();	
				</script>';
		}
		else {
			return '<div class="warning">' . $this->errorMessage .
					 '<img src="catalog/view/theme/default/image/close.png" alt="" class="close" /></div>';
		}
	}

	protected function getOrderConfirmationButton(){
		if ($this->formActionUrl === null) {
			return '';
		}
		else {
			return parent::getOrderConfirmationButton();
		}
	}

	protected function getAdditionalFormHtml(){
		if ($this->formActionUrl === null) {
			return $this->getWidget();
		}
		else {
			return parent::getAdditionalFormHtml();
		}
	}

	protected function getVisibleFormFields(){
		return $this->visibleFormFields;
	}

	protected function getFormActionUrl(){
		return $this->formActionUrl;
	}
}