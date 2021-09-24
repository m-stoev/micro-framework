<?php

/**
 * Common framework class.
 * We will use it for some common methods useful for models and controllers.
 *
 * @author Miroslav Stoev
 */
class Common
{
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
     * Get value from $_GET or $_POST arrays by its name.
     * The $_GET is with priority.
     * 
     * @param string $name key name
     * @param mixed $false_val value to return if there is no variable
     * 
     * @return mixed
     */
    public function get_value($name, $false_val = false)
    {
        if (isset($_GET[$name])) {
            return filter_var($_GET[$name]);
        }

        if (isset($_POST[$name])) {
            return filter_var($_POST[$name]);
        }

        return $false_val;
    }
}
