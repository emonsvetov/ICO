<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceType extends Model
{

    protected $guarded = [];

    public function getIdByName( $name, $insert = false ) {
        $id = self::where('name', $name)->first()->id;
        if( !$id && $insert)    {
            $id = self::insertGetId([
                'name'=>$name
            ]);
        }
        return $id;
    }
}
