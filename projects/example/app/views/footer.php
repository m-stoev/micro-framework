            <?php if(defined('DEBUG_MODE') && DEBUG_MODE and isset($_SESSION['debug_log'])): ?>
                <div id="debug_cont"><?= $_SESSION['debug_log'] ?></div>
            <?php endif; ?>
        </div>
		<!-- wrapper end -->
		
		<!-- put here scripts or what you need -->
		
		<?php
        // 1 auto load a _footer_{$action}.php file from directory with name as the controller
        if(defined('VIEWS_PATH') && is_readable(VIEWS_PATH . $controller . DS . '_footer_' . $action . '.php')) {
            require VIEWS_PATH . $controller . DS . '_footer_' . $action . '.php';
        }

        // 2 other php file to include
        if(!empty($custom_footer)) {
            // check for extension
            $ext_pos = stripos($custom_footer, '.php');
            
            if($ext_pos < 0)  {
                $custom_footer .= '.php';
            }

            if(defined('VIEWS_PATH')) {
                if(is_readable(VIEWS_PATH . $controller . DS . $custom_footer)) {
                    require VIEWS_PATH . $controller . DS . $custom_footer;
                }
                elseif(is_readable(VIEWS_PATH . 'elements' . DS . $custom_footer)) {
                    require VIEWS_PATH .'elements' . DS . $custom_footer;
                }
            }
        }

        // 3 js file to include
        if(defined('JS_PATH')) {
            if(is_readable(JS_PATH . $controller . DS . $action . '.js')) {
                echo '<script type="text/javascript" src="/js/' . $controller . '/' . $action . '.js"></script>';
            }
            elseif(!empty($custom_js_footer) && is_readable(JS_PATH . $controller . DS . $custom_js_footer)) {
                // check for extension
                $ext_pos = stripos($custom_footer, '.js');
                if($ext_pos < 0) {
                    $custom_js_footer .= '.js';
                }

                echo '<script type="text/javascript" src="/js/' . $controller . '/' . $custom_js_footer . '"></script>';
            }
        }
		?>
	</body>
</html>