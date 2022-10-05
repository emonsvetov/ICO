<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceType extends Model
{

    protected $guarded = [];

    public static function getIdByName( $name, $insert = false ) {
        $id = self::where('name', $name)->first()->id;
        if( !$id && $insert)    {
            $id = self::insertGetId([
                'name'=>$name
            ]);
        }
        return $id;
    }

    public static function getTypeLiability(): int
    {
        return (int)self::getIdByName(config('global.finance_type_liability'), true);
    }

    public static function getTypeAsset(): int
    {
        return (int)self::getIdByName(config('global.finance_type_asset'), true);
    }

}
