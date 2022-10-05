<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    protected $guarded = [];

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
        return (int)self::getIdByName(config('global.account_type_peer_to_peer_points'), true);
    }

    public static function getTypePeer2PeerPoints(): string
    {
        return config('global.account_type_peer_to_peer_points');
    }
}
