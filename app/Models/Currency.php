<?php

namespace App\Models;

use App\Models\BaseModel;

class Currency extends BaseModel
{
    protected $guarded = [];

    public static function getIdByType( $type = 'USD', $insert = false ) {

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
