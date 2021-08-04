<?php

/**
 * class Device_Detector
 * 
 * The class help us get information for the user and its device.
 * 
 * @author Miroslav Stoev
 * @package micro-framework
 */
class Device_Detector
{
    private static $_devices = array('iphone', 'ipad', 'android', 'silk', 'blackberry', 'touch', 'linux', 'windows');
    private static $_browsers = array('ucbrowser', 'firefox', 'chrome', 'opera', 'msie', 'edge', 'safari', 'blackberry', 'trident');

    /**
     * The function search in user_agent for some of described devices
     * 
     * @return (string) $device
     */
    public static function find_device()
    {
        if(empty($_SERVER['HTTP_USER_AGENT'])) {
            return '';
        }
        
        foreach (self::$_devices as $d) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], $d) !== false) {
                return $d;
            }
        }
    }

    /**
     * The function serch in user_agent for some of described browsers
     * 
     * @return (string) $browser
     */
    public static function find_browser()
    {
        if(empty($_SERVER['HTTP_USER_AGENT'])) {
            return '';
        }
        
        foreach (self::$_browsers as $b) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], $b) !== false) {
                return $b;
            }
        }
    }

    /**
     * Try to determine is the visitor is bot or etc.
     * 
     * @param string $u_agent - user agent if is passed
     * @return bool
     */
    public static function is_bot($u_agent = '')
    {
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

        if (!empty($_SERVER["REMOTE_ADDR"])) {
            $ip_address = $_SERVER["REMOTE_ADDR"];
        }
        elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        elseif (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip_address = $_SERVER["HTTP_CLIENT_IP"];
        }

        if ($ip_address && in_array($ip_address, $robots_ips)) {
            return true;
        }

        if ($u_agent == '') {
            return preg_match($robots, $_SERVER['HTTP_USER_AGENT']);
        }

        return preg_match($robots, $u_agent);
    }
}
