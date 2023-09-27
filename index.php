<?php
/**
 * Plugin Name: Base Plugin
 * Plugin URI: https://melvinlomibao.com/projects/
 * Description: Allows you to create custom post type along with meta fields
 * Version: 1.0
 * Author: Melvin
 * Author URI: https://melvinlomibao.com/
 **/

!defined( 'BASE_DIR' ) ? define( "BASE_DIR", __DIR__ ) : "";

require_once BASE_DIR . "/core/functions.php";


new \BasePlugin\core\functions\pluginUpdateChecker( 156 );