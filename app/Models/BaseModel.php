<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\GetModelByMixed;
use App\Models\Status;

class BaseModel extends Model
{
    use GetModelByMixed;
    
    public static function getIdByName( $name, $insert = false ) {
        $row = self::where('name', $name)->first();
        $id = $row->id ?? null;

        if( !$id && $insert)    {
            $id = self::insertGetId([
                'name'=>$name
            ]);
        }
        return $id;
    }

    public function getByNameAndContext( $name, $context ) {
        return Status::getByNameAndContext($name, $context);
    }
}
