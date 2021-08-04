<?php

/** 
 * class HTML
 * 
 * With this class we will try fast draw of common html elemnts
 * like buttons, inputs, form, etc.
 * 
 * The class will check for the following constants:
 * 
 * GLYPH_LIB - bootstrap or fontawesome
 * GLYPH_LIB_VER - the version: 3, 4, 5, etc.
 * 
 * If they are not set by default we will use FontAwesome 4
 * 
 * @author Miroslav Stoev
 * @package micro-framework
 * 
 * TODO - add suport for the libraries !!!
 */
Class Html
{
    /**
     * Creates button with save icon in it.
     * 
     * @param string $type default, success, etc.
     * @param type $size btn-sm, btn-lg, etc.
     * 
     * @return string
     */
    public static function btn_save($type = 'default', $size = '')
    {
        $version    = defined('GLYPH_LIB_VER') ? intval(GLYPH_LIB_VER) : 4;
        $button     = '<button type="submit" class="btn btn-'.$type.' '.($size ? $size : '').'">';
        $gl         = '';
        
        if(!defined('GLYPH_LIB') or GLYPH_LIB == 'fontawesome') {
            switch($version) {
                case 5:
                    $gl = '<i class="far fa-save"></i>';
                    break;
                
                default:
                    $gl = '<i class="fa fa-save"></i>';
                    break;
            }
        }
        else {
            switch($version) {
                default:
                    $gl = '<i class="fa fa-floppy-o" aria-hidden="true"></i>';
                    break;
            }
        }
        
        return $button . $gl . '</button>';
    }
    
    /**
     * Function btn_del
     * Creates button with trash icon in it
     * 
     * @param int $rec_id - record id
     * @param string $title - modal title
     * @param string $body - modal body
     * @param string $href - url to go
     * @param string $type - default, success, etc.
     * @param type $size - btn-sm, btn-lg, etc.
     * 
     * @return string
     */
    public static function btn_del($rec_id, $title = '', $body = '', $href = '', $type = 'default', $size = '')
    {
        $button = '<button type="button" class="btn btn-'
            .$type.' data-onclick="deleteRecord('
            .$rec_id.')" data-toggle="modal" data-target="#confirm_delete" data-title="'
            .$title.'" data-body="'.$body.'" data-href="'.$href.'" '
            .($size ? $size : '').'">';
        
        if(!defined('GLYPH_LIB') or GLYPH_LIB == 'fontawesome') {
            $gl = '<i class="fa fa-trash"></i>';
        }
        else {
            // TODO
        }
        
        return $button . $gl . '</button>';
    }
    
    /**
     * Generate simple html options from db results.
     * The function generates only options elements, you have to
     * put the results in select tag!!!
     * 
     * @param array $results - array with results
     * @param string $val_key - the array key for option value
     * @param array $text_keys - the array keys to generete option text, concatenate with empty spaces
     * @param array $selected - [results_key, needed_value] - put 'selected' on an option with this value
     * @param array $classes - if need some classes for each option element
     * @param bool $class_form_results - get the class form results value
     *      need $class to be set, it will be results key
     * 
     * @return string $html - html result
     */
    public static function html_options(
        $results, $val_key, 
        array $text_keys, 
        array $selected = [],
        array $classes = [], 
        $class_form_results = false
    ) {
        $html = '';
        
        if(!$results) {
            return $html;
        }

        foreach ($results as $data) {
            $text           = '';
            $selected_flag  = '';

            if ($selected && $data[$selected[0]] == $selected[1]) {
                $selected_flag = 'selected=""';
            }

            // add classes
            $html_class = '';

            if ($classes) {
                foreach ($classes as $cl) {
                    if ($class_form_results and isset($data[$cl])) {
                        $html_class .= str_replace(' ', '_', $data[$cl]) . ' ';
                    } else {
                        $html_class .= $cl . ' ';
                    }
                }
            }

            $html .= '<option class="' . $html_class . '" value="' . $data[$val_key] . '" ' . $selected_flag . '>';

            foreach ($text_keys as $k) {
                $text .= $data[$k] . ' ';
            }

            $html .= trim($text) . '</option>';
        }

        return $html;
    }
}

