<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Status;
use App\Models\GoalPlanType;
use Illuminate\Validation\ValidationException;

class GoalPlan extends BaseModel
{
    use HasFactory;
    protected $guarded = [];
    const CONFIG_PROGRAM_USES_GOAL_TRACKER = 1;

    public function goalPlanType()
    {
        return $this->belongsTo(GoalPlanType::class, 'goal_plan_type_id');
    }
    public function status()    {
        return $this->belongsTo(Status::class,'state_type_id');
    }
    public static function getStatusByName( $status ) {
        return self::getByNameAndContext($status, 'Goals');
    }   
    
    public static function getActiveStatusId() {
        $status = self::getByNameAndContext('Active', 'Goals');
        if( $status->exists()) return  $status->id;
        return null;
    }
    public static function getFutureStatusId() {
        $status = self::getByNameAndContext('Future', 'Goals');
        if( $status->exists()) return  $status->id;
        return null;
    }
    public static function getExpiredStatusId() {
        $status = self::getByNameAndContext('Expired', 'Goals');
        if( $status->exists()) return  $status->id;
        return null;
    }
    public static function calculateStatusId($date_begin, $date_end) {
        $today = today()->format('Y-m-d');
        if($date_end < $today)
            $status_id = self::getExpiredStatusId();
       if($date_begin <= $today && $date_end >= $today)
            $status_id = self::getActiveStatusId();
        if($date_begin > $today)
            $status_id = self::getFutureStatusId();
        return $status_id;
    }
    public function user_goals()
    {
        return $this->hasMany(UserGoal::class);
    }
}