<?php

namespace App\Models;

use App\Models\BaseModel;

class FinanceType extends BaseModel
{
    protected $guarded = [];

    const FINANCE_TYPE_LIABILITY = 'Liability';
    const FINANCE_TYPE_ASSET = 'Asset';
    const FINANCE_TYPE_MONIES = 'Monies';
    const FINANCE_TYPE_REVENUE = 'Monies';

    public static function getTypeLiability(): int
    {
        return (int)self::getIdByName(self::FINANCE_TYPE_LIABILITY, true);
    }

    public static function getTypeAsset(): int
    {
        return (int)self::getIdByName(self::FINANCE_TYPE_ASSET, true);
    }
    public static function getIdByTypeLiability(): int
    {
        return (int)self::getIdByName(self::FINANCE_TYPE_LIABILITY, true);
    }
    public static function getIdByTypeAsset(): int
    {
        return (int)self::getIdByName(self::FINANCE_TYPE_ASSET, true);
    }
    public static function getIdByTypeMonies(): int
    {
        return (int)self::getIdByName(self::FINANCE_TYPE_MONIES, true);
    }
    public static function getIdByTypeRevenue(): int
    {
        return (int)self::getIdByName(self::FINANCE_TYPE_REVENUE, true);
    }
}
