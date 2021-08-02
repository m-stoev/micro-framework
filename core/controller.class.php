<?php

/**
 * Class Controller
 * 
 * controller.class.php
 * 
 * @author Miroslav Stoev
 * @package micro-framework
 */
class Controller
{
    protected $controller   = '';
    protected $model        = '';
    protected $action       = '';
    protected $params       = array();
    protected $do_render    = true; // do we want to render a view
    protected $need_model   = true; // do we want to use a model

    /**
     * Set manually view file.
     * Use $view_file instead
     * 
     * @deprecated
     * @var string
     */
    protected $content_view_file = '';
    
    /**
     * holds variables for the template, use this for direct access 
     * to set them use view_assign() method
     * 
     * @var array
     */
    protected $controller_template_variables = [];

    /**
     * The file with translations - they are in an associative array
     * Instead of using this array in templates use:
     * self::tr('string')
     * 
     * @deprecated soon
     * @var array 
     */
    protected $words = array();

    # safe access to global variables
    /**
     * @var array
     * @deprecated
     */
    protected $post = array();

    /**
     * @var array
     * @deprecated
     */
    protected $get = array();

    /**
     * @var array
     * @deprecated
     */
    protected $request = array();

    /**
     * @var array
     * @deprecated
     */
    protected $cookie = array();

    /**
     * @var array
     * @deprecated
     */
    protected $session = array();

    /**
     * @deprecated
     * @var array
     */
    protected $server                       = array();
    protected $env                          = array();
    protected $files                        = array();
    // we put the default template variables here
    protected $_default_template_variables  = array();
    protected $smarty_config                = array();

    /**
     * @param (string) $action - the name of the action, method
     * @param (array) $params - some parameters form the url
     */
    public function __construct($action, array $params = array()) {
        /* TODO deprecated */
        $this->sanitize_globals();

        $contr_name_parts = explode('_', get_class($this));
        unset($contr_name_parts[count($contr_name_parts) - 1]);

        $this->controller   = implode('_', $contr_name_parts);
        $this->action       = $action;
        $this->params       = $params;

        $this->_default_template_variables['controller']    = strtolower($this->controller);
        $this->_default_template_variables['action']        = $action;
        $this->_default_template_variables['params']        = $params;

        $this->controller_init();

        // it is possible to not need a model
        if ($this->need_model) {
            $this->load_model($this->controller);
        }

        // execute the application init
        $this->app_init();
    }

    public function __destruct() {
        if ($this->do_render) {
            if (class_exists('Smarty')) {
                $this->render_smarty();
            } else {
                $this->render();
            }
        }

        // execute the destructor_filter - background process
        $this->destructor_filter();
    }

    /**
     * Function delete_record
     * 
     * Simple function to delete a record from a table,
     * getting the id and optionally the table from ajax request.
     * Mostly used when delete record in list view pages.
     * 
     * See delete_confirm.php template
     */
    public function delete_record() {
        $this->do_render = false;

        if (
            $this->is_ajax()
            && isset($this->post['table'], $this->post['id'])
            && !empty($this->post['id'])
            && is_numeric($this->post['id'])
        ) {
            $table  = !empty($this->post['table']) ? $this->post['table'] : strtolower($this->controller);
            $resp   = $this->model->delete_by_id(intval($this->post['id']), $table);
            
            if (!$resp) {
                echo json_encode([
                    'status' => 0,
                    'msg' => 'Problem when try to delete a record'
                ]);
                exit;
            }

            echo json_encode(['status' => 1]);
        }

        exit;
    }

    /**
     * Declaration of controller_init()
     * 
     * We call this method in the constructor, after we set controller, action and parameters
     * and just before calling the model.
     * Use this function in the controllers instead constructor.
     */
    protected function controller_init() {
        
    }

    /**
     * Declaration of app_init
     * 
     * We will use this function in App_Controller to execute some code needed from all controllers.
     * For example - check users login status. This will be very useful for admin sites.
     * The function runs after we create instance of the model.
     */
    protected function app_init() {
        
    }

    /**
     * Function crypt_token
     * 
     * Part of predefined login-logout methods.
     * Low security crypt for tokens.
     *
     * @param string $str
     * @return string
     */
    protected final function crypt_token($str) {
        return password_hash(
            (string) $str,
            PASSWORD_DEFAULT,
            ['cost' => 5]
        );
    }

    /**
     * Function return_action_notifications
     * 
     * The function returns variables for the view, to show different success or
     * not success messages after some action - post, jquery request and etc.
     * We show the posted data only if the action returns error and there
     * is post data. If the action was successful we have to redirect to
     * edit or some other place.
     * 
     * @param (bool) $was_action_successful
     * @param (string) $msg - message if we have message
     * @param (array) $post - the posted data, we need it if $was_action_successful is false, to keep user added info
     */
    protected final function return_action_notifications(
        $was_action_successful,
        $msg = '', array $post = array()
    ) {
        $alert = array(
            'show'      => true,
            'success'   => $was_action_successful
        );

        if (!empty($msg)) {
            $alert['msg'] = $msg;
        }

        // there is sense to show the posted data only of the post returns error an there is post data
        if (!$was_action_successful and count($post) > 0) {
            $this->controller_template_variables['post'] = $post;
        }

        $_SESSION['alert'] = $this->controller_template_variables['alert'] = $alert;
    }

