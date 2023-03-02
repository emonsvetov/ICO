<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted',
    ];

    const EVENT_TYPE_BADGE = 'badge';
    const EVENT_TYPE_PEER2PEER_BADGE = 'peer2peer badge';
    const EVENT_TYPE_STANDARD = 'standard';
    const event_type_activation = 'activation';
    const EVENT_TYPE_PEER2PEER = 'peer2peer';
    const EVENT_TYPE_PEER2PEER_ALLOCATION = 'peer2peer allocation';
    const EVENT_TYPE_PROMOTIONAL_AWARD = 'promotional award';
    const EVENT_TYPE_AUTO_AWARD = 'auto award';

    /**
     * @param string $type
     * @return int|null
     */
    public static function getIdByType(string $type): ?int
    {
        return (int)self::where('type', $type)->first()->id ?? null;
    }

    public function isEventTypePeer2Peer(): bool
    {
        return $this->type == self::EVENT_TYPE_PEER2PEER;
    }

    public function isEventTypeBadge(): bool
    {
        return $this->type == self::EVENT_TYPE_BADGE;
    }

    public function isEventTypePeer2PeerBadge(): bool
    {
        return $this->type == self::EVENT_TYPE_PEER2PEER_BADGE;
    }

    public function isEventTypePeer2PeerAllocation(): bool
    {
        return $this->type == self::getEventTypePeer2PeerAllocation();
    }

    public function isEventTypeAutoAward(): bool
    {
        return $this->type == self::EVENT_TYPE_AUTO_AWARD;
    }

    public static function getEventTypePeer2PeerAllocation(): string
    {
        return self::EVENT_TYPE_PEER2PEER_ALLOCATION;
    }

    public static function getEventTypeIdPeer2PeerAllocation(): int
    {
        return self::getIdByType(self::getEventTypePeer2PeerAllocation());
    }

    public static function getIdByTypeStandard( $insert = false)   {
        return self::getIdByType(self::EVENT_TYPE_STANDARD);
    } 
    public static function getIdByTypeBadge( $insert = false)   {
        return self::getIdByType(self::EVENT_TYPE_BADGE);
    }
}
