<?php

class Home_Controller extends App_Controller
{
    /**
     * This will be the Redirect URL
     */
    public function index()
    {
        echo 'Home page';
    }
    
    public function error404()
    {
        echo 'Page 404';
    }
    
    protected function controller_init()
    {
        $this->need_model = false;
    }

}
