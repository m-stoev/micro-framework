<?php
/**
 * Set all your important constants here.
 */

date_default_timezone_set('Europe/Sofia');

if (is_dir(ROOT . 'core')) {
    define('FW_ROOT', ROOT);
}
else {
    define('FW_ROOT', dirname(dirname(dirname(dirname(__FILE__)))).DS);
}

define('CORE_PATH', FW_ROOT . 'core' . DS);
define('CLASSES_PATH', FW_ROOT . 'classes' . DS);

if (isset($url_data['params']['admin']) && $url_data['params']['admin']) {
    define('IS_ADMIN', 'admin' . DS);
}
else {
    define('IS_ADMIN', '');
}

define('CONTROLLERS_PATH',  ROOT . 'app' . DS . IS_ADMIN . 'controllers' . DS);
define('MODELS_PATH',       ROOT . 'app' . DS . 'models' . DS); // optional
define('VIEWS_PATH',        ROOT . 'app' . DS . IS_ADMIN . 'views' . DS);  // optional, if no need it set do_render = false
define('LOGS_PATH',         ROOT . 'logs' . DS); // optional
define('JS_PATH',           ROOT . 'public' . DS . IS_ADMIN . 'js' . DS);
define('COOKIE_PATH',       IS_ADMIN === '' ? '/' : '/backend/');
define('SRC_HREF_PATH',     '/public/admin/'); // for admin only

define('ADMIN_EMAIL',       ''); // optional
define('DEFAULT_LANG',      'bg'); // optional

# The constans below can be different for the different servers
if(empty($_SERVER['SERVER_NAME'])) {
    die('Server Name is empty!');
}

define('HOST',          'localhost');
define('SERVER_NAME',   filter_var($_SERVER['SERVER_NAME'], FILTER_SANITIZE_URL, FILTER_NULL_ON_FAILURE));
define('WEB_ROOT',      'https://' . SERVER_NAME . COOKIE_PATH);
define('SALT',          'some sALt'); // salt for crypt token

// possible field names holding passwords, because before php 5.6
// we do not have const array we will use string, and then will explode it by ","
define('PASS_FIELDS_NAMES', 'pass,password');
define('RESULTS_PER_PAGE',  2);

define('TRUSTED_IPS',   json_encode([])); // trusted ips, on them we will show errors
define('AUTOLOGIN_IPS', json_encode([])); // auto login IPs

# DB configurations
switch (SERVER_NAME) {
    case 'wix-nuvei-app.hostmi.eu':
        // for admin only
        define('MYSQL_DUMP_HOST', '');
        define('DB', '');
        define('USER', '');
        define('PASS', '');
        define('DEBUG_MODE', false);
        break;

    case 'wix-nuvei-app.hostmi.dev':
    case 'micro-framework':
        // for admin only
        define('MYSQL_DUMP_HOST', 'localhost');
        define('DB', '');
        define('USER', '');
        define('PASS', '');
        define('DEBUG_MODE', true);
        break;

    default:
        die('Wrong configs for this domain. Please check config file!');
}

if(empty($_SERVER['REMOTE_ADDR'])) {
    error_reporting(DEBUG_MODE ? E_ALL : 0);
}
elseif(defined('TRUSTED_IPS')) {
    error_reporting(
        (DEBUG_MODE or in_array(
            filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE),
            json_decode(TRUSTED_IPS)
        ) ? E_ALL : 0)
    );
}

# if we are in admin site we set lifetime to 30 min, else it has no limit
session_set_cookie_params((IS_ADMIN != '' ? 60 * 30 : 0), COOKIE_PATH, SERVER_NAME, false, true);
