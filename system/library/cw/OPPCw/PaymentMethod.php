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


require_once 'Customweb/Payment/Authorization/IPaymentMethod.php';

require_once 'OPPCw/Util.php';
require_once 'OPPCw/Entity/Transaction.php';
require_once 'OPPCw/PaymentMethod.php';
require_once 'OPPCw/TransactionContext.php';
require_once 'OPPCw/PaymentMethodWrapper.php';
require_once 'OPPCw/Store.php';
require_once 'OPPCw/Language.php';
require_once 'OPPCw/DefaultPaymentMethodDefinition.php';
require_once 'OPPCw/SettingApi.php';
require_once 'OPPCw/OrderContext.php';


final class OPPCw_PaymentMethod implements Customweb_Payment_Authorization_IPaymentMethod{

	/**
	 * @var OPPCw_IPaymentMethodDefinition
	 */
	private $paymentMethodDefinitions;

	/**
	 * @var OPPCw_SettingApi
	 */
	private $settingsApi;

	private static $completePaymentMethodDefinitions = array(
		'generic' => array(
			'machineName' => 'Generic',
 			'frontendName' => 'Generic',
 			'backendName' => 'Open Payment Platform: Generic',
 		),
 		'americanexpress' => array(
			'machineName' => 'AmericanExpress',
 			'frontendName' => 'American Express',
 			'backendName' => 'Open Payment Platform: American Express',
 		),
 		'boleto' => array(
			'machineName' => 'Boleto',
 			'frontendName' => 'Boleto Bancário',
 			'backendName' => 'Open Payment Platform: Boleto Bancário',
 		),
 		'cartebleue' => array(
			'machineName' => 'CarteBleue',
 			'frontendName' => 'Carte Bleue',
 			'backendName' => 'Open Payment Platform: Carte Bleue',
 		),
 		'dankort' => array(
			'machineName' => 'Dankort',
 			'frontendName' => 'Dankort',
 			'backendName' => 'Open Payment Platform: Dankort',
 		),
 		'diners' => array(
			'machineName' => 'Diners',
 			'frontendName' => 'Diners Club',
 			'backendName' => 'Open Payment Platform: Diners Club',
 		),
 		'directdebitssepa' => array(
			'machineName' => 'DirectDebitsSepa',
 			'frontendName' => 'Sepa Direct Debits',
 			'backendName' => 'Open Payment Platform: Sepa Direct Debits',
 		),
 		'discovercard' => array(
			'machineName' => 'DiscoverCard',
 			'frontendName' => 'Discover Card',
 			'backendName' => 'Open Payment Platform: Discover Card',
 		),
 		'eps' => array(
			'machineName' => 'Eps',
 			'frontendName' => 'EPS',
 			'backendName' => 'Open Payment Platform: EPS',
 		),
 		'giropay' => array(
			'machineName' => 'Giropay',
 			'frontendName' => 'giropay',
 			'backendName' => 'Open Payment Platform: giropay',
 		),
 		'ideal' => array(
			'machineName' => 'IDeal',
 			'frontendName' => 'iDEAL',
 			'backendName' => 'Open Payment Platform: iDEAL',
 		),
 		'jcb' => array(
			'machineName' => 'Jcb',
 			'frontendName' => 'JCB',
 			'backendName' => 'Open Payment Platform: JCB',
 		),
 		'klarnainvoice' => array(
			'machineName' => 'KlarnaInvoice',
 			'frontendName' => 'Klarna Invoice',
 			'backendName' => 'Open Payment Platform: Klarna Invoice',
 		),
 		'maestro' => array(
			'machineName' => 'Maestro',
 			'frontendName' => 'Maestro',
 			'backendName' => 'Open Payment Platform: Maestro',
 		),
 		'mastercard' => array(
			'machineName' => 'MasterCard',
 			'frontendName' => 'MasterCard',
 			'backendName' => 'Open Payment Platform: MasterCard',
 		),
 		'openinvoice' => array(
			'machineName' => 'OpenInvoice',
 			'frontendName' => 'Open Invoice',
 			'backendName' => 'Open Payment Platform: Open Invoice',
 		),
 		'payolutionelv' => array(
			'machineName' => 'PayolutionElv',
 			'frontendName' => 'Payolution ELV',
 			'backendName' => 'Open Payment Platform: Payolution ELV',
 		),
 		'payolutionins' => array(
			'machineName' => 'PayolutionIns',
 			'frontendName' => 'Payolution Installment',
 			'backendName' => 'Open Payment Platform: Payolution Installment',
 		),
 		'paybox' => array(
			'machineName' => 'Paybox',
 			'frontendName' => 'Paybox Mobile Phone Payment',
 			'backendName' => 'Open Payment Platform: Paybox Mobile Phone Payment',
 		),
 		'paypal' => array(
			'machineName' => 'PayPal',
 			'frontendName' => 'PayPal',
 			'backendName' => 'Open Payment Platform: PayPal',
 		),
 		'paysafecard' => array(
			'machineName' => 'Paysafecard',
 			'frontendName' => 'paysafecard',
 			'backendName' => 'Open Payment Platform: paysafecard',
 		),
 		'prepayment' => array(
			'machineName' => 'Prepayment',
 			'frontendName' => 'Prepayment',
 			'backendName' => 'Open Payment Platform: Prepayment',
 		),
 		'skrill' => array(
			'machineName' => 'Skrill',
 			'frontendName' => 'Skrill (Moneybookers)',
 			'backendName' => 'Open Payment Platform: Skrill (Moneybookers)',
 		),
 		'sofortueberweisung' => array(
			'machineName' => 'Sofortueberweisung',
 			'frontendName' => 'Sofortüberweisung',
 			'backendName' => 'Open Payment Platform: Sofortüberweisung',
 		),
 		'chinaunionpay' => array(
			'machineName' => 'ChinaUnionpay',
 			'frontendName' => 'China Unionpay',
 			'backendName' => 'Open Payment Platform: China Unionpay',
 		),
 		'visa' => array(
			'machineName' => 'Visa',
 			'frontendName' => 'Visa',
 			'backendName' => 'Open Payment Platform: Visa',
 		),
 		'visaelectron' => array(
			'machineName' => 'VisaElectron',
 			'frontendName' => 'Visa Electron',
 			'backendName' => 'Open Payment Platform: Visa Electron',
 		),
 		'vpay' => array(
			'machineName' => 'Vpay',
 			'frontendName' => 'V PAY',
 			'backendName' => 'Open Payment Platform: V PAY',
 		),
 		'paydirekt' => array(
			'machineName' => 'Paydirekt',
 			'frontendName' => 'paydirekt',
 			'backendName' => 'Open Payment Platform: paydirekt',
 		),
 		'bcmc' => array(
			'machineName' => 'Bcmc',
 			'frontendName' => 'Bancontact',
 			'backendName' => 'Open Payment Platform: Bancontact',
 		),
 		'poli' => array(
			'machineName' => 'Poli',
 			'frontendName' => 'POLi',
 			'backendName' => 'Open Payment Platform: POLi',
 		),
 		'interac' => array(
			'machineName' => 'Interac',
 			'frontendName' => 'Interac',
 			'backendName' => 'Open Payment Platform: Interac',
 		),
 		'entercash' => array(
			'machineName' => 'Entercash',
 			'frontendName' => 'entercash',
 			'backendName' => 'Open Payment Platform: entercash',
 		),
 		'afterpaypaylater' => array(
			'machineName' => 'AfterPayPayLater',
 			'frontendName' => 'AfterPay Pay Later',
 			'backendName' => 'Open Payment Platform: AfterPay Pay Later',
 		),
 		'afterpaydirectdebit' => array(
			'machineName' => 'AfterPayDirectDebit',
 			'frontendName' => 'AfterPay Direct Debit',
 			'backendName' => 'Open Payment Platform: AfterPay Direct Debit',
 		),
 		'paytrail' => array(
			'machineName' => 'Paytrail',
 			'frontendName' => 'Paytrail',
 			'backendName' => 'Open Payment Platform: Paytrail',
 		),
 		'trustly' => array(
			'machineName' => 'Trustly',
 			'frontendName' => 'Trustly',
 			'backendName' => 'Open Payment Platform: Trustly',
 		),
 		'trustpay' => array(
			'machineName' => 'TrustPay',
 			'frontendName' => 'TrustPay',
 			'backendName' => 'Open Payment Platform: TrustPay',
 		),
 		'postfinancecard' => array(
			'machineName' => 'PostFinanceCard',
 			'frontendName' => 'PostFinance Card',
 			'backendName' => 'Open Payment Platform: PostFinance Card',
 		),
 		'onecard' => array(
			'machineName' => 'OneCard',
 			'frontendName' => 'onecard',
 			'backendName' => 'Open Payment Platform: onecard',
 		),
 		'przelewy24' => array(
			'machineName' => 'Przelewy24',
 			'frontendName' => 'Przelewy24',
 			'backendName' => 'Open Payment Platform: Przelewy24',
 		),
 		'yandex' => array(
			'machineName' => 'Yandex',
 			'frontendName' => 'Yandex',
 			'backendName' => 'Open Payment Platform: Yandex',
 		),
 		'tenpay' => array(
			'machineName' => 'Tenpay',
 			'frontendName' => 'Tenpay',
 			'backendName' => 'Open Payment Platform: Tenpay',
 		),
 		'alipay' => array(
			'machineName' => 'Alipay',
 			'frontendName' => 'Alipay',
 			'backendName' => 'Open Payment Platform: Alipay',
 		),
 		'daopay' => array(
			'machineName' => 'Daopay',
 			'frontendName' => 'DAOPAY',
 			'backendName' => 'Open Payment Platform: DAOPAY',
 		),
 		'cashuprepaid' => array(
			'machineName' => 'CashUPrepaid',
 			'frontendName' => 'CASHUPrepaid',
 			'backendName' => 'Open Payment Platform: CASHUPrepaid',
 		),
 		'debitmastercard' => array(
			'machineName' => 'DebitMasterCard',
 			'frontendName' => 'Debit MasterCard',
 			'backendName' => 'Open Payment Platform: Debit MasterCard',
 		),
 		'debitvisa' => array(
			'machineName' => 'DebitVisa',
 			'frontendName' => 'Debit Visa',
 			'backendName' => 'Open Payment Platform: Debit Visa',
 		),
 		'creditcard' => array(
			'machineName' => 'CreditCard',
 			'frontendName' => 'Credit Card',
 			'backendName' => 'Open Payment Platform: Credit Card',
 		),
 	);

