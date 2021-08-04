<?php

/**
 * Describe your routes here.
 */

class Router
{
    /**
     * Get data from url or from passed string and return
     * controller, action and other parameters
     * 
     * @param string $target
     * @return array $url_data- optional
     */
    public static function get_url_data($target = '')
    {
        $url_data['params']['admin']    = false;
        $data                           = [];
        
        if(!empty($target)) {
            parse_str($target, $data);
        }
        else {
            $get_url = mb_strtolower(filter_input(INPUT_GET, 'url'));
        }
        
        $data = explode('/', $get_url);
        
        //var_dump($data);

        // check for language in the URL
        if (strlen($data[0]) == 2) {
            $url_data['params']['lang'] = $data[0];
            array_shift($data); // remove the language from $data array
        }
        
        # The Routing
        // home
        if (!isset($data[0]) || $data[0] == '') {
            $url_data['controller'] = 'home';
        }
        // custom route
        elseif ($data[0] != '') {
            $url_data['controller'] = 'home';
            $url_data['action']     = $data[0];
        }
        // route of type /controller/action/
//        elseif (isset($data[0])) {
//            $url_data['controller'] = $data[0];
//            $url_data['action']     = 'index';
//
//            if (isset($data[1]) and $data[1] != '') {
//                $url_data['action'] = $data[1];
//            }
//        }
        else {
            $url_data['controller']     = 'home';
            $url_data['params']['page'] = 'error404';
        }
        
//        var_dump($url_data);

        return $url_data;
    }
}
