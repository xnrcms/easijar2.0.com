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

require_once 'Customweb/OPP/Authorization/AbstractAdapter.php';
require_once 'Customweb/Payment/Authorization/Widget/IAdapter.php';
require_once 'Customweb/I18n/LocalizableString.php';
require_once 'Customweb/OPP/Authorization/Widget/ParameterBuilder.php';
require_once 'Customweb/I18n/Translation.php';
require_once 'Customweb/OPP/Request.php';
require_once 'Customweb/Payment/Authorization/ITransaction.php';
require_once 'Customweb/Core/Logger/Factory.php';



/**
 * @Bean
 */
class Customweb_OPP_Authorization_Widget_Adapter extends Customweb_OPP_Authorization_AbstractAdapter implements
		Customweb_Payment_Authorization_Widget_IAdapter {

	public function getAdapterPriority(){
		return 50;
	}

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function createTransaction(Customweb_Payment_Authorization_Widget_ITransactionContext $transactionContext, $failedTransaction){
		return $this->createTransactionInternal($transactionContext, $failedTransaction);
	}

	public function getWidgetHTML(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		try{
			if ($transaction->getTransactionContext()->getAlias() instanceof Customweb_Payment_Authorization_ITransaction) {
				$processAliasUrl = $this->getEndpointAdapter()->getUrl('process', 'alias',
						array(
							'cw_transaction_id' => $transaction->getExternalTransactionId()
						));
				return '<div>' . Customweb_I18n_Translation::__('Please wait...') . '</div><script type="text/javascript">window.location = "' .
						$processAliasUrl . '"</script>';
			}
			$checkoutId = $transaction->getCheckoutId();
			if(empty($checkoutId)){
				$checkoutId = $this->generateCheckout($transaction, $formData);
				$transaction->setCheckoutId($checkoutId);
			}
			$cssUrl = $this->getContainer()->getBean('Customweb_Asset_IResolver')->resolveAssetUrl('widget.css');
			$responseUrl = $this->getEndpointAdapter()->getUrl('process', 'index',
					array(
						'cw_transaction_id' => $transaction->getExternalTransactionId()
					));
			$html = '<script>var wpwlOptions = {';
			$html .= 'locale: "' . $transaction->getTransactionContext()->getOrderContext()->getLanguage()->getIso2LetterCode() . '",';
			$html .= 'style: "' . $this->getPaymentMethod($transaction->getPaymentMethod())->getWidgetStyle() . '",';
			$html .= $this->getPaymentMethod($transaction->getPaymentMethod())->getAdditionalWidgetOptionString($transaction);
			$html .= '}</script>';
			$html .= '<script src="' . $this->getJavascriptUrl($checkoutId) . '"></script>';
			$html .= '<form action="' . $responseUrl . '" class="paymentWidgets">';
			$html .= $this->getPaymentMethod($transaction->getPaymentMethod())->getPaymentMethodBrand();
			$html .= '</form>';
			$html .= '<script>
						if (!document.getElementById("_opp_-widget-style")) {
							var head = document.getElementsByTagName("head")[0];
							var cssLink = document.createElement("link");
							cssLink.href = "'.$cssUrl.'";
							cssLink.id="_opp_-widget-style";
							cssLink.type="text/css";
	 						cssLink.rel = "stylesheet";	
							head.appendChild(cssLink);
						}	
					</script>';
			return $html;
		}
		catch(Exception $e){
			$transaction->setAuthorizationFailed($e->getMessage());
			return $e->getMessage();
		}
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		$logger = Customweb_Core_Logger_Factory::getLogger(get_class($this));
		try {
			//This request is required, but it can executed only once reliably, so we make the call
			$logger->logInfo('Requesting Status for checkoutId: '.$transaction->getCheckoutId());
			$request = new Customweb_OPP_Request($this->getCheckoutStatusUrl($transaction->getCheckoutId()));
			$request->setMethod(Customweb_OPP_Request::METHOD_GET);
			$request->setData($this->getParameterBuilder($transaction)->buildStatusParameters());
			
			$response = $request->send('Checkout Status Check');
			if($response != null && ($response->result->code != '200.300.404' || stripos($response->result->description, 'No payment session found for the requested id') === false)){
				return $this->finalizeAuthorization($transaction, $response);
			}
		}
		catch (Exception $e) {
		}
		$response = null;
		
		try {
			$logger->logInfo('Requesting Status for transactionExternalId: '.$this->getPaymentMethod($transaction->getPaymentMethod())->getMerchantTransactionId($transaction));
			$request = new Customweb_OPP_Request($this->getPaymentStatusUrlForMerchantId());
			$request->setMethod(Customweb_OPP_Request::METHOD_GET);
			$request->setData($this->getParameterBuilder($transaction)->buildStatusParametersMerchantId());
			$response = $request->send('Merchant Id Status Check');
			if($response->result->code == '700.400.580'){
				$transaction->setAuthorizationFailed(new Customweb_I18n_LocalizableString("The transaction with external Id ".$this->getPaymentMethod($transaction->getPaymentMethod())->getMerchantTransactionId($transaction)." ist not available in the remote system"));
				return $this->redirect($transaction);
			}
			if($response->result->code != '000.000.100'){
				throw new Exception("Received unexpected result code for transaction status request. Code: ".$response->result->code);
			}				
			$paymentResponses = $response->payments;
			foreach($paymentResponses as $paymentResponse){
				if($paymentResponse->paymentType == 'PA' || $paymentResponse->paymentType == 'DB' || $paymentResponse->paymentType == 'PA.CP'){
					$result= $this->finalizeAuthorization($transaction, $paymentResponse);
					if($transaction->isAuthorizationFailed() || $transaction->isAuthorized()){
						return $result;
					}
				}
			}
			$logger->logError('No valid payment in the status response.' , $response);
			return $this->redirect($transaction);
		}
		catch (Exception $e) {
			Customweb_Core_Logger_Factory::getLogger(get_class())->logInfo("Error quering transaction status.", $e->getMessage());
		}
		return $this->redirect($transaction);
	}

	/**
	 *
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param array $parameters
	 * @throws Exception
	 */
	protected function generateCheckout(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		$request = new Customweb_OPP_Request($this->getCheckoutUrl());
		$parameters = $this->getParameterBuilder($transaction)->buildAuthorizationParameters($formData);
		$transaction->setAuthorizationChannel($parameters['authentication.entityId']);
		$request->setData($parameters);
		$response = $request->send('Generate Checkout');
		if ($response->result->code != '000.200.100' || !isset($response->id)) {
			throw new Exception('The checkout could not have been created.');
		}
		return $response->id;
	}

	/**
	 *
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @return Customweb_OPP_Authorization_Widget_ParameterBuilder
	 */
	protected function getParameterBuilder(Customweb_Payment_Authorization_ITransaction $transaction){
		return new Customweb_OPP_Authorization_Widget_ParameterBuilder($this->getContainer(), $transaction);
	}
}