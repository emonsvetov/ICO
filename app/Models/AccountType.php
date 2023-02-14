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
    const ACCOUNT_TYPE_POINTS_AVAILABLE = 'Points Available';
    const ACCOUNT_TYPE_POINTS_AWARDED = 'Points Awarded';
    const ACCOUNT_TYPE_MONIES_AWARDED = 'Monies Awarded';
    const ACCOUNT_TYPE_PEER2PEER_POINTS = 'Peer to Peer Points';

    public static function getIdByName( $name, $insert = false ) {
        $first = self::where('name', $name)->first();
        if( $first) return $first->id;
        if( $insert )    {
            return self::insertGetId([
                'name'=>$name
            ]);
        }
    }

    public static function getTypeIdPeer2PeerPoints(): int
    {
        return (int)self::getIdByName(self::ACCOUNT_TYPE_PEER2PEER_POINTS, true);
    }

    public static function getTypePeer2PeerPoints(): string
    {
        return self::ACCOUNT_TYPE_PEER2PEER_POINTS;
    }

    public static function getTypePointsAwarded(): string
    {
        return self::ACCOUNT_TYPE_POINTS_AWARDED;
    }

    public static function getTypeMoniesAwarded(): string
    {
        return self::ACCOUNT_TYPE_MONIES_AWARDED;
    }
}