    /**
     * Function is_ajax()
     * The function get server HTTP_X_REQUESTED_WITH and check for ajax request.
     * 
     * We do not set do_render, because in some methods we only want
     * to get true or false, without change do_render variable.
     * So we need to set do_render = false, manual
     *
     * @return (bool)
     */
    protected final function is_ajax() {
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            and 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH']
        ) {
            return true;
        }

        return false;
    }

    /**
     * Function render
     * This function get all variables we need,
     * put them into the view files and display them.
     * With this we close the connection with the client.
     */
    protected final function render() {
        $content = '';

        // merge default and controller variables for the template
        $this->controller_template_variables = array_merge(
            $this->controller_template_variables, $this->_default_template_variables
        );
        extract($this->controller_template_variables);

        // Some_Name_Controller
        $controller_for_path = strtolower($this->controller);

        # define the view file
        $view_path = $this->get_view_file_path($controller_for_path);

        // get view file content, do it in separate buffer, else the content
        // will be printed on wrong place
        if (is_readable($view_path)) {
            ob_start();
            require $view_path;
            $content = ob_get_clean();
        }

        // start the main bufer
        ob_start();

        // define the controller, use custom head
        if (is_readable(VIEWS_PATH . $controller_for_path . DS . 'head.php')) {
            require VIEWS_PATH . $controller_for_path . DS . 'head.php';
        }
        // use default head
        else {
            require VIEWS_PATH . 'head.php';
        }

        // get the content file
        require VIEWS_PATH . 'content.php';

        // get the footer file, use custom footer
        if (is_readable(VIEWS_PATH . $controller_for_path . DS . 'footer.php')) {
            require VIEWS_PATH . $controller_for_path . DS . 'footer.php';
        }
        // use dafault footer
        else {
            require VIEWS_PATH . 'footer.php';
        }

        // Set the content length of the response.
        header("Content-Length: " . ob_get_length());
        // Disable compression (in case content length is compressed).
        header('Content-type: text/html; charset=utf-8');
        // Close the connection.
        header("Connection: close");

        // Flush all output.
        ob_end_flush();
        flush();

        // Close current session (if it exists)
        if (session_id()) {
            session_write_close();
        }
    }

    /**
     * Function render_smarty
     * Use smarty instead common render.
     * Prepare templates for it.
     * 
     * IN TEST MODE
     * 
     */
    protected function render_smarty() {
        $this->controller_template_variables = array_merge(
            $this->controller_template_variables, $this->_default_template_variables
        );

        // Some_Name_Controller
        $controller_for_path = strtolower($this->controller);

        # define the view file
        $view_path = $this->get_view_file_path($controller_for_path, 'tpl');

        $this->smarty_config = array(
            'compile_dir' => ROOT . 'tmp' . DS . 'smarty' . DS . 'tpl_compile',
            'cache_dir' => ROOT . 'tmp' . DS . 'smarty' . DS . 'cache',
        );

        $smarty = new Smarty();

        $smarty->template_dir = VIEWS_PATH;
        $smarty->compile_dir = $this->smarty_config['compile_dir'];
        $smarty->cache_dir = $this->smarty_config['cache_dir'];

        foreach ($this->controller_template_variables as $name => $val) {
            $smarty->assign($name, $val);
        }

        // get user constants
        $consts = get_defined_constants(true);
        $user_consts = $consts['user'];

        foreach ($user_consts as $name => $val) {
            $smarty->assign($name, $val);
        }

        $smarty->display($view_path);
    }

    /**
     * Function html_options
     * Generate simple html options from db results.
     * The function generates only options elements, you have to
     * put the results in select tag!!!
     * 
     * @param array $results - array with results
     * @param string $val_key - the array key for option value
     * @param array $text_keys - the array keys to generete option text, concatenate with empty spaces
     * @param array $selected - [results_key, needed_value] - put 'selected' on an option with this value
     * @param array $classes - if need some classes for each option element
     * @param bool $class_form_results - get the class form results value
     *      need $class to be set, it will be results key
     * 
     * @return string $html - html result
     */
    protected function html_options(
        $results, $val_key, array $text_keys, array $selected = [],
        array $classes = [], $class_form_results = false
    ) {
        $html = '';

        if ($results) {
            foreach ($results as $data) {
                $text = '';
                $selected_flag = '';

                if ($selected) {
                    if ($data[$selected[0]] == $selected[1]) {
                        $selected_flag = 'selected=""';
                    }
                }

                // add classes
                $html_class = '';

                if ($classes) {
                    foreach ($classes as $cl) {
                        if ($class_form_results and isset($data[$cl])) {
                            $html_class .= str_replace(' ', '_', $data[$cl]) . ' ';
                        } else {
                            $html_class .= $cl . ' ';
                        }
                    }
                }

                $html .= '<option class="' . $html_class . '" value="' . $data[$val_key] . '" ' . $selected_flag . '>';

                foreach ($text_keys as $k) {
                    $text .= $data[$k] . ' ';
                }

                $html .= trim($text) . '</option>';
            }
        }

        return $html;
    }

    /**
     * Function convert_units
     *
     * @param int $size
     * @return string
     */
    protected final function convert_units($size) {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * Function view_assign
     * Shorter way, with better look for adding variables in
     * $controller_tempalte_variables array/
     * 
     * @param string $key - key
     * @param mixed $val - value
     */
    protected final function view_assign($key, $val) {
        $this->controller_template_variables[$key] = $val;
    }

    /**
     * Declaration of destructor_filter()
     * We execute this method after render the page, and just before we call the destructor.
     */
    protected function destructor_filter() {
        
    }

    /**
     * Function get_var
     * Get a variable from $_GET or $_POST arrays by its name.
     * The $_GET is with priority.
     * 
     * @param string $name - key name
     * @param mixed $false_val - value to return if there is no variable
     * 
     * @return mixed
     */
    protected function get_var($name, $false_val = false) {
        if (!empty($_GET[$name])) {
            return filter_input(INPUT_GET, $name);
        }

        if (!empty($_POST[$name])) {
            return filter_input(INPUT_POST, $name);
        }

        return $false_val;
    }

    /**
     * Translate a word. Check in $this->controller_template_variables['words']
     * for the word, return translated word if find it, or return the word.
     * 
     * Use it in the template like this: <?= self::tr('the word'); ?>
     * 
     * @param str $word
     * @return str
     */
    protected final function tr($word) {
        if (isset($this->words[$word])) {
            return $this->words[$word];
        } else {
            return $word;
        }
    }

    /**
     * Function sanitize_globals()
     * Get globals by safe way, put them in variables and remove globals.
     * Some of the globals, like post and get are pass to the template.
     * 
     * @deprecated
     */
    private function sanitize_globals() {
        $this->session = filter_var_array(isset($_SESSION) ? $_SESSION : array());
        if ($this->session) {
            $this->_default_template_variables['session'] = $this->session;
        }

        $this->post = filter_input_array(INPUT_POST);
        if ($this->post) {
            $this->_default_template_variables['post'] = $this->post;
        }

        $this->get = filter_input_array(INPUT_GET);
        if ($this->get) {
            if (isset($this->get['url'])) {
                unset($this->get['url']);
            }

            $this->_default_template_variables['get'] = $this->get;
        }

        $this->cookie = filter_input_array(INPUT_COOKIE);
        if ($this->cookie) {
            $this->_default_template_variables['cookie'] = $this->cookie;
        }

        $this->request = filter_var_array(isset($_REQUEST) ? $_REQUEST : array());

        $this->server = filter_var($_SERVER, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        if ($this->server) {
            $this->_default_template_variables['server'] = $this->server;
        }

        $this->env = filter_input_array(INPUT_ENV);

        $this->files = filter_var_array(isset($_FILES) ? $_FILES : []);
    }

    /**
     * Function load_model
     * Generate the name of the model and load it - create instance
     *
     * @param (string) $class_name - Some_Name_Controller
     */
    private function load_model($class_name) {
        $model_name = '';
        $model_name_parts = explode('_', $class_name);

        if (count($model_name_parts) > 1) {
            $last_part_of_name = array_pop($model_name_parts);
            $model_name_len = strlen($class_name);

            $new_last_arr_element = Text::plural_singular($last_part_of_name, 'singular');
            $model_name = implode('', $model_name_parts);
            $model_name .= $new_last_arr_element;
        } else {
            $model_name = Text::plural_singular($class_name, 'singular');
        }

        $model_name .= 'Model';

        if (!class_exists($model_name) and is_readable(MODELS_PATH . $model_name . '.php')) {
            spl_autoload_register(array('Builder', 'load_model'));
        }

        $this->model = new $model_name();
        $this->model->table = strtolower($class_name);
    }

    /**
     * Function get_view_file_path
     * Generate path to the view file
     * 
     * @param str $controler_lower - controler with lower cases
     * @param str $ext - file extension we need
     * 
     * @return str $view_path - the path
     */
    private function get_view_file_path($controler_lower, $ext = 'php') {
        $view_path = '';

        if (isset($this->view_file)) {
            $this->content_view_file = $this->view_file;
        }

        // use action for file name
        if (empty($this->content_view_file)) {
            $this->content_view_file = $this->action;
        }

        // view file can be name of php file or path to php file.
        // check for path
        if (
            strpos($this->content_view_file, 'views') !== false
            and is_readable($this->content_view_file)
        ) {
            $view_path = $this->content_view_file;
        }
        // file name with extension
        elseif (strpos($this->content_view_file, '.' . $ext) !== false) {
            $view_path = VIEWS_PATH . $controler_lower . DS . $this->content_view_file;
        }
        // just name of the file, no extension
        elseif (is_readable(VIEWS_PATH . $controler_lower . DS . $this->content_view_file . '.' . $ext)) {
            $view_path = VIEWS_PATH . $controler_lower . DS . $this->content_view_file . '.' . $ext;
        }


        return $view_path;
    }

}
