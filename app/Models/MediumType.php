<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediumType extends Model
{

    protected $guarded = [];

    public function getIdByName( $name, $insert = false ) {
        $id = self::where('name', $name)->first()->id;
        if( !$id && $insert)    {
            $id = self::insertGetId([
                'name'=>$namerg
            ]);
        }
        return $id;
    }
}
