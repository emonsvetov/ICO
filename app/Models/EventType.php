<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    protected $guarded = [];
    public function getIsPeer2peerAttribute()   {
        return $this->type == config('global.event_type_peer2peer');
    }
}
