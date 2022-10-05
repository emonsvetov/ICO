<?php

namespace App\Models;

use App\Models\BaseModel;

class MediumType extends BaseModel
{
    protected $guarded = [];

    public static function getTypePoints(): int
    {
        return (int)self::getIdByName(config('global.medium_type_points'), true);
    }

    public static function getTypeMonies(): int
    {
        return (int)self::getIdByName(config('global.medium_type_monies'), true);
    }

    public static function getTypeGiftCodes(): int
    {
        return (int)self::getIdByName(config('global.medium_type_gift_codes'), true);
    }
}
