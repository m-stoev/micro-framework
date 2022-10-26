<?php

/**
 * The class help us get information for the user and its device.
 * 
 * @author Miroslav Stoev
 * @package micro-framework
 */
trait DeviceDetector
{
    private $devices = array('iphone', 'ipad', 'android', 'silk', 'blackberry', 'touch', 'linux', 'windows');
    private $browsers = array('ucbrowser', 'firefox', 'chrome', 'opera', 'msie', 'edge', 'safari', 'blackberry', 'trident');

    /**
     * The function search in user_agent for some of described devices
     * 
     * @return (string) $device
     */
    protected function find_device()
    {
        if(empty($_SERVER['HTTP_USER_AGENT'])) {
            return '';
        }
        
        foreach ($this->devices as $d) {
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
    protected function find_browser()
    {
        if(empty($_SERVER['HTTP_USER_AGENT'])) {
            return '';
        }
        
        foreach ($this->browsers as $b) {
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
    protected function is_bot($u_agent = '')
    {
        $robots = '/googlebot|robot|facebook|twitter|ia_archiver|spider|crawl|curl|'
            . 'slurp|bot|panscient\.com|\.net\sclr|elnsb50|amd64|compatible|okhttp|'
            . 'scraper|kickfire|Beta\s\(Windows\)|ips-agent|Qwantify|BUbiNG|OrgProbe|^$/i';

        // the ip address are hashed if stored with md5 !!!
        $robots_ips = [];

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
