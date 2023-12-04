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

    const ET_BADGE = 5;

    const EVENT_TYPE_BADGE = 'badge';
    const EVENT_TYPE_PEER2PEER_BADGE = 'peer2peer badge';
    const EVENT_TYPE_STANDARD = 'standard';
    const event_type_activation = 'activation';
    const EVENT_TYPE_PEER2PEER = 'peer2peer';
    const EVENT_TYPE_PEER2PEER_ALLOCATION = 'peer2peer allocation';
    const EVENT_TYPE_PROMOTIONAL_AWARD = 'promotional award';
    const EVENT_TYPE_AUTO_AWARD = 'auto award';
    const EVENT_TYPE_MILESTONE_AWARD = 'milestone award';
    const EVENT_TYPE_MILESTONE_BADGE = 'milestone badge';
    const EVENT_TYPE_BIRTHDAY_AWARD = 'birthday award';
    const EVENT_TYPE_BIRTHDAY_BADGE = 'birthday badge';
    const EVENT_TYPE_PROGRAM_PAYS_FOR_MONIES_PENDING = 'Program pays for monies pending';
    const EVENT_TYPE_PROGRAM_TRANSFERS_MONIES_AVAILABLE = 'Program transfers monies available';
    const EVENT_TYPE_RECLAIM_MONIES = 'Reclaim monies';
    const EVENT_TYPE_AWARD_MONIES_TO_RECIPIENT = 'Award monies to recipient';
    const EVENT_TYPE_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING = 'Reversal program pays for monies pending';
    const EVENT_TYPE_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE = 'Reversal program pays for deposit fee';


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
    public function isEventTypeMilestoneAward(): bool
    {
        return $this->type == self::getEventTypeMilestoneAward();
    }
    public function isEventTypeMilestoneBadge(): bool
    {
        return $this->type == self::getEventTypeMilestoneBadge();
    }
    public function isEventTypeBirthdayAward(): bool
    {
        return $this->type == self::getEventTypeBirthdayAward();
    }
    public function isEventTypeBirthdayBadge(): bool
    {
        return $this->type == self::getEventTypeBirthdayBadge();
    }
    public function isEventTypeAutoAward(): bool
    {
        return $this->type == self::EVENT_TYPE_AUTO_AWARD;
    }

    public static function getEventTypePeer2PeerAllocation(): string
    {
        return self::EVENT_TYPE_PEER2PEER_ALLOCATION;
    }

    public static function getEventTypeMilestoneAward(): string
    {
        return self::EVENT_TYPE_MILESTONE_AWARD;
    }

    public static function getEventTypeMilestoneBadge(): string
    {
        return self::EVENT_TYPE_MILESTONE_BADGE;
    }

    public static function getEventTypeBirthdayAward(): string
    {
        return self::EVENT_TYPE_BIRTHDAY_AWARD;
    }

    public static function getEventTypeBirthdayBadge(): string
    {
        return self::EVENT_TYPE_BIRTHDAY_BADGE;
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
