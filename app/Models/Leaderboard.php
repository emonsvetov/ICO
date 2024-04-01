<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use App\Models\Status;


class Leaderboard extends BaseModel
{
    use SoftDeletes;
    public $timestamps = true;

    const LEADERBOARD_STATE_DELETED = 'Deleted';
    const LEADERBOARD_STATE_DEACTIVATED = 'Deactivated';

    protected $guarded = [];

    public function getStatusByName( $status ) {
        return self::getByNameAndContext($status, 'Leaderboards');
    }

    public static function getActiveStatusId() {
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
        return $this->belongsToMany(Event::class, 'leaderboard_event')->withTimestamps();
    }

    public function goalPlans()
    {
        return $this->belongsToMany(GoalPlan::class, 'leaderboard_goal');
    }

    public function status()    {
        return $this->belongsTo(Status::class);
    }
    public function leaderboard_type()
    {
        return $this->belongsTo(LeaderboardType::class, 'leaderboard_type_id');
    }

    public function journal_events()
    {
        return $this->belongsToMany(JournalEvent::class, 'leaderboard_journal_event')->withTimestamps();
    }

    public function leaderboard_journal_event()
    {
        return $this->hasMany(LeaderboardJournalEvent::class, 'leaderboard_id', 'id');
    }

    public static function getByProgramId($programId)
    {
        return self::where('program_id', $programId)->get();
    }

}
