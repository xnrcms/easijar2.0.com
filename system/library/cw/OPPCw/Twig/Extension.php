<?php

require_once 'Customweb/Core/Util/System.php';
require_once 'Customweb/Util/Currency.php';
require_once 'Customweb/Core/Util/Html.php';

require_once 'OPPCw/Language.php';
require_once 'OPPCw/Template.php';


class OPPCw_Twig_Extension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
        	new Twig_SimpleFunction('OPPCw_Translate', function($translate) {
        		return OPPCw_Language::_($translate);
        	}, array('needs_environment' => false)),
        	new Twig_SimpleFunction('OPPCw_IncludeCss', function($file) {
        		return OPPCw_Template::includeCSSFile($file);
        	}, array('needs_environment' => false)),
        	new Twig_SimpleFunction('OPPCw_IncludeJS', function($file) {
        		return OPPCw_Template::includeJavaScriptFile($file);
        	}, array('needs_environment' => false)),
        	new Twig_SimpleFunction('OPPCw_DefaultDateTimeFormat', function() {
        		return Customweb_Core_Util_System::getDefaultDateTimeFormat();
        	}, array('needs_environment' => false)),
        	new Twig_SimpleFunction('OPPCw_HtmlToText', function($text) {
        		return Customweb_Core_Util_Html::toText($text);
        	}, array('needs_environment' => false)),
        	new Twig_SimpleFunction('OPPCw_FormatAmount', function($amount, $currency) {
        		return Customweb_Util_Currency::formatAmount($amount, $currency);
        	}, array('needs_environment' => false)),
        	new Twig_SimpleFunction('OPPCw_DecimalPlaces', function($currency) {
        		return Customweb_Util_Currency::getDecimalPlaces($currency);
        	}, array('needs_environment' => false)),
        	//
        );
    }

    public function getName()
    {
        return 'oppcw_translate';
    }
}