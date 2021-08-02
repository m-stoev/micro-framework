<?php

/**
 * class Images
 * 
 * images.class.php
 * 
 * The class help us to work with images
 * 
 * @author Miroslav Stoev
 * @package micro-framework
 */
class Images
{
    // fields in the exif who can hold the time of creation
    private static $_time_holders = ['DateTimeOriginal', 'DateTime', 'FileDateTime'];

    /**
     * Function get_image_exif
     *
     * The function check for image in a directory and return its exif data
     *
     * @param string $path
     */
    public static function get_image_exif($path)
    {
        if (is_readable($path) and is_file($path)) {
            $image_data = array();
            $image_time = '';

            @$exif_data = exif_read_data($path, null);

            if ($exif_data) {
                foreach (self::$_time_holders as $holder) {
                    if (isset($exif_data[$holder])) {
                        if (is_numeric($exif_data[$holder])) {
                            $image_time = self::time_to_string($exif_data[$holder]);
                        } else {
                            $image_time = self::format_string_time($exif_data[$holder]);
                        }

                        break;
                    }
                }

                $focal_length_arr = @explode('/', $exif_data['FocalLength']);
                $focal_length     = @round($focal_length_arr[0] / $focal_length_arr[1], 1);

                $exposure_bias_value_arr = @explode('/', $exif_data['ExposureBiasValue']);
                $exposure_bias_value     = @round($exposure_bias_value_arr[0] / $exposure_bias_value_arr[1], 2).' EV';

                $exposure_time_arr = @explode('/', $exif_data['ExposureTime']);
                $exposure_time     = @$exposure_time_arr[1] == 1 ?
                    $exposure_time_arr[0] : @$exif_data['ExposureTime'];

                $image_data = array(
                    'Taken_on'      => $image_time,
                    'Foc_len'       => $focal_length,
                    'Sh_speed'      => $exposure_time,
                    'F_num'         => @$exif_data['COMPUTED']['ApertureFNumber'],
                    'ISO'           => @$exif_data['ISOSpeedRatings'],
                    'White_bal'     => @$exif_data['WhiteBalance'],
                    'Flash'         => @$exif_data['Flash'],
                    'Cam_model'     => @trim($exif_data['Model']),
                    'Exp_program'   => @$exif_data['ExposureProgram'],
                    'Exp_bias'      => $exposure_bias_value,
                    'Metering'      => @$exif_data['MeteringMode'],
                    'Size'          => @$exif_data['COMPUTED']['Width'].'x'.@$exif_data['COMPUTED']['Height']
                );
            }

            //	Text::debug($image_data);
            //	die;

            return $image_data;
        }
    }
    
    /**
     * Function use_exiftool
     * Use preinstalled exiftool software to get exif information.
     * Works ONLY with JPG files!
     * 
     * @param string $app_path - the path to the executable
     * @param string $ext - extension of the file
     * @param array $img_data - the data we want to get for the image
     * @param string $img_path - path to the image or image's directory
     * @param string $output - path to the outputfile, do not include file name!
     * 
     * @return mixed output file name or false
     */
    public static function use_exiftool($app_path, $ext, array $img_data, $img_path, $output)
    {
        $cmd = $app_path;
        
        if(!in_array($ext, array('T', 'csv'))) {
            $ext = 'csv';
        }
        $cmd .= ' -'.$ext;
        
        if(strpos(strtolower($img_path), '.jpg') === false) {
            $cmd .= ' -r';
        }
        
        $output = trim($output, '/');
        $file_name = uniqid().'.'.$ext;
        
        $cmd .= ' -'.implode(' -', $img_data).' '.$img_path.' > /'
            .$output.'/'.$file_name;
    
        exec($cmd, $out, $ret);
        return $ret == 0 ? $file_name : false;
    }
    
