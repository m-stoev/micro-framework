<?php

/**
 * Class Model
 * 
 * @author Miroslav Stoev
 * @package micro-framework
 */
class Model extends SQL_Query
{
    public $table = '';
	
    protected $model;

    public function __construct()
    {
        $this->connect(HOST, USER, PASS, DB);
        $this->model = get_class($this);

        if (!empty($this->table)) {
            $this->table = $this->generate_table_name_from_model($this->model);
        }

        $this->init(); // execute the model init function
    }

    public function __destruct()
    {
        $this->disconnect();
    }
    
    /**
     * The function get the user ip address
     * 
     * @param there are no parameters
     * @return (string) $ip_address - the IP address of the user
     */
    public final function get_ip()
    {
        $ip_address = '';

		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip_address = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
		}
        elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
        }

        return $ip_address;
    }

    /**
     * Generate random and unique id number
     * 
     * @param (int) $len the lenght of generated id
     * @param (bool) $special_symbols sometimes we do not want special symobls
     * 
     * @return string $code 
     */
    public function generate_id($len, $special_symbols = true)
    {
        $alphas   = range('a', 'z');
        $alphasUp = range('A', 'Z');
        $nums     = range(0, 9);
        $symbols  = array('!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '=');

        if ($special_symbols) {
            $allSymbols = array_merge($alphas, $alphasUp, $nums, $symbols);
        }
        else {
            $allSymbols = array_merge($alphas, $alphasUp, $nums);
        }

        $code = '';
        for ($cnt = 0; $cnt < $len - 1; $cnt++) {
            $code .= $allSymbols[array_rand($allSymbols)];
        }

        return $code;
    }

    /**
     * Gets paginated results by predefined query, exclude the limit.
     * Use it when we have complicated select, with joins, groups and etc.,
     * because we do not use MySQL COUNT function.
     * 
     * @param string $query
     * @param int $page_num
     * @param int $res_per_page
     * 
     * @return array
     */
    public final function get_paginated_results_by_query($query, $page_num, $res_per_page)
    {
        // keep pdo params for next query
        $pdo_params = $this->pdo_params_arr;

        // first get all results
        $this->query($query);
        $all_res = $this->query_results;

        $all_pages      = ceil($all_res / $res_per_page);
        $start_record   = $res_per_page * $page_num - $res_per_page; // the first record in limit condition
        $query          .= " LIMIT ".intval($start_record).", ".intval($res_per_page);

        $this->pdo_params_arr = $pdo_params;

        return [
            'records_cnt'   => $all_res,
            'records'       => $this->query($query, true, 'id')
        ];
    }

    /**
     * The function generate paginated results in HTML li tags.
     * 
     * @param (array) $get - the get variables
     * @param (int) $records_cnt - all results
     * @param (str) $controller - the controller name, we use this in the links
     * @param (str) $action - the action name, we use this in the link of the button
     * 
     * @return (string) $pages - string with pages
     */
    public final function create_pages(array $get, $records_cnt, $controller = '', $action = '')
    {
        $page               = 1;
        $results_per_page   = defined('RESULTS_PER_PAGE') ? RESULTS_PER_PAGE : 5;
        $pagination_text    = '';
        
        if (isset($get['page'])) {
            $page = intval($get['page']);
            unset($get['page']);
        }
        
        if (isset($get['results'])) {
            $results_per_page = intval($get['results']);
            unset($get['results']);
        }

        // count of visible pages buttons + the current
        $visible_pages_links = 11;
        $half_visible        = $visible_pages_links % 2 == 0 ?
            $visible_pages_links / 2 : ($visible_pages_links - 1) / 2;

        if ($results_per_page == 0) {
            $results_per_page = RESULTS_PER_PAGE;
        }

        // pages buttons
        $all_pages = ceil($records_cnt / $results_per_page);

        if ($all_pages > 1) {
            # create url_prefix for the links
            $url_prefix = COOKIE_PATH;
            $url_prefix .= $controller != '' ? $controller.'/' : '';
            $url_prefix .= ($action != '' and $action != 'index') ?
                str_replace('_', '-', $action).'/' : '';
            $url_prefix .= $get ? '?'.http_build_query($get).'&' : '?';
            $url_prefix .= 'results='.$results_per_page.'&page=';

            if ($page > $all_pages or $page < 1) {
                Text::create_error_log($this->get_error());
            }

            $start = 1;

            if ($page <= $half_visible) {
                $start = $start;
            }
            elseif ($page > $half_visible and $page < ($all_pages - $half_visible)) {
                $start = $page - $half_visible;
            }
            elseif ($page > $half_visible and $page >= ($all_pages - $half_visible)) {
                $start = $all_pages - $visible_pages_links + 1;
            }
            
            if($start < 1) {
                $start = 1;
            }
            
            if ($start > 1) {
                $pagination_text .= '<li class="'.($page == 1 ?
                    'disabled' : '').'"><a href="'.$url_prefix.'1">First</a></li>';
            }

            // left arrow
            if($page == 1) {
                $pagination_text .= '<li class="disabled"><a href="javascript: void(0)">&laquo;</a></li>';
            }
            else {
                $pagination_text .= '<li><a href="'.$url_prefix.($page - 1).'">&laquo;</a></li>';
            }

            // calibrate when to show "..." on left of current page
            if ($page - $half_visible > 1 and $all_pages > $visible_pages_links) {
                $pagination_text .= '<li class=""><a href="javascript: void(0)">...</a></li>';
            }

            $rem_pages = $visible_pages_links;
            
            // the pages before current page and current page
            for ($cnt = $start; $rem_pages > 0 and $cnt <= $all_pages and $cnt > 0; $cnt++) {
                if($page == $cnt) {
                    $pagination_text .= '<li class="active"><a href="javascript: void(0)">'.$cnt.'</a></li>';
                }
                else {
                    $pagination_text .= '<li><a href="'.$url_prefix.$cnt.'">'.$cnt.'</a></li>';
                }

                $rem_pages--;
            }
            
            // calibrate when to show ... on right of current page
            if ($all_pages - $page > $half_visible and $all_pages > $visible_pages_links) {
                $pagination_text .= '<li class=""><a href="javascript: void(0)">...</a></li>';

                // right arrow
                if($page == $all_pages) {
                    $pagination_text .=
                    '<li class="disabled"><a href="javascript: void(0)">&raquo;</a></li>'
                    .'<li class="disabled"><a href="javascript: void(0)">Last</a></li>';
                }
                else {
                    $pagination_text .=
                    '<li><a href="'.$url_prefix.($page + 1).'">&raquo;</a></li>'
                    .'<li><a href="'.$url_prefix.$all_pages.'">Last</a></li>';
                }
            }
            else {
                // right arrow
                if($page == $all_pages) {
                    $pagination_text .= '<li class="disabled"><a href="javascript: void(0)">&raquo;</a></li>';
                }
                else {
                    $pagination_text .= '<li><a href="'.$url_prefix.($page + 1).'">&raquo;</a></li>';
                }
            }
        }

        return $pagination_text;
    }

    /**
     * Get information for the visitor's place by its IP
     * we uese web service http://ip-api.com/json/
     * 
     * @param string $ip - optional
     * @return 
     */
    public function get_visitor_data_by_ip($ip = '')
    {
        $visitor_data = false;
        $ip           = !$ip ? $this->get_ip() : $ip;

        $visitor_data = file_get_contents('http://ip-api.com/json/'.$ip);
        if ($visitor_data) {
            $visitor_data = json_decode($visitor_data);

            if ($visitor_data->status == 'fail') {
                return false;
            }
        }

        return $visitor_data;
    }
    
    /**
     * Generate table name from the name of the model
     * 
     * @param (string) $model_name
     * @return (string) $name
     */
    protected function generate_table_name_from_model($model_name)
    {
        $name = str_replace('Model', '', $model_name);
        // if we have name with two parts example: StaticPage, we explode string by capital letters
        $name_parts = preg_split('/(?=[A-Z])/', $name);
        // remove first element it is always empty
        array_shift($name_parts);

        $name = strtolower(implode('_', $name_parts));
        $name = Text::plural_singular($name, 'plural');

        return $name;
    }

    /**
     * The function use recomended from php.net way to encript the passwords. It is part of PHP.
     * 
     * @param (string) $pass
     * @return encripted passowrd
     */
    protected function encript_pass($pass)
    {
        return password_hash((string) $pass, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    /**
     * Declaration init
     * We will use this function in the model instead of constructor
     */
    protected function init() {}
    
}