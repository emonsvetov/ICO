<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\GetModelByMixed;

class BaseModel extends Model
{
    use GetModelByMixed;
    
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