    /**
     * Function get_image_create_date
     * The function check for image in a directory and return its date of creation
     * 
     * @param string $path
     * @return string
     */
    public static function get_image_create_date($path)
    {
        $image_time = null;

        if (is_readable($path) and is_file($path)) {
            $image_time = '';

            // the keys that may be holds time of image creation
            $time_holders = array('DateTimeOriginal', 'DateTime', 'FileDateTime');
            @$exif_data    = exif_read_data($path, null);

            if ($exif_data) {
                foreach (self::$_time_holders as $holder) {
                    if (isset($exif_data[$holder])) {
                        if (is_numeric($exif_data[$holder])) {
                            $image_time = [
                                self::time_to_string($exif_data[$holder]),
                                @$exif_data['COMPUTED']['Width'].'x'.@$exif_data['COMPUTED']['Height']
                            ];
                        } else {
                            $image_time = [
                                self::format_string_time($exif_data[$holder]),
                                @$exif_data['COMPUTED']['Width'].'x'.@$exif_data['COMPUTED']['Height']
                            ];
                        }

                        break;
                    }
                }
            }
        }
        //	Text::debug($path);
        return $image_time;
    }

    /**
     * Function upload_image
     * Upload and save single image
     *
     * @param array $files - $_FILES variable
     * @param array $allowed_ext - allowed extensions: ['jpg', 'png']
     * @param int $max_size - in bytes
     * @param string $dir_path - path to upload directory
     * @param bool $overwrite - do overwrite if file exists
     * 
     * @return array('status' => true|false, 'msg' => 'some text') - msg is optional
     */
    public static function upload_image(array $files, array $allowed_ext, $max_size, $dir_path, $overwrite = false)
    {
        $files = current($files);
        
        if (count($files) != 5) {
            return array(
                'status' => false,
                'msg' => 'Problem with file array <pre>'.print_r($files, true).'</pre>');
        }

        if (!is_dir($dir_path)) {
            if (!mkdir($dir_path, 0755, true)) {
                return array(
                    'status' => false,
                    'msg' => "Error when create directory: ".$dir_path
                );
            }
        }
        
        if(!isset($files["name"]) or !is_array($files["name"]) or empty($files["name"])) {
            return array(
                'status' => false,
                'msg' => "Files array does not contain names: " . $files
            );
        }
        
        for($cnt = 0; $cnt < count($files["name"]); $cnt++) {
            $name_parts = explode(".", $files["name"][$cnt]);
            $ext = strtolower(end($name_parts));
            
            // check extension
            if(!in_array($ext, $allowed_ext)) {
                return array(
                    'status' => false,
                    'msg' => "Not allowed file type: ".$files["name"][$cnt]
                );
            }
            
            // chacks size
            if($files['size'][$cnt] > $max_size) {
                return array(
                    'status' => false,
                    'msg' => "File is too big: ".$files["name"][$cnt]
                        .', '.$files['size'][$cnt]
                );
            }
            
            // clear empty intervals from the name
            $img_name = str_replace(' ', '-',  $files["name"][$cnt]);
            
            // check for existing file name
            if (file_exists($dir_path . $img_name) and !$overwrite) {
                return array(
                    'status' => false,
                    'msg' => "Duplicate file name."
                );
            }
            
            if(!move_uploaded_file($files["tmp_name"][$cnt], $dir_path . $img_name)) {
                return array(
                    'status' => false,
                    'msg' => "The file was not uploaded."
                );
            }
        }

        return array('status' => true, 'msg' => '');
    }

    /**
     * Function time_to_string
     * Convert time (datetime or string time) to string time with format Y-m-d H:i:s
     * 
     * @param $_the_time the time
     * @return string the time with new format
     */
    private static function time_to_string($_the_time)
    {
        $_format = 'Y-m-d H:i:s';

        if (is_numeric($_the_time)) {
            return date($_format, $_the_time);
        }
        else {
            $d = new DateTime($_the_time);
            return $d->format($_format);
        }
    }

    private static function format_string_time($_the_time)
    {
        $date = new DateTime($_the_time);
        return $date->format('Y-m-d H:i:s');
    }
    
}