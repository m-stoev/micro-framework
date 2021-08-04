<?php

/**
 * Class Builder
 * 
 * Build the app.
 * 
 * @author Miroslav Stoev
 * @package micro-framework
 */
class Builder
{
    /**
     * The function build main classes and start the site
     *
     * @param array $url_data the data from the router
     * @return void
     */
    public static function run(array $url_data)
    {
        // clean custom debug log
        $_SESSION['debug_log']  = '';
        $class                  = '';
        $class_name             = '';

        // generate class name if we have name from 2 words
        if (isset($url_data['controller'])) {
            // Some-name
            if (strpos($url_data['controller'], '-') > -1) {
                $class_arr = explode('-', $url_data['controller']);
            }
            // Some_name
            elseif (strpos($url_data['controller'], '_') > -1) {
                $class_arr = explode('_', $url_data['controller']);
            }
            // SomeName
            else {
                $class_arr[] = $url_data['controller'];
            }

            foreach ($class_arr as $key => $part) {
                $class_arr[$key] = ucfirst($part);
            }

            $class_name = implode('_', $class_arr);
            $class      = $class_name . '_Controller';
        }

        $action = isset($url_data['action']) ? str_replace("-", "_", $url_data['action']) : 'index'; // check for action
        $params = isset($url_data['params']) ? $url_data['params'] : []; // check for params

        spl_autoload_register(array('Builder', 'auto_load'));

        if (class_exists($class) && method_exists($class, $action)) {
            $controller = new $class($action, $params); // create instance
            $controller->{$action}(); // call action (method)
            
            return;
        }
        
        if (DEBUG_MODE) {
            die(
                "1. File <strong>'" . $url_data['controller']
                . ".php'</strong> containing class <strong>'$class'</strong> might be missing.<br/>"
                . "2. Method <strong>'$action'</strong> is missing in <strong>'"
                . $url_data['controller'] . ".php'</strong>"
            );
        }
            
        $text = "\n\n" . date("Y-m-d H:i:s") . "\n" .
            "1. File <strong>'" . mb_convert_encoding($url_data['controller'], 'UTF-8') .
            ".php'</strong> containing class <strong>'" . mb_convert_encoding($class, 'UTF-8') .
            "'</strong> might be missing.\n" .
            "2. Method <strong>'" . mb_convert_encoding($action, 'UTF-8') .
            "'</strong> is missing in <strong>'" . mb_convert_encoding($url_data['controller'], 'UTF-8') . ".php'</strong>\n" .
            "3. Address: " . mb_convert_encoding(urldecode($_SERVER['REQUEST_URI']), 'UTF-8') . "\n" .
            "4. Redirect to: /error404/";

        file_put_contents(
            ROOT . 'logs' . DS . date("Y-m-d") . '-errors.txt', 
            "\xEF\xBB\xBF" . $text, 
            FILE_APPEND
        );

        self::error_redirect();
    }

    /**
     * Auto load files.
     * 
     * @param string $class_name
     * @return void
     */
    public static function auto_load($class_name)
    {
        $name = str_replace('_Controller', '', $class_name);
        $name = str_replace('_Model', '', $name);

        if(empty($class_name)) {
            die('Builder: Class name is empty!');
        }
        
        if(class_exists($class_name)) {
            return;
        }
        
        if (is_readable(CORE_PATH . mb_strtolower($name) . '.class.php')) {
            require_once CORE_PATH . mb_strtolower($name) . '.class.php';
            return;
        }
        
        if (is_readable(CONTROLLERS_PATH . mb_strtolower($name) . '.php')) {
            require_once CONTROLLERS_PATH . mb_strtolower($name) . '.php';
            return;
        }
        
        if (defined('MODELS_PATH') && is_readable(MODELS_PATH . $class_name . '.php')) {
            require_once MODELS_PATH . $class_name . '.php';
            return;
        }
        
        if (is_readable(ROOT . 'config' . DS . 'autoloader.php')) {
            $autoloaded_classes = [];
            require ROOT . 'config' . DS . 'autoloader.php';

            if (isset($autoloaded_classes)
                && array_key_exists($class_name, $autoloaded_classes)
            ) {
                if (is_readable($autoloaded_classes[$class_name]['path'])) {
                    require_once $autoloaded_classes[$class_name]['path'];
                }
                elseif ($autoloaded_classes[$class_name]['download'] != '') {
                    try {
                        echo shell_exec("git clone " . $autoloaded_classes[$class_name]['download'] . ' '
                            . CLASSES_PATH . $class_name);

                        require_once $autoloaded_classes[$class_name]['path'];
                    }
                    catch (Exception $ex) {
                        self::on_error('<pre>' . print_r($ex, true) . '</pre>', false);
                    }
                }
            }
            
            return;
        }
        
        self::on_error(
            "\n" . date("Y-m-d H:i:s") . "\n" . 'autoloader did not find: ' . $name . '.class.php',
            true
        );
    }

    /**
     * Print message on error
     * 
     * @param string $text
     * @param bool $go_to_404 - go to page 404 or die
     */
    private static function on_error($text, $go_to_404)
    {
        if (DEBUG_MODE && !$go_to_404) {
            die($text);
        }
        
        file_put_contents(
            ROOT . 'tmp' . DS . 'logs' . DS . date("Y-m-d") . '-errors.txt',
            "\xEF\xBB\xBF" . mb_convert_encoding($text, 'UTF-8'),
            FILE_APPEND
        );

        if (defined('ADMIN_EMAIL') and ADMIN_EMAIL) {
            mail(ADMIN_EMAIL, 'Site error', $text);
        }

        self::error_redirect();
    }
    
    /**
     * On errors in the Builder try to call Error404 or Home class
     * 
     * @return void
     */
    private static function error_redirect()
    {
        // error404_class
        if (class_exists('Error404_Controller') && method_exists('Error404_Controller', 'index')) {
            $controller = new Error404_Controller('index', []); // create instance
            $controller->index(); // call action (method)
        }
        // home
        else {
            $controller = new Home_Controller('index', []); // create instance
            $controller->index(); // call action (method)
        }
    }

}
