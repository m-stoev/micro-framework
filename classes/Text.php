<?php
/**
 * Helper class to work with strings.
 * 
 * @author Miroslav Stoev
 * @package micro-framework
 * 
 * Class for some functions working with strings and texts
 */
class Text
{
    private static $trace_id;
    
    /**
     * A function to save logs.
     * 
     * @param mixed $data
     * @param string $message
     * @param string $log_level
     */
    public static function create_log($data, $message = '', $log_level = 'INFO')
    {
        if(!defined('LOGS_PATH')) {
            return;
        }
        
        if(defined('CACHE_PATH') && !is_writable(CACHE_PATH)) {
            exit(json_encode([
                'error' => CACHE_PATH . ' is not writable. The permissions are: '
                    . substr(sprintf('%o', fileperms(CACHE_PATH)), -4)
            ]));
        }

        $beauty_log = false;
        
        if(defined('BEAUTY_LOG') && !empty(BEAUTY_LOG)) {
            $beauty_log = BEAUTY_LOG;
        }
        
        # prepare log parts
        $utimestamp     = microtime(true);
        $timestamp      = floor($utimestamp);
        $milliseconds   = round(($utimestamp - $timestamp) * 1000000);
        $record_time    = date('Y-m-d') . 'T' . date('H:i:s') . '.' . $milliseconds . date('P');
        
        $tab = '    '; // 4 spaces
        
        if(!self::$trace_id) {
            self::$trace_id = bin2hex(random_bytes(16));
        }
        
//        if(!empty($span_id)) {
//            $span_id .= $tab;
//        }
//        
//        if(!empty($parent_id)) {
//            $parent_id .= $tab;
//        }
        
        $machine_name = '';
        if(defined('LOGS_MACHINE_NAME') && !empty(LOGS_MACHINE_NAME)) {
            $machine_name = LOGS_MACHINE_NAME . '|';
        }
        
        $service_name = '';
        if(defined('LOGS_SERVICE_NAME') && !empty(LOGS_SERVICE_NAME)) {
            $service_name = LOGS_SERVICE_NAME . '|';
        }
        
        $source_file_name   = '';
        $member_name        = '';
        $source_line_number = '';
        
        $backtrace = debug_backtrace();
        if(!empty($backtrace)) {
            if(!empty($backtrace[0]['file'])) {
                $file_path_arr  = explode(DS, $backtrace[0]['file']);
                
                if(!empty($file_path_arr)) {
                    $source_file_name = end($file_path_arr) . '|';
                }
            }
            
//            if(!empty($backtrace[0]['function'])) {
//                $member_name = $backtrace[0]['function'] . '|';
//            }
            
            if(!empty($backtrace[0]['line'])) {
                $source_line_number = $backtrace[0]['line'] . $tab;
            }
        }
        
        # prepare log parts END
        
        if(is_array($data) || is_object($data)) {
            $exception = $beauty_log ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data);
        }
        elseif(is_bool($data)) {
            $exception = $data ? 'true' : 'false';
        }
        else {
            $exception = $data;
        }
        
        // Content of the log string:
        $string = $record_time      // timestamp
            . $tab                  // tab
            . $log_level            // level
            . $tab                  // tab
            . self::$trace_id       // TraceId
            . $tab                  // tab
//            . $span_id              // SpanId, if not empty it will include $tab
//            . $parent_id            // ParentId, if not empty it will include $tab
            . $machine_name         // MachineName if not empty it will include a "|"
            . $service_name         // ServiceName if not empty it will include a "|"
            // TreadId
            . $source_file_name     // SourceFileName if not empty it will include a "|"
            . $member_name          // MemberName if not empty it will include a "|"
            . $source_line_number   // SourceLineName if not empty it will include $tab
            // RequestPath
            // RequestId
            . $message
            . $tab
            . $exception                 // the exception
        ;
        
        $string     .= "\r\n\r\n";
        $file_name  = 'log-' . date('Y-m-d', time());
        
