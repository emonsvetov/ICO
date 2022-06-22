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

function generate_unique_id($char = 12)
{
    $rand = strtoupper(substr(uniqid(sha1(time())),0,$char));
    return date("ymds") .'-'. $rand;
}

if(!function_exists('get_merchant_by_id'))  {
    function get_merchant_by_id($merchants, $merchant_id)   {
        foreach($merchants as $merchant)   {
            if($merchant->id == $merchant_id) return $merchant;
        }
    }
}
if(!function_exists('isValidDate'))  {
    function isValidDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

if(!function_exists('_flatten'))  {
    function _flatten($collection, &$newCollection)
    {
        foreach( $collection as $model ) {
            $children = $model->children;
            unset($model->children);
            if( !$newCollection ) {
                $newCollection = collect([$model]);
            }   else {
                $newCollection->push($model);
            }
            if (!$children->isEmpty()) {
                $newCollection->merge(_flatten($children, $newCollection));
            }
        }
    }
}