	public function __construct(OPPCw_IPaymentMethodDefinition $defintions) {
		$this->paymentMethodDefinitions = $defintions;
		$this->settingsApi = new OPPCw_SettingApi('payment_oppcw_' . $this->paymentMethodDefinitions->getMachineName());
	}

	public static function getPaymentMethod($paymentMethodMachineName) {
		$paymentMethodMachineName = strtolower($paymentMethodMachineName);

		if (isset(self::$completePaymentMethodDefinitions[$paymentMethodMachineName])) {
			$def = self::$completePaymentMethodDefinitions[$paymentMethodMachineName];
			return new OPPCw_PaymentMethod(new OPPCw_DefaultPaymentMethodDefinition($def['machineName'], $def['backendName'], $def['frontendName']));
		}
		else {
			throw new Exception("No payment method found with name '" . $paymentMethodMachineName . "'.");
		}
	}

	/**
	 * @return OPPCw_SettingApi
	 */
	public function getSettingsApi() {
		return $this->settingsApi;
	}

	/**
	 * (non-PHPdoc)
	 * @see Customweb_Payment_Authorization_IPaymentMethod::getPaymentMethodName()
	 */
	public function getPaymentMethodName() {
		return strtolower($this->paymentMethodDefinitions->getMachineName());
	}

