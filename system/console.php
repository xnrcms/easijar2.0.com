<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/startup.php';

// Registry
$registry = Registry::getSingleton();

// Config
$config = new Config();

// Load the default config
$config->load('default');
$config->load('catalog');
$registry->set('config', $config);

// Log
$log = new Log($config->get('error_filename'));
$registry->set('log', $log);

date_default_timezone_set($config->get('date_timezone'));

set_error_handler(function ($code, $message, $file, $line) use ($log, $config) {
    // error suppressed with @
    if (error_reporting() === 0) {
        return false;
    }

    switch ($code) {
        case E_NOTICE:
        case E_USER_NOTICE:
            $error = 'Notice';
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $error = 'Warning';
            break;
        case E_ERROR:
        case E_USER_ERROR:
            $error = 'Fatal Error';
            break;
        default:
            $error = 'Unknown';
            break;
    }

    if ($config->get('error_display')) {
        echo '<b>' . $error . '</b>: ' . $message . ' in <b>' . $file . '</b> on line <b>' . $line . '</b>';
    }

    if ($config->get('error_log')) {
        $log->write('PHP ' . $error . ':  ' . $message . ' in ' . $file . ' on line ' . $line);
    }

    return true;
});

// Event
$event = new Event($registry);
$registry->set('event', $event);

// Event Register
if ($config->has('action_event')) {
    foreach ($config->get('action_event') as $key => $value) {
        foreach ($value as $priority => $action) {
            $event->register($key, new Action($action), $priority);
        }
    }
}

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Request
$registry->set('request', new Request());

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$response->setCompression($config->get('config_compression'));
$registry->set('response', $response);

// Database
if ($config->get('db_autostart')) {
    $registry->set('db', new DB($config->get('db_engine'), $config->get('db_hostname'), $config->get('db_username'), $config->get('db_password'), $config->get('db_database'), $config->get('db_port')));

    $capsule = new Illuminate\Database\Capsule\Manager();
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => DB_HOSTNAME,
        'database' => DB_DATABASE,
        'username' => DB_USERNAME,
        'password' => DB_PASSWORD,
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix' => DB_PREFIX,
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
}

// Cache
$registry->set('cache', new Cache($config->get('cache_engine'), $config->get('cache_expire')));

// Url
if ($config->get('url_autostart')) {
    $registry->set('url', new Url($config->get('site_url')));
}

if (is_admin()) {
    $registry->set('front_url', new Url(HTTP_CATALOG));
}

// Language
$language = new Language($config->get('language_directory'));
$registry->set('language', $language);

// Document
$registry->set('document', new Document());

// Config Autoload
if ($config->has('config_autoload')) {
    foreach ($config->get('config_autoload') as $value) {
        $loader->config($value);
    }
}

// Language Autoload
if ($config->has('language_autoload')) {
    foreach ($config->get('language_autoload') as $value) {
        $loader->language($value);
    }
}

// Library Autoload
if ($config->has('library_autoload')) {
    foreach ($config->get('library_autoload') as $value) {
        $loader->library($value);
    }
}

// Model Autoload
if ($config->has('model_autoload')) {
    foreach ($config->get('model_autoload') as $value) {
        $loader->model($value);
    }
}

// Route
$route = new Router($registry);

// Pre Actions
if ($config->has('action_pre_action')) {
    foreach ($config->get('action_pre_action') as $value) {
        $route->addPreAction(new Action($value));
    }
}

// Set others configurations
// Store
$db = $registry->get('db');
$config->set('config_store_id', 0);
$config->set('config_url', HTTP_SERVER);
$config->set('config_ssl', HTTPS_SERVER);

// Settings
$query = $db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' OR store_id = '" . (int)$config->get('config_store_id') . "' ORDER BY store_id ASC");

foreach ($query->rows as $result) {
    if (!$result['serialized']) {
        $config->set($result['key'], $result['value']);
    } else {
        $config->set($result['key'], json_decode($result['value'], true));
    }
}

// Theme
$config->set('template_cache', $config->get('developer_theme'));

// Url
$registry->set('url', new Url($config->get('config_url')));

// Language
$code = $config->get('config_language');

// Overwrite the default language object
$language = new Language($code);
$language->load($code);

$registry->set('language', $language);

// Set the config language_id
$languages = model('localisation/language')->getLanguages();
$language_codes = array_column($languages, 'language_id', 'code');
$config->set('config_language_id', $language_codes[$code]);

// Customer
$customer = new Cart\Customer($registry);
$registry->set('customer', $customer);

// Customer Group
$config->set('config_customer_group_id', 1);

// Currency
$code = $config->get('config_currency');
$registry->set('currency', new Cart\Currency($registry));

// Tax
$tax = new Cart\Tax($registry);
$registry->set('tax', $tax);
if ($config->get('config_tax_default') == 'shipping') {
    $tax->setShippingAddress($config->get('config_country_id'), $config->get('config_zone_id'));
}

if ($config->get('config_tax_default') == 'payment') {
    $tax->setPaymentAddress($config->get('config_country_id'), $config->get('config_zone_id'));
}

$tax->setStoreAddress($config->get('config_country_id'), $config->get('config_zone_id'));

// Weight
$registry->set('weight', new Cart\Weight($registry));

// Length
$registry->set('length', new Cart\Length($registry));

// Cart
$registry->set('cart', new Cart\Cart($registry));

// Encryption
$registry->set('encryption', new Encryption());
