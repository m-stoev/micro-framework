<?php

/**
 * Framework main file.
 * This file can be moved in projects/project_name/ with others project's directories like: 
 * app, config, logs, tmp and public.
 * 
 * classes/ and core/ can not be in project directory!
 * 
 * @version 0.9.1
 * @autor Miroslav Stoev
 * @package micro-framework
 */

mb_internal_encoding('UTF-8');
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__) . DS);

session_start();

require_once ROOT . 'config' . DS . 'router.php';
$url_data = Router::get_url_data();

require_once ROOT . 'config' . DS . 'config.php';
require_once CORE_PATH . 'builder.class.php';
Builder::run($url_data);
unset($url_data);
