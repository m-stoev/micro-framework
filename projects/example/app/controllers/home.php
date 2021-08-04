<?php

class Home_Controller extends App_Controller
{
    public function connect()
    {
        Text::create_log(@$_REQUEST, 'connect');
        echo 'Connect Page';
    }
    
    public function refund()
    {
        Text::create_log(@$_REQUEST, 'refund');
        echo 'Refund Page';
    }
    
    public function sale()
    {
        Text::create_log(@$_REQUEST, 'sale');
        echo 'Sale Page';
    }
    
    
    
    /**
     * This will be the Redirect URL
     */
    public function index()
    {
        Text::create_log(@$_REQUEST, 'index()');
        echo 'Redirect URL';
    }
    
    /**
     * This will be the App URL
     */
    public function app_url()
    {
        Text::create_log(@$_REQUEST, 'app_url()');
        echo 'App URL';
        Text::debug($_REQUEST, false);
    }
    
    /**
     * This will be the Transaction Updated callback URL
     */
    public function tr_updated()
    {
        Text::create_log(@$_REQUEST, 'tr_updated()');
        echo 'Transaction Updated';
    }
    
    /**
     * This will be the Transaction Created callback URL
     */
    public function tr_created()
    {
        Text::create_log(@$_REQUEST, 'tr_created()');
        echo 'Transaction Created';
    }
    
    /**
     * This will be the Dashboard Page callback URL
     */
    public function dashboard_page()
    {
        Text::create_log(@$_REQUEST, 'dashboard_page');
        echo 'Dashboard Page';
    }
    
    protected function controller_init()
    {
        $this->need_model = false;
    }

}
