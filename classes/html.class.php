<?php

/* 
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
 * html.class.php
 * august 2018
 * Miroslav Stoev
 * micro-framework
 * 
 * TODO - add suport for the libraries !!!
 */
Class Html
{
    /**
     * Function btn_save
     * Creates button with save icon in it
     * 
     * @param string $type - default, success, etc.
     * @param type $size - btn-sm, btn-lg, etc.
     * @return string
     */
    public static function btn_save($type = 'default', $size = '')
    {
        $version = defined('GLYPH_LIB_VER') ? intval(GLYPH_LIB_VER) : 4;
        $button = '<button type="submit" class="btn btn-'.$type.' '.($size ? $size : '').'">';
        $gl = '';
        
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
}

