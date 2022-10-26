<?php

/**
 * @author Miroslav Stoev
 * @package micro-framework
 */

trait Http {
    /**
     * The function get the user ip address
     * 
     * @return (string) $ip_address - the IP address of the user
     */
    protected function get_ip()
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
     * Get value from $_GET or $_POST arrays by its name.
     * The $_GET is with priority.
     * 
     * @param string $name key name
     * @param mixed $false_val value to return if there is no variable
     * 
     * @return mixed
     */
    protected function get_value($name, $false_val = false)
    {
        if (isset($_GET[$name])) {
            return filter_var($_GET[$name]);
        }

        if (isset($_POST[$name])) {
            return filter_var($_POST[$name]);
        }

        return $false_val;
    }
    
    protected function get_server_protocol()
    {
        return stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://';
    }
}
