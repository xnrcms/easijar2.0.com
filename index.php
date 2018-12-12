<?php
$origin 		= isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';  
$allow_origin 	= [
	'http://seller.easijar.com',
	'https://h5.easijar.com'
];  
  
if(in_array($origin, $allow_origin)){  
    header('Access-Control-Allow-Origin:'.$origin);       
}

// Version GD-OC-PRO-20180904A
define('VERSION', '3.2.0.0');
define('OCTYPE', 'PRO');

// Configuration
if (is_file('config.php')) {
  require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
  header('Location: install/index.php');
  exit;
}

// VirtualQMOD
require_once('./vqmod/vqmod.php');
VQMod::bootup();

// VQMODDED Startup
require_once(VQMod::modCheck(DIR_SYSTEM . 'startup.php'));

start('catalog');
