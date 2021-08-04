<?php

/**
 * Site main file.
 * This is multi projects configuration.
 * 
 * In case of single project configuration, this file can be moved in the root,
 * next to core and classes directories.
 * You must move all other files and directories with it: app, config, logs, tmp, public, etc.
 * 
 * classes/ and core/ can not be in project directory!
 * 
 * @version 0.9.3
 * @author Miroslav Stoev
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
