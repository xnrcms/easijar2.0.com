<?php 
/**
  * You are allowed to use this API in your web application.
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

require_once 'OPPCw/Adapter/AbstractAdapter.php';

/**
 * @author Thomas Hunziker
 * @Bean 
 */
class OPPCw_Adapter_AjaxAdapter extends OPPCw_Adapter_AbstractAdapter {
	
	/**
	 * @var OPPCw_Entity_Transaction
	 */
	private $transaction = null;
	private $visibleFormFields = array();
	private $ajaxScriptUrl = null;
	private $javaScriptCallbackFunction = null;

	public function getPaymentAdapterInterfaceName() {
		return 'Customweb_Payment_Authorization_Ajax_IAdapter';
	}
	
	/**
	 * @return Customweb_Payment_Authorization_Ajax_IAdapter
	 */
	public function getInterfaceAdapter() {
		return parent::getInterfaceAdapter();
	}
	
	protected function preparePaymentFormPane() {
		$this->transaction = $this->createNewTransaction();
		$this->visibleFormFields = $this->getInterfaceAdapter()->getVisibleFormFields(
			$this->getOrderContext(),
			$this->getAliasTransactionObject(),
			$this->getFailedTransactionObject(),
			$this->transaction->getTransactionObject()->getPaymentCustomerContext()
		);
		$this->ajaxScriptUrl = $this->getInterfaceAdapter()->getAjaxFileUrl($this->transaction->getTransactionObject());
		$this->javaScriptCallbackFunction = $this->getInterfaceAdapter()->getJavaScriptCallbackFunction($this->transaction->getTransactionObject());
		OPPCw_Util::getEntityManager()->persist($this->transaction);
	}
	
	protected function getPaymentFormPane() {
	
		$this->preparePaymentFormPane();
	
		$output = '<div id="oppcw-checkout-form-pane">';
		
		$output .= '<script src="' . $this->ajaxScriptUrl . '"></script>';
		
		$output .= '<script type="text/javascript">';
		$output .= "\n var oppcw_ajax_submit_callback = " . $this->javaScriptCallbackFunction . ";\n";
		$output .= '</script>';
		
		$output .= '<form id="oppcw-confirmation-ajax-authorization-form" class="oppcw-confirmation-form  form-horizontal">';
	
		$visibleFormFields = $this->getVisibleFormFields();
		if ($visibleFormFields !== null && count($visibleFormFields) > 0) {
			$renderer = new OPPCw_Form_FrontendRenderer();
			$renderer->setCssClassPrefix('oppcw-');
			$output .= $renderer->renderElements($visibleFormFields);
		}
	
		$output .= '</form>';
		
		$output .= $this->getOrderConfirmationButton();
	
		$output .= '</div>';
	
		return $output;
	}
	
	protected function getVisibleFormFields() {
		return $this->visibleFormFields;
	}
		
}