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
require_once 'Customweb/Mvc/Template/Renderer.php';


/**
 * @author Nico Eigenmann
 */
class OPPCw_Twig_Renderer extends Customweb_Mvc_Template_Renderer
{
	
	/**
	 * @param Customweb_Asset_IResolver $assetResolver
	 * @param Customweb_DependencyInjection_IContainer $container
	 */
	public function __construct(Customweb_Asset_IResolver $assetResolver, Customweb_DependencyInjection_IContainer $container)
	{
		//Include the twig autoloader, so all twig classes are available.
		include_once(DIR_SYSTEM . 'library/template/Twig/Autoloader.php');
		Twig_Autoloader::register();
		parent::__construct($assetResolver, $container);
		require_once 'OPPCw/Twig/Environment.php';
require_once 'OPPCw/Twig/SecurityPolicy.php';

	}
	
	
	public function render(Customweb_Mvc_Template_IRenderContext $context)
	{		
		$loader = new Twig_Loader_Array(array($context->getTemplate() => $this->getAssetResolver()->resolveAssetStream($context->getTemplate() . '.twig')->read()));		
		$twig = new OPPCw_Twig_Environment($loader, $this->getCacheBackend());
		$policy = new OPPCw_Twig_SecurityPolicy($context->getSecurityPolicy());
		$sandbox = new Twig_Extension_Sandbox($policy, true);
		$twig->addExtension($sandbox);
		
		return $twig->render($context->getTemplate(), $context->getVariables());
	}
}