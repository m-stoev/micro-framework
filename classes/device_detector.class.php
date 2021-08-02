<?php

/**
 * class Device_Detector
 * 
 * device_detector.class.php
 * 2016
 * Miroslav Stoev
 * micro-framework
 * 
 * The class help us get information for the user and its device.
 */
class Device_Detector {

    private static $_devices = array('iphone', 'ipad', 'android', 'silk', 'blackberry', 'touch', 'linux', 'windows');
    private static $_browsers = array('ucbrowser', 'firefox', 'chrome', 'opera', 'msie', 'edge', 'safari', 'blackberry', 'trident');

    /**
     * Function find_device
     * The function serch in user_agent for some of described devices
     * 
     * @return (string) $device
     */
    public static function find_device() {
        $user_agent = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);

        foreach (self::$_devices as $d) {
            if (strpos($user_agent, $d) !== false) {
                return $d;
            }
        }
    }

    /**
     * Function find_browser
     * The function serch in user_agent for some of described browsers
     * 
     * @return (string) $browser
     */
    public static function find_browser() {
        $user_agent = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);

        foreach (self::$_browsers as $b) {
            if (strpos($user_agent, $b) !== false) {
                return $b;
            }
        }
    }

    /**
     * Function is_bot
     * Try to determine is the visitor is bot or etc.
     * 
     * @param string $u_agent - user agent if is passed
     * @return bool
     */
    public static function is_bot($u_agent = '') {
        $robots = '/googlebot|robot|facebook|twitter|ia_archiver|spider|crawl|curl|slurp|bot|panscient\.com|\.net\sclr|elnsb50|amd64|compatible|okhttp|scraper|kickfire|Beta\s\(Windows\)|ips-agent|Qwantify|BUbiNG|OrgProbe|^$/i';

        // the ip address are hashed if stored with md5 !!!
        $robots_ips = [
            // Ukraine, Lviv
            '178.137.90.14', '46.118.118.15', '178.137.92.36', '178.137.163.110',
            '178.137.161.77', '46.118.154.190', '134.249.51.255', '37.115.113.73',
            '198.245.49.215', // Canada, Montreal
            '69.84.207.246', // USA, Bakersfield
            '5.9.17.118', // Germany, Falkenstein
            '107.178.194.15', // USA, Mountain View
        ];

        // default
        $ip_address = '';
        $server = filter_input_array(INPUT_SERVER);

        if (isset($server["REMOTE_ADDR"])) {
            $ip_address = $server["REMOTE_ADDR"];
        }
        elseif (isset($server["HTTP_X_FORWARDED_FOR"])) {
            $ip_address = $server["HTTP_X_FORWARDED_FOR"];
        }
        elseif (isset($server["HTTP_CLIENT_IP"])) {
            $ip_address = $server["HTTP_CLIENT_IP"];
        }

        if ($ip_address and in_array($ip_address, $robots_ips)) {
            return true;
        }

        if ($u_agent == '') {
            $server = filter_input_array(INPUT_SERVER);
            return preg_match($robots, $server['HTTP_USER_AGENT']);
        }

        return preg_match($robots, $u_agent);
    }
}
