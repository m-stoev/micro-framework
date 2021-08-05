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

        // 2 load file by full path
        if(!empty($custom_footer) && is_file($custom_footer) && is_readable($custom_footer)) {
            require $custom_footer;
        }

        // 3 auto load js file
        if(defined('JS_PATH') && is_readable(JS_PATH . $controller . DS . $action . '.js')) {
            echo '<script type="text/javascript" src="/js/' . $controller . '/' . $action . '.js"></script>';
        }
		?>
	</body>
</html>