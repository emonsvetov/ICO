<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\GoalPlanType;
use App\Models\GoalPlanModel;

class UserGoal extends Model
{
	/*protected $fillable = [
		'goal_plan_id',
	];*/
	protected $guarded = [];
    use HasFactory;
	
	public function goal_plans()
    {
        return $this->belongsToMany(GoalPlan::class);
    }
}
