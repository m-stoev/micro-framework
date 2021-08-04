<?php

/**
* class Files
 * 
 * The class help us to work with files and directories.
* 
* @author Miroslav Stoev
* @package micro-framework
*/

class Files
{
    /**
    * Get files or directories recursively by some path;
    * 
    * @param (string) $path - path to directory
    * @param (bool) $recursive - search in current directory only or search in its children also
    * @param (bool) $get_files - when true get files, when fals get dirs
    * @param (array) $file_ext - allowed extensions
    * @param (bool) $get_full_path - get file full path instead parent dir only
    * 
    * @return (array) $result
    */
    public static function get_files(
        $path, 
        $recursive = false, 
        $get_files = true, 
        array $file_ext = [], 
        $get_full_path = false
    ) {
        $result = [];
			
        if(is_dir($path)) {
            if($recursive === true) {
                $dir    = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
                $iter   = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);
                
                if($get_files === true) {
                    $result = self::get_files_recursively($iter, $file_ext, $get_full_path);
                }
                else {
                    foreach ($iter as $file_info) {
                        $dir_name = $file_info->getFilename();
                        if($file_info->isDir() and !preg_match('/^\./', $dir_name)) {
                            $result[] = [
                                'dir_name' => $dir_name,
                                'level' => $iter->getDepth()
                            ];
                        }
                    }
                }
            }
            else {
                $iter = new DirectoryIterator($path);
                
                // do not get hidden files and dirs
                if($get_files === true) {
                    $result = self::get_files_not_recursively($iter, $file_ext);
                }
                else {
                    foreach ($iter as $file_info) {
                        $dir_name = $file_info->getFilename();
                        if(!$file_info->isDot() and $file_info->isDir() and !preg_match('/^\./', $dir_name))
                        {
                            $result[] = $dir_name;
                        }
                    }
                }
            }
        }
        else {
            Text::create_log($path, 'get_files() Error: iterator path is not readable');
            die('iterator path is not readable: '.$path);
        }

        return $result;
    }
    
    /**
    * Help function for avoid many if-else checks. Here we use parent dir as array for its children
    * 
    * @param (RecursiveIteratorIterator) $i
    * @param (array) $exts - allowed extensions of the files
    * 
    * @return (array) $result
    */
    private static function get_files_recursively(
        RecursiveIteratorIterator $i, 
        array $exts = [], 
        $get_full_path = false
    ) {
        $result = [];

        if(count($exts) > 0) {
            foreach($i as $file) {
                if($file->isFile() and in_array(strtolower($file->getExtension()), $exts)) {
					if(!$get_full_path) {
						$parent_dir             = basename(dirname($file));
						$result[$parent_dir][]  = $file->getFilename();
					}
					else {
						$parent_dir             = dirname($file);
						$result[$parent_dir][]  = $file->getFilename();
					}
                }
            }
        }
        else {
            foreach($i as $file) {
                if($file->isFile()) {
                    $parent_dir             = basename(dirname($file));
                    $result[$parent_dir][]  = $file->getFilename();
                }
            }
        }

        return $result;
    }

    /**
     * Help function for avoid many if-else checks. Here we do not use parent dir in result.
     * 
     * @param (RecursiveDirectoryIterator) $i
     * @param (array) $exts - allowed extensions of the files
     * 
     * @return (array) $result
     */
    private static function get_files_not_recursively(DirectoryIterator $i, array $exts = [])
    {
       $result = [];

		if(count($exts) > 0) {
			foreach($i as $file) {
				$file_name = $file->getFilename();
				
                if($file->isFile()
					&& !preg_match('/^\./', $file_name)
					&& in_array(strtolower($file->getExtension()), $exts)
				) {
					$result[] = $file_name;
				}
			}
		}
		else {
			foreach($i as $file) {
				$file_name = $file->getFilename();
				if($file->isFile() and !preg_match('/^\./', $file_name)) {
					$result[] = $file_name;
				}
			}
		}

		return $result;
    }
}

