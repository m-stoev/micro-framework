<?php

class Home_Controller extends App_Controller
{
    /**
     * This will be the Redirect URL
     */
    public function index()
    {
        // the views/home/index.php template is loaded
        // you can pass to it variables
        
        $this->view_assign('variable', 'Hello World!');
    }
    
    public function error404()
    {
        // here we do not use template
        $this->do_render = false;
        echo 'Page 404';
    }
    
    protected function controller_init()
    {
        // we no need model and DB
        $this->need_model = false;
    }

}
