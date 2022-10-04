<?php
namespace App\Models\Traits;

trait IdExtractor
{
    private function extractId( $mixed )    {
        if( gettype($mixed)=='object' ) {
            $id = isset($mixed->id) ? $mixed->id : null;
        } else if( gettype($mixed)=='array' ) {
            $id = isset($mixed['id']) ? $mixed['id'] : null;
        } else {
            $id = (int) $mixed;
        }
        if( !isset($id)) return null;
        return $id;
    }
}