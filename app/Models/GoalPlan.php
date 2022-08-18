<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalPlan extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function goalPlanType()
    {
        return $this->belongsTo(GoalPlanType::class, 'goal_plan_type_id');
    }
}
