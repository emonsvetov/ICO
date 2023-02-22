<?php

namespace App\Models;

use App\Models\BaseModel;

class MediumType extends BaseModel
{
    protected $guarded = [];

    const MEDIUM_TYPE_POINTS = "Points";
    const MEDIUM_TYPE_MONIES = "Monies";
    const MEDIUM_TYPE_GIFTCODES = "Gift Codes";

    public static function getTypePoints(): int
    {
        return (int)self::getIdByName(self::MEDIUM_TYPE_POINTS, true);
    }

    public static function getTypeMonies(): int
    {
        return (int)self::getIdByName(self::MEDIUM_TYPE_MONIES, true);
    }

    public static function getTypeGiftCodes(): int
    {
        return (int)self::getIdByName(self::MEDIUM_TYPE_GIFTCODES, true);
    }

    public static function getIdByTypePoints(): int
    {
        return (int)self::getIdByName(self::MEDIUM_TYPE_POINTS, true);
    }    
    
    public static function getIdByTypeMonies(): int
    {
        return (int)self::getIdByName(self::MEDIUM_TYPE_MONIES, true);
    }
}
