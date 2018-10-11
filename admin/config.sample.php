<?php
// HTTP
define('HTTP_CATALOG', 'http://chint/');
define('HTTP_SERVER', HTTP_CATALOG . 'admin/');

// HTTPS
define('HTTPS_CATALOG', HTTP_CATALOG);
define('HTTPS_SERVER', HTTP_SERVER);

// DIR
define('DIR_OCROOT', str_replace('\\', '/', realpath(dirname(dirname(__FILE__)))) . '/');
define('DIR_APPLICATION', DIR_OCROOT . 'admin/');
define('DIR_SYSTEM', DIR_OCROOT . 'system/');
define('DIR_IMAGE', DIR_OCROOT . 'image/');
define('DIR_STORAGE', DIR_SYSTEM . 'storage/');
define('DIR_CATALOG', DIR_OCROOT . 'catalog/');

define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', '192.168.0.112');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'admin123my');
define('DB_DATABASE', 'project_chint');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');

// OpenCart API
define('OPENCART_SERVER', 'https://www.opencart.com/');

// Others
define('DEBUG', false);
