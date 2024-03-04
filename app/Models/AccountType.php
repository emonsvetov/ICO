<?php

namespace App\Models;

use App\Models\BaseModel;

class AccountType extends BaseModel
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
    const ACCOUNT_TYPE_PEER2PEER_MONIES = 'Peer to Peer Monies';
    const ACCOUNT_TYPE_GIFT_CODES_AVAILABLE = 'Gift Codes Available';
    const ACCOUNT_RECLAIM_POINTS = 'Reclaim points';
    const ACCOUNT_AWARD_POINTS_RECIPIENT = 'Award points to recipient';
    const ACCOUNT_REDEEM_POINTS_GIFT_CODES = 'Redeem points for gift codes';
    const ACCOUNT_AWARD_MONIES_RECIPIENT = 'Award monies to recipient';
    const ACCOUNT_REDEEM_MONIES_GIFT_CODES = 'Redeem monies for gift codes';

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