	public function getPaymentMethodDisplayName() {
		$title = $this->getSettingsApi()->getValue('title');
		$langId = OPPCw_Language::getCurrentLanguageId();
		if (!empty($title) && isset($title[$langId]) && !empty($title[$langId])) {
			return $title[$langId];
		}
		else {
			return $this->paymentMethodDefinitions->getFrontendName();
		}
	}

	public function getPaymentMethodConfigurationValue($key, $languageCode = null) {

		if ($languageCode === null) {
			return $this->getSettingsApi()->getValue($key);
		}
		else {
			$languageId = null;
			$languageCode = (string)$languageCode;
			foreach (OPPCw_Util::getLanguages() as $language) {
				if ($language['code'] == $languageCode) {
					$languageId = $language['language_id'];
					break;
				}
			}

			if ($languageId === null) {
				throw new Exception("Could not find language with language code '" . $languageCode . "'.");
			}

			return $this->getSettingsApi()->getValue($key, null, $languageId);
		}
	}

	public function existsPaymentMethodConfigurationValue($key, $languageCode = null) {
		return $this->getSettingsApi()->isSettingPresent($key);
	}

	public function getBackendPaymentMethodName() {
		return $this->paymentMethodDefinitions->getBackendName();
	}

	/**
	 * @param Customweb_Payment_Authorization_IOrderContext $context
	 * @return OPPCw_Adapter_IAdapter
	 */
	public function getPaymentAdapterByOrderContext(Customweb_Payment_Authorization_IOrderContext $context) {
		$paymentAdapter = OPPCw_Util::getAuthorizationAdapterFactory()->getAuthorizationAdapterByContext($context);
		return OPPCw_Util::getShopAdapterByPaymentAdapter($paymentAdapter);

	}

