<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Status;

class GoalPlan extends BaseModel
{
    use HasFactory;
    protected $guarded = [];

    public function goalPlanType()
    {
        return $this->belongsTo(GoalPlanType::class, 'goal_plan_type_id');
    }
    public function status()    {
        return $this->belongsTo(Status::class,'state_type_id');
    }
    public function getStatusByName( $status ) {
        return self::getByNameAndContext($status, 'Goals');
    }   
    
    public function getActiveStatusId() {
        $status = self::getByNameAndContext('Active', 'Goals');
        if( $status->exists()) return  $status->id;
        return null;
    }
    public function getFutureStatusId() {
        $status = self::getByNameAndContext('Future', 'Goals');
        if( $status->exists()) return  $status->id;
        return null;
    }
    public function getExpiredStatusId() {
        $status = self::getByNameAndContext('Expired', 'Goals');
        if( $status->exists()) return  $status->id;
        return null;
    }
    public function calculateStatusId($date_begin, $date_end) {
        $today = today()->format('Y-m-d');
        if($date_end < $today)
            $status_id = self::getExpiredStatusId();
       if($date_begin <= $today && $date_end >= $today)
            $status_id = self::getActiveStatusId();
        if($date_begin > $today)
            $status_id = self::getFutureStatusId();
        return $status_id;
    }
}