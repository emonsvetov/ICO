<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\GetModelByMixed;
use App\Models\Status;

class BaseModel extends Model
{
    use GetModelByMixed;

    public static function getIdByField( $field, $value, $insert = false ) {
        $row = self::where($field, $value)->first();
        $id = $row->id ?? null;

        if( !$id && $insert)    {
            $id = self::insertGetId([
                $field=>$value
            ]);
        }
        return $id;
    }
    public static function getIdByName( $value, $insert = false ) : ?int {
        return self::getIdByField('name', $value, $insert);
    }
    public static function getIdByType( $value, $insert = false) : ?int {
        return self::getIdByField('type', $value, $insert);
    }
    // Depricated; use "getStatusByNameAndContext" instead
    public static function getByNameAndContext( $name, $context ) {
        return Status::getByNameAndContext($name, $context);
    }
    public static function getStatusByNameAndContext( $name, $context ) {
        return Status::getByNameAndContext($name, $context);
    }
}