	/**
	 * @param OPPCw_Entity_Transaction $transaction
	 * @return OPPCw_Adapter_IAdapter
	 */
	public function getPaymentAdapterByTransaction(OPPCw_Entity_Transaction $transaction) {
		$paymentAdapter = OPPCw_Util::getAuthorizationAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationType());
		return OPPCw_Util::getShopAdapterByPaymentAdapter($paymentAdapter);
	}


	/**
	 * @return OPPCw_Entity_Transaction
	 */
	public function newTransaction(OPPCw_OrderContext $orderContext, $aliasTransactionId = null, $failedTransactionObject = null) {
		$transaction = new OPPCw_Entity_Transaction();

		$orderInfo = $orderContext->getOrderInfo();
		$transaction->setOrderId($orderInfo['order_id'])->setCustomerId($orderInfo['customer_id']);
		$transaction->setStoreId(OPPCw_Store::getStoreId());
		OPPCw_Util::getEntityManager()->persist($transaction);

		$transactionContext = new OPPCw_TransactionContext($transaction, $orderContext, $aliasTransactionId);
		$transactionObject = $this->getPaymentAdapterByOrderContext($orderContext)->getInterfaceAdapter()->createTransaction($transactionContext, $failedTransactionObject);
		
		unset($_SESSION['oppcw_checkout_id'][$orderContext->getPaymentMethod()->getPaymentMethodName()]);
		
		$transaction->setTransactionObject($transactionObject);
		OPPCw_Util::getEntityManager()->persist($transaction);

		return $transaction;
	}

	public function newOrderContext($orderInfo, $registry) {
		$order_totals = OPPCw_Util::getOrderTotals($registry);
		return new OPPCw_OrderContext(new OPPCw_PaymentMethodWrapper($this), $orderInfo, $order_totals);
	}
}