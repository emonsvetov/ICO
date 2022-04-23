<?php
if(!function_exists('pr'))  {
    function pr($d)    {
        $appPath = app_path();
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $relativePath = str_replace( $appPath, '', $caller['file']);
        $file_line = $relativePath . "(line " . $caller['line'] . ")\n";
        print_r($file_line);
        print_r($d);
        print_r("\n\n");
    }
}

if(!function_exists('pre'))  {
    function pre($d)    {
        $appPath = app_path();
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $relativePath = str_replace( $appPath, '', $caller['file']);
        $file_line = $relativePath . "(line " . $caller['line'] . ")\n";
        print_r($file_line);
        echo '<pre>';
        print_r($d);
        echo '</pre>';
    }
}