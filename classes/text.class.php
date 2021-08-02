<?php
/**
 * class Text
 * 
 * text.class.php
 * sep.2015
 * Miroslav Stoev
 * micro-framework
 * 
 * Class for some functions working with strings and texts
 */
class Text
{
    /**
     * Function latin_to_slug
     * 
     * The function create standart slug from latin text - replace some special characters with simple ones.We have this method in model and controller classes because some projects may have not models.
     * 
     * @param (string) $str - the not standart text
     * @param (bool) $to_lower - do we convert string to lower cases or not
     * 
     * @return (string) standart slug text for url 
     */
    public static function latin_to_slug($str, $to_lower = FALSE) {
        $str = str_replace('&', 'And', $str);
        $str = trim(preg_replace('~[^0-9a-z]+~i', '-', html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($str, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-');

        return $to_lower ? strtolower($str) : $str;
    }

    /**
     * Function cyr_to_slug
     * 
     * The function try to change cyrilic letters with their latin (ASCII) "analogs", so they be use in url addresses. In first step we change letters, on the second we use latin_to_slug().
     * 
     * @param (string) $str - the string
     * @param (bool) $to_lower - do we convert string to lower cases or not
     * 
     * @return (string) - the slug
     */
    public static function cyr_to_slug($str, $to_lower = FALSE) {
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
        $str = self::latin_to_slug($str, $to_lower);

        return $to_lower ? mb_strtolower($str, 'UTF-8') : $str;
    }

    /**
     * Function cyr_to_slug
     * 
     * The function create cyrilic slug from cyrilic text
     * 
     * @param (string) $str - the string
     * @param (bool) $to_lower - do we convert string to lower cases or not
     * 
     * @return (string) - the slug
     */
    public static function cyr_to_cyr_slug($str, $to_lower = FALSE) {
        $str = mb_convert_encoding((string) $str, 'UTF-8', mb_list_encodings());
        $str = str_replace('&', 'И', $str);
        // remove punctoation
        $str = preg_replace('#[^\p{L}\p{N}]+#u', ' ', $str);
        // remove multiple intervals
        $str = preg_replace('"\s{2,}"', ' ', $str);
        $str = strip_tags($str);
        $str = trim($str);
        $str = str_replace(' ', '-', $str);

        return $to_lower ? mb_strtolower($str, 'UTF-8') : $str;
    }

    /**
     * Function lat_to_cyr
     * 
     * The corrent name of this function have to be "shliokavitsa_to_cyr" but it is too long.
     * The function will try to convert text write in shliokavitsa - bulgarian words with latin letters.
     * 
     * @param (string) $str - the string
     * @param (bool) $to_lower - do we convert string to lower cases or not
     * 
     * @return (string) - cyr words
     */
    public static function lat_to_cyr($str, $to_lower = FALSE) {
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
        $str = self::latin_to_slug($str, $to_lower);
        echo json_encode($letters);
        return $to_lower ? mb_strtolower($str, 'UTF-8') : $str;
    }

    /**
     * Function text_dot_dot_dot
     * 
     * The function get long text and cuts it to the predefined number of symbols. After the last word it puts "...". Optionaly after '...', it append "Read More" text, between anchor tag.
     * 
     * @param (string) $text
     * @param (int) $text_len
     * @param (string) $read_more_text - the text in specified language
     * @param (string) $link - the link
     * 
     * @return (string)
     */
    public static function text_dot_dot_dot($text, $text_len, $read_more_text = '', $link = '') {
        $new_text = '';

        if (!empty($text)) {
            $full_text_len = mb_strlen($text);

            if ($full_text_len > $text_len) {
                $cutted_text = substr($text, 0, $text_len);

                $last_empty_pos = strripos($cutted_text, ' ');
                $cutted_text = substr($cutted_text, 0, $last_empty_pos);

                $new_text = $cutted_text . ' ...';

                if (!empty($read_more_text)) {
                    if (!empty($link)) {
                        $link = '<a href="' . $link . '">' . $read_more_text . '</a>';
                        $new_text .= ' ' . $link;
                    } else {
                        $new_text .= ' ' . $link;
                    }
                }
            } else {
                $new_text = $text;
            }
        }

        return $new_text;
    }

    /**
     * Function mb_ucfirst
     * The function convert first letter in to capital. It is strange but this function is missing in PHP till now.
     * 
     * @param (string) $str - the string
     * @param (string) $encoding
     * @param (bool) $lower_str_end - do we want to convert the string end (string without first letter) to lower
     * 
     * @return (string)
     */
    public static function mb_ucfirst($str, $encoding = "UTF-8", $lower_str_end = FALSE) {
        if (!function_exists('mb_ucfirst')) {
            $first_letter = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding);
            $strEnd = "";

            if ($lower_str_end) {
                $str_end = mb_strtolower(mb_substr($str, 1, mb_strlen($str, $encoding), $encoding), $encoding);
            } else {
                $str_end = mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
            }

            return $first_letter . $str_end;
        }
    }

    /**
     * Function plural_singular
     * The function help to trasnform from singular to plural and from plural to singular endings.
     * Main use is when transform from Class Name to Model Name
     * 
     * @param (string) $string - the string we need convert
     * @param (string) $result - what to be the result - plural or singular
     * 
     * @return (string) $new_string
     */
    public static function plural_singular($string, $result) {
        $new_string = $string;
        $used_array = array();
        $not_used_array = array();
        $string_len = strlen($string);

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
            $used_array = $plurals;
            $not_used_array = $singular;
        }
        // search for singular end and replace with plural
        elseif ($result == 'plural') {
            $used_array = $singular;
            $not_used_array = $plurals;
        }

        // search for ending to replace it
        foreach ($used_array as $key => $end) {
            if (strripos($string, $end) > -1) {
                $end_len = strlen($end);
                $last_letters = substr($string, $string_len - $end_len);

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
     * Function debug
     * Return formated preview of the variable
     * 
     * @param mixed $data
     * @param bool $in_session - pass to session or direct print
     * @param string $name - some name
     */
    public static function debug($data = '', $in_session = true, $name = '') {
        $remote_addr = filter_input(INPUT_SERVER, "REMOTE_ADDR", FILTER_SANITIZE_STRING);

        if(DEBUG_MODE or in_array($remote_addr, json_decode(TRUSTED_IPS))) {
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
     * Function create_error_log
     * The function create error log, and send mail to the admin,
     * if we are in production or display error directly,
     * if we are in development mode.
     * 
     * @param any $data - data to print
     * @param bool $redirect - redirect to 404
     * 
     * @deprecated
     */
    public static function create_error_log($data, $redirect = true) {
        $input_as_text = '<pre>' . print_r($data, true) . '</pre><br/>';

        if (DEBUG_MODE) {
            if($redirect) {
                self::debug($data, false);
                die('Text::create_error_log() die');
            }
            else {
                self::debug($data);
            }
        }
        else {
            $text = "\n" . date("Y-m-d H:i:s") . "\n" . $input_as_text;
            // set file to be utf-8
            file_put_contents(
                ROOT . 'tmp' . DS . 'logs' . DS . date("Y-m-d") . '-errors.txt',
                "\xEF\xBB\xBF" . mb_convert_encoding($text, 'UTF-8'),
                FILE_APPEND
            );

            if (defined(ADMIN_EMAIL) and ADMIN_EMAIL) {
                mail(ADMIN_EMAIL, 'Site error', $text);
            }

            // if this is AJAX request just return response
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode([
                    'status' => false,
                    'msg' => 'There was an error. Error log was created.'
                ]);

                exit;
            }

            if ($redirect) {
                // in any other case go to 404
                header('Location: ' . COOKIE_PATH . 'error404/');
                exit;
            }
        }
    }
    
    /**
     * A function to save logs. Use it instead of old create_error_log()
     * 
     * @param mixed $data
     * @param string $title
     * @param bool $redirect
     */
    public static function create_log($data, $title = '', $redirect = true)
    {
        if(!defined('LOGS_PATH')) {
            exit();
        }
        
        $beauty_log = false;
        
        if(defined('BEAUTY_LOG') && !empty(BEAUTY_LOG)) {
            $beauty_log = $beauty_log;
        }
        
        $string     = '';
        $data_str   = '';
        
        if(is_array($data) || is_object($data)) {
            $data_str = $beauty_log ? print_r($data, true) : json_encode($data_str);
        }
        elseif(is_bool($data)) {
            $data_str = $data ? 'true' : 'false';
        }
        
        if(!empty($title)) {
            if(is_string($title)) {
                $string = $title;
            }
            else {
                $string = json_encode($title);
            }
        }
        
        $string .= "\r\n";
        $string = $data_str . "\r\n\r\n";
        
        try {
            file_put_contents(
                LOGS_PATH . 'log-' . date('Y-m-d', time()) . '.txt',
                $string,
                FILE_APPEND
            );
        }
        catch (Exception $ex) {}
    }

    /**
     * Function send_email
     * Function for sending simple e-mails
     * 
     * @param (array) $emails - array with mails
     * @param (string) $subject
     * @param (string) $message
     * @param (string) $sender_email

     * @return (bool)
     */
    public static function send_mail(array $emails, $subject, $message, $sender_email = '') {
        if (!$sender_email) {
            $sender_email = 'no-replay@'.SITE_NAME;
        }

        $to = implode(', ', $emails);

        $headers = 'MIME-Version: 1.0'."\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        $headers .= 'From: '.$sender_email."\r\n";

        if (!mail($to, $subject, $message, $headers)) {
            self::create_error_log('Error while send email!', false);
            return false;
        }

        return true;
    }
}
