<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $guarded = [];
    public static function getIdByType( $type, $insert = false ) {
        $first = self::where('type', $type)->first();
        if( $first )    {
            return $first->id;
        }
        if( $insert )    {
            return self::insertGetId([
                'type'=>$type
            ]);
        }
    }
}
