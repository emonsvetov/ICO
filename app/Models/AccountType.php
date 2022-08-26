<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    protected $guarded = [];
    const ACCOUNT_TYPE_MONIES_FEES = 'Monies Fees';
    const ACCOUNT_TYPE_MONIES_DUE_TO_OWNER = 'Monies Due to Owner';
    const ACCOUNT_TYPE_MONIES_AVAILABLE = 'Monies Available';
    const ACCOUNT_TYPE_MONIES_SHARED = 'Monies Shared';
    const ACCOUNT_TYPE_INTERNAL_STORE_POINTS = 'Award Internal Store Points';
    const ACCOUNT_TYPE_PROMOTIONAL_POINTS = 'Award Promotional Points';
    const ACCOUNT_TYPE_POINTS_REDEEMED = 'Points Redeemed';
    const ACCOUNT_TYPE_MONIES_REDEEMED = 'Monies Redeemed';

    public static function getIdByName( $name, $insert = false ) {
        $first = self::where('name', $name)->first();
        if( $first) return $first->id;
        if( $insert )    {
            return self::insertGetId([
                'name'=>$name
            ]);
        }
    }
}
