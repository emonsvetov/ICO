<?php

namespace App\Models;

use App\Models\BaseModel;

class Currency extends BaseModel
{
    protected $guarded = [];

    const DEFAULT_CURRENCY = 'USD';

    public static function getDefault()
    {
        return self::getIdByType(self::DEFAULT_CURRENCY, true);
    }
}
