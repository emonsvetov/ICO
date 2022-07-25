<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use App\Models\Status;


class Leaderboard extends BaseModel
{
    use SoftDeletes;

    protected $guarded = [];

    public function getStatusByName( $status ) {
        return self::getByNameAndContext($status, 'Leaderboards');
    }   
    
    public function getActiveStatusId() {
        $status = self::getByNameAndContext('Active', 'Leaderboards');
        if( $status->exists()) return  $status->id;
        return null;
    }

    public function getInactiveStatusId() {
        $status = self::getByNameAndContext('Deactivated', 'Leaderboards');
        if( $status->exists()) return  $status->id;
        return null;
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'leaderboard_event');
    }

    public function status()    {
        return $this->belongsTo(Status::class);
    }
}
