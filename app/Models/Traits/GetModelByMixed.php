<?php
namespace App\Models\Traits;

trait GetModelByMixed
{
    public static function getModelByMixed( $mixed )   {
        if( gettype($mixed)=='object' ) {
            return $mixed;
        } else if( gettype($mixed)=='array' ) {
            $id = isset($mixed['id']) ? $mixed['id'] : null;
        } else {
            $id = (int) $mixed;
        }
        if( !isset($id)) return null;
        return self::find($id);
    }
}
