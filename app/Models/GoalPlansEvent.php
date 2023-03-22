<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalPlansEvent extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    
    public function goalPlan()
    {
        return $this->belongsTo(GoalPlan::class, 'goal_plans_id');
    }

    public function event()
    {
        return $this->belongsTo(GoalPlan::class, 'event_id');
    }

}