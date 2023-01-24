<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateType extends Model
{
    protected $guarded = [];
    const FIELD_NAME = 'type';

    public static function getIdByName( $name, $insert = false ) {
        $first = self::where(self::FIELD_NAME, $name)->first();
        if( $first) return $first->id;
        if( $insert )    {
            return self::insertGetId([
                self::FIELD_NAME => $name
            ]);
        }
    }
}