        if(defined('LOG_FILE_NAME') && !empty(LOG_FILE_NAME)) {
            $file_name  = LOG_FILE_NAME;
        }
        
        try {
            file_put_contents(
                LOGS_PATH . $file_name . '.log',
                $string,
                FILE_APPEND
            );
        }
        catch (Exception $ex) {}
    }
    
    /**
     * Remove emoticons form a string.
     * 
     * @param string $text
     * @return string
     */
    public static function remove_emoticons($text)
    {
        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text     = preg_replace($regexEmoticons, '', $text);

        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols   = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text     = preg_replace($regexSymbols, '', $clean_text);

        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text     = preg_replace($regexTransport, '', $clean_text);

        // Match Miscellaneous Symbols
        $regexMisc  = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);

        // Match Dingbats
        $regexDingbats  = '/[\x{2700}-\x{27BF}]/u';
        $clean_text     = preg_replace($regexDingbats, '', $clean_text);

        // Match Flags
        $regexDingbats  = '/[\x{1F1E6}-\x{1F1FF}]/u';
        $clean_text     = preg_replace($regexDingbats, '', $clean_text);

        // Others
        $regexDingbats  = '/[\x{1F910}-\x{1F95E}]/u';
        $clean_text     = preg_replace($regexDingbats, '', $clean_text);

        $regexDingbats  = '/[\x{1F980}-\x{1F991}]/u';
        $clean_text     = preg_replace($regexDingbats, '', $clean_text);

        $regexDingbats  = '/[\x{1F9C0}]/u';
        $clean_text     = preg_replace($regexDingbats, '', $clean_text);

        $regexDingbats  = '/[\x{1F9F9}]/u';
        $clean_text     = preg_replace($regexDingbats, '', $clean_text);
        
        // Match Variation Selectors 
        $regexVarSel    = '/[\x{FE00}-\x{FE0F}]/u';
        $clean_text     = preg_replace($regexVarSel, '', $clean_text);
        
        // at the end convert all wierd dashes with "-"
        $clean_text = preg_replace('#\p{Pd}#u', '-', $clean_text);
        
        // remove multispaces
        $clean_text = preg_replace('/[[:blank:]]+/', ' ', $clean_text);

        return trim($clean_text);
    }
    
    /**
     * The function create standart slug from latin text - replace some special characters with simple ones.
     * 
     * @param (string) $str the not standart text
     * @param (bool) $to_lower do we convert string to lower cases or not
     * 
     * @return (string) standart slug text for url 
     */
    public static function latin_to_slug($str, $to_lower = false)
    {
        $str = str_replace('&', 'And', $str);
        $str = trim(preg_replace('~[^0-9a-z]+~i', '-', html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($str, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-');

        return $to_lower ? strtolower($str) : $str;
    }

    /**
     * The function try to change cyrilic letters with their latin (ASCII) "analogs", so they be use in url addresses.
     * In first step we change letters, on the second we use latin_to_slug().
     * 
     * @param (string) $str the string
     * @param (bool) $to_lower do we convert string to lower cases or not
     * 
     * @return (string) the slug
     */
    public static function cyr_to_slug($str, $to_lower = false)
    {
        // cyrilic to latin analogs
        $letters = array(
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo',
            'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M',
            'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sht', 'Ъ' => 'A',
            'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            // the combinations are befor the other to be sure we will catch them
            'ьо' => 'io',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sht', 'ъ' => 'a',
            'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        );

        $str = mb_convert_encoding((string) $str, 'UTF-8', mb_list_encodings());
        $str = str_replace(array_keys($letters), $letters, $str);
        $str = $this->latin_to_slug($str, $to_lower);

        return $to_lower ? mb_strtolower($str, 'UTF-8') : $str;
    }

    /**
     * The function create cyrilic slug from cyrilic text
     * 
     * @param (string) $str the string
     * @param (bool) $to_lower do we convert string to lower cases or not
     * 
     * @return (string) the slug
     */
    public static function cyr_to_cyr_slug($str, $to_lower = false)
    {
        $str = mb_convert_encoding((string) $str, 'UTF-8', mb_list_encodings());
        $str = str_replace('&', 'И', $str);
        $str = preg_replace('#[^\p{L}\p{N}]+#u', ' ', $str); // remove punctoation
        $str = preg_replace('"\s{2,}"', ' ', $str); // remove multiple intervals
        $str = strip_tags($str);
        $str = trim($str);
        $str = str_replace(' ', '-', $str);

        return $to_lower ? mb_strtolower($str, 'UTF-8') : $str;
    }

    /**
     * The current name of this function have to be "shliokavitsa_to_cyr" but it will be ugly.
     * The function will try to convert text write in shliokavitsa - bulgarian words with latin letters.
     * 
     * @param (string) $str the string
     * @param (bool) $to_lower do we convert string to lower cases or not
     * 
     * @return (string) cyr words
     */
    public static function lat_to_cyr($str, $to_lower = false)
    {
        $letters = array(
            'Ch' => 'Ч', 'Ia' => 'Я', 'Ju' => 'Ю', 'Sh' => 'Ш', 'Sht' => 'Щ', 'Yu' => 'Ю',
            'Ya' => 'Я', 'Zh' => 'Ж',
            'A' => 'А', 'B' => 'Б', 'C' => 'Ц', 'D' => 'Д', 'E' => 'Е', 'F' => 'Ф', 'G' => 'Г',
            'H' => 'Х', 'I' => 'И', 'J' => 'Й', 'K' => 'К', 'L' => 'Л', 'M' => 'М', 'N' => 'Н',
            'O' => 'О', 'P' => 'П', 'Q' => 'Я', 'R' => 'Р', 'S' => 'С', 'T' => 'Т', 'U' => 'У',
            'V' => 'В', 'W' => 'В', 'X' => 'Ь', 'Y' => 'Ъ', 'Z' => 'З',
            'ch' => 'ч', 'ia' => 'я', 'ju' => 'ю', 'sh' => 'ш', 'sht' => 'щ', 'yu' => 'ю',
            'ya' => 'я', 'zh' => 'ж',
            'a' => 'а', 'b' => 'б', 'c' => 'ц', 'd' => 'д', 'e' => 'е', 'f' => 'ф', 'g' => 'г',
            'h' => 'х', 'i' => 'и', 'j' => 'й', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н',
            'o' => 'о', 'p' => 'п', 'q' => 'я', 'r' => 'р', 's' => 'с', 't' => 'т', 'u' => 'у',
            'v' => 'в', 'w' => 'в', 'x' => 'ь', 'y' => 'ъ', 'z' => 'з',
        );

        $str = mb_convert_encoding((string) $str, 'UTF-8', mb_list_encodings());
        $str = str_replace(array_keys($letters), $letters, $str);
        $str = $this->latin_to_slug($str, $to_lower);
        
        return $to_lower ? mb_strtolower($str, 'UTF-8') : $str;
    }

    /**
     * The function get long text and cuts it to the predefined number of symbols.
     * After the last word it puts "...".
     * Optionaly after '...', it append "Read More" text, between anchor tag.
     * 
     * @param (string) $text
     * @param (int) $text_len
     * @param (string) $read_more_text the text in specified language
     * @param (string) $link the link
     * 
     * @return (string)
     */
    public static function text_dot_dot_dot($text, $text_len, $read_more_text = '', $link = '')
    {
        $new_text = '';

        if (!empty($text)) {
            $full_text_len = mb_strlen($text);

            if ($full_text_len > $text_len) {
                $cutted_text    = substr($text, 0, $text_len);
                $last_empty_pos = strripos($cutted_text, ' ');
                $cutted_text    = substr($cutted_text, 0, $last_empty_pos);
                $new_text       = $cutted_text . ' ...';

                if (!empty($read_more_text)) {
                    if (!empty($link)) {
                        $link       = '<a href="' . $link . '">' . $read_more_text . '</a>';
                        $new_text   .= ' ' . $link;
                    } else {
                        $new_text .= ' ' . $link;
                    }
                }
            }
            else {
                $new_text = $text;
            }
        }

        return $new_text;
    }

    /**
     * The function convert first letter in to capital.
     * It is strange but this function is missing in PHP till now.
     * 
     * @param (string) $str the string
     * @param (string) $encoding
     * @param (bool) $lower_str_end do we want to convert the string end (string without first letter) to lower
     * 
     * @return (string)
     */
    public static function mb_ucfirst($str, $encoding = "UTF-8", $lower_str_end = false)
    {
        if (!function_exists('mb_ucfirst')) {
            $first_letter   = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding);
            $strEnd         = "";

            if ($lower_str_end) {
                $str_end = mb_strtolower(mb_substr($str, 1, mb_strlen($str, $encoding), $encoding), $encoding);
            }
            else {
                $str_end = mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
            }

            return $first_letter . $str_end;
        }
    }

    /**
     * The function help to transform from singular to plural and from plural to singular endings.
     * Main use is when transform from Class Name to Model Name
     * 
     * @param (string) $string the string we need convert
     * @param (string) $result what to be the result - plural or singular
     * 
     * @return (string) $new_string
     */
    public static function plural_singular($string, $result)
    {
        $new_string     = $string;
        $string_len     = strlen($string);
        $used_array     = array();
        $not_used_array = array();

        // list with non simple endings of plural forms like Countries, addresses...
        $plurals = array(
            'ies', 'ses', 's',
        );

        $singular = array(
            'y', 's', '',
        );

        // choose the array we will use in the search
        // search for plural end and replace with singular
        if ($result == 'singular') {
            $used_array     = $plurals;
            $not_used_array = $singular;
        }
        // search for singular end and replace with plural
        elseif ($result == 'plural') {
            $used_array     = $singular;
            $not_used_array = $plurals;
        }

        // search for ending to replace it
        foreach ($used_array as $key => $end) {
            if (strripos($string, $end) > -1) {
                $end_len        = strlen($end);
                $last_letters   = substr($string, $string_len - $end_len);

                if ($last_letters == $end) {
                    $new_string = substr($string, 0, $string_len - $end_len);
                    $new_string .= $not_used_array[$key];
                    break;
                }
            }
        }

        // last check
        if ($result == 'plural' and $string === $new_string) {
            $new_string .= 's';
        }

        return $new_string;
    }

    /**
     * Print a variable.
     * 
     * @param mixed $data
     * @param bool $in_session pass to session or direct print
     * @param string $name some name
     */
    public static function debug($data = '', $in_session = true, $name = '')
    {
        if( (defined('DEBUG_MODE') && DEBUG_MODE)
            || (defined('TRUSTED_IPS') && in_array($_SERVER["REMOTE_ADDR"], json_decode(TRUSTED_IPS)))
        ) {
            if ($in_session) {
                $_SESSION['debug_log'] .= '<pre><strong>'.$name.'</strong><br/>'.
                    print_r($data, true).'</pre><br/>';
            }
            else {
                echo '<pre><strong>'.$name.'</strong><br/>'.print_r($data, true).'</pre><br/>';
            }
        }
    }

    /**
     * Function for sending simple e-mails
     * 
     * @param (array) $emails array with mails
     * @param (string) $subject
     * @param (string) $message
     * @param (string) $sender_email

     * @return (bool)
     */
    public static function send_mail(array $emails, $subject, $message, $sender_email = '')
    {
        $site_name = 'no_name';
        
        if(defined('SITE_NAME') && !empty(SITE_NAME)) {
            $site_name = SITE_NAME;
        }
        
        if (!$sender_email) {
            $sender_email = 'no-replay@' . $site_name;
        }

        $to = implode(', ', $emails);

        $headers = 'MIME-Version: 1.0'."\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        $headers .= 'From: '.$sender_email."\r\n";

        if (!mail($to, $subject, $message, $headers)) {
            $this->create_log('send_mail() - Error while send email!');
            return false;
        }

        return true;
    }
    
}
