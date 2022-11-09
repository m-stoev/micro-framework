<?php

/**
 * Class Controller
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

    protected $controller_template_variables    = array(); // holds template variables
    protected $env                              = array();
    protected $files                            = array();
    protected $_default_template_variables      = array(); // default template variables
    protected $smarty_config                    = array();

    /**
     * @param (string) $action the name of the action, method
     * @param (array) $params some parameters form the url
     */
    public function __construct($action, array $params = array())
    {
        $this->controller   = get_class($this);
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
            $this->render();
        }

        // execute the destructor_filter - background process
        $this->destructor_filter();
    }

    /**
     * Delete a record from a table by AJAX request.
     * Mostly used when delete record in list view pages.
     * 
     * See delete_confirm.php template
     */
    public function delete_record()
    {
        $this->do_render = false;
        
        $table  = $this->get_var('table');
        $id     = $this->get_var('id');

        if ($this->is_ajax() && is_numeric($id)) {
            if(!$table) {
                $table = strtolower($this->controller);
            }
            
            $resp = $this->model->delete_by_id((int) $id, $table);
            
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
    protected function controller_init() {}

    /**
     * Declaration of app_init
     * 
     * We will use this function in App_Controller to execute some code needed from all controllers.
     * For example - check users login status. This will be very useful for admin sites.
     * The function runs after we create instance of the model.
     */
    protected function app_init() {}

    /**
     * Part of predefined login-logout methods.
     * Low security crypt for tokens.
     *
     * @param string $str
     * @return string
     */
    protected final function crypt_token($str)
    {
        return password_hash((string) $str, PASSWORD_DEFAULT, ['cost' => 5]);
    }

    /**
     * The function returns variables for the view, to show different success or
     * not success messages after some action - post, jQuery request and etc.
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
        $msg = '',
        array $post = array()
    ) {
        $alert = array(
            'show'      => true,
            'success'   => $was_action_successful
        );

        if (!empty($msg)) {
            $alert['msg'] = $msg;
        }

        // there is sense to show the posted data only of the post returns error an there is post data
        if (!$was_action_successful && count($post) > 0) {
            $this->controller_template_variables['post'] = $post;
        }

        $_SESSION['alert'] = $this->controller_template_variables['alert'] = $alert;
    }

    /**
     * The function get server HTTP_X_REQUESTED_WITH and check for ajax request.
     * 
     * We do not set do_render, because in some methods we only want
     * to get true or false, without change do_render variable.
     * So we need to set do_render = false, manual
     *
     * @return (bool)
     */
    protected final function is_ajax()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            and 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH']
        ) {
            return true;
        }

        return false;
    }

    /**
     * This function get all variables we need,
     * put them into the view files and display them.
     * With this we close the connection with the client.
     */
    protected final function render()
    {
        $content = '';

        // merge default and controller variables for the template
        $this->controller_template_variables = array_merge(
            $this->controller_template_variables, $this->_default_template_variables
        );
        
        extract($this->controller_template_variables);

        // define the view file
        $view_path = $this->get_view_file_path(strtolower($this->controller));

        // get view file content, do it in separate buffer, else the content
        // will be printed on wrong place
        if (is_readable($view_path)) {
            ob_start();
            require $view_path;
            $content = ob_get_clean();
        }
        
        if(!defined('VIEWS_PATH')) {
            // Close current session (if it exists)
            if (session_id()) {
                session_write_close();
            }
            
            return;
        }

        // start the main bufer
        ob_start();

        // define the controller, use custom head
        if (is_readable(VIEWS_PATH . $this->controller . DS . 'head.php')) {
            require VIEWS_PATH . $this->controller . DS . 'head.php';
        }
        // use default head
        else {
            require VIEWS_PATH . 'head.php';
        }

        // get the content file
        require VIEWS_PATH . 'content.php';

        // get the footer file, use custom footer
        if (is_readable(VIEWS_PATH . $this->controller . DS . 'footer.php')) {
            require VIEWS_PATH . $this->controller . DS . 'footer.php';
        }
        // use dafault footer
        elseif (is_readable(VIEWS_PATH . 'footer.php')) {
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
     * Add variable to the template.
     * Those variables are in $controller_tempalte_variables array.
     * 
     * @param string $key key
     * @param mixed $val value
     */
    protected final function view_assign($key, $val)
    {
        $this->controller_template_variables[$key] = $val;
    }

    /**
     * Declaration of destructor_filter()
     * We execute this method after render the page, and just before we call the destructor.
     */
    protected function destructor_filter() {}

    /**
     * Translate a word.
     * Check in $this->controller_template_variables['words'] for the word,
     * return translated word if find it, or return the word.
     * 
     * Use it in the template like this: <?= self::tr('some tex'); ?>
     * 
     * @param str $word
     * @return str
     */
    protected final function tr($word)
    {
        if (isset($this->words[$word])) {
            return $this->words[$word];
        } 
        
        return $word;
    }

    /**
     * Generate the name of the model and load it - create instance
     *
     * @param (string) $class_name - Some_Name_Controller
     */
    private function load_model($class_name)
    {
        $model_name         = '';
        $model_name_parts   = explode('_', $class_name);

        if (count($model_name_parts) > 1) {
            $last_part_of_name  = array_pop($model_name_parts);
            $model_name_len     = strlen($class_name);

            $new_last_arr_element   = Text::plural_singular($last_part_of_name, 'singular');
            $model_name             = implode('', $model_name_parts);
            $model_name             .= $new_last_arr_element;
        }
        else {
            $model_name = Text::plural_singular($class_name, 'singular');
        }

        $model_name .= 'Model';

        if (!class_exists($model_name) and is_readable(MODELS_PATH . $model_name . '.php')) {
            spl_autoload_register(array('Builder', 'load_model'));
        }

        $this->model        = new $model_name();
        $this->model->table = strtolower($class_name);
    }

    /**
     * Generate path to the view file
     * 
     * @param str $controler_lower controller with lower cases
     * @param str $ext file extension we need
     * 
     * @return str $view_path the path
     */
    private function get_view_file_path($controler_lower, $ext = 'php')
    {
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
        if (strpos($this->content_view_file, 'views') !== false
            && is_readable($this->content_view_file)
        ) {
            $view_path = $this->content_view_file;
        }
        // file name with extension
        elseif (strpos($this->content_view_file, '.' . $ext) !== false) {
            $view_path = VIEWS_PATH . $controler_lower . DS . $this->content_view_file;
        }
        // just name of the file, no extension
        elseif (defined('VIEWS_PATH') 
            && is_readable(VIEWS_PATH . $controler_lower . DS . $this->content_view_file . '.' . $ext)
        ) {
            $view_path = VIEWS_PATH . $controler_lower . DS . $this->content_view_file . '.' . $ext;
        }


        return $view_path;
    }

}
