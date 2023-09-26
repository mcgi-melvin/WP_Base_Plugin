<?php
$functions_path = BASE_DIR . "/core/functions/";

$files = array_diff(scandir($functions_path), array('.', '..'));

foreach( $files as $file ) {
    $path = "{$functions_path}{$file}";

    if( !file_exists( $path ) ) continue;

    require_once $path;
}