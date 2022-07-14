<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public function getIdByName( $name, $insert = false ) {
        $id = self::where('name', $name)->first()->id;
        if( !$id && $insert)    {
            $id = self::insertGetId([
                'name'=>$name
            ]);
        }
        return $id;
    }

    public function getModelByMixed( $mixed )   {
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
