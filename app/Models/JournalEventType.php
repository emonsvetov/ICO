<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class JournalEventType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function getIdByType($type)
    {
        return self::where('type', $type)->first()->id;
    }

    public static function getTypeAllocatePeerPoints(): int
    {
        return (int)self::getIdByType(config('global.journal_event_type_allocate_peer_points'));
    }
}
