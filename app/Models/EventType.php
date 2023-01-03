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
    const EVENT_TYPE_STANDARD = 'Standard';
    const EVENT_TYPE_BADGE = 'Badge';

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
        return $this->type == config('global.event_type_peer2peer');
    }

    public function isEventTypeBadge(): bool
    {
        return $this->type == config('global.event_type_badge');
    }

    public function isEventTypePeer2PeerBadge(): bool
    {
        return $this->type == config('global.event_type_peer2peer_badge');
    }

    public function isEventTypePeer2PeerAllocation(): bool
    {
        return $this->type == self::getEventTypePeer2PeerAllocation();
    }

    public static function getEventTypePeer2PeerAllocation(): string
    {
        return config('global.event_type_peer2peer_allocation');
    }

    public static function getEventTypeIdPeer2PeerAllocation(): int
    {
        return self::getIdByType(self::getEventTypePeer2PeerAllocation());
    }
    public static function getIdByName( $name)   {
        $row = self::where('name', $name)->first();
        $id = $row->id ?? null;
        return $id;
    }    

    public static function getIdByTypeStandard( $insert = false)   {
        return self::getIdByName(self::EVENT_TYPE_STANDARD);
    } 
    public static function getIdByTypeBadge( $insert = false)   {
        return self::getIdByName(self::EVENT_TYPE_BADGE);
    }   

}
