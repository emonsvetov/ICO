<?php
namespace App\Services;

use Illuminate\Database\Query\Builder;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
use App\Services\ProgramService;
use App\Models\User;
use App\Models\UserGoal;
use App\Models\GoalPlanType;
use DB;
//use App\Services\EmailTemplateService;
use DateTime;

class UserGoalService
{

    public function __construct(
        ProgramService $programService
        )
	{
        $this->programService = $programService;
    }
    public static function create($goal_plan, $user_goal) {
		$response=[];
		/* TO DO
		// Make sure the program allows peer 2 peer
		$uses_goal_tracker_config = $this->programs_config_fields_model->read_config_field_by_name ( $program_account_holder_id, CONFIG_PROGRAM_USES_GOAL_TRACKER );
		if (! $uses_goal_tracker_config->value) {
			throw new UnexpectedValueException ( 'This program does allow goal plans', 400 );
		}
		*/
		/*$date_begin = new DateTime ( $user_goal['date_begin'] );
		$date_end = new DateTime ( $user_goal['date_end'] );
		if ($date_end < $date_begin) {
			$response['error']='Date begin cannot be less than Date end';
			return $response;
		}
		*/

		$current_user_goal_plan = UserGoal::where(['user_id' =>$user_goal['user_id'], 'goal_plan_id' => $user_goal['goal_plan_id']])->first();
		//pr($current_user_goal_plan); die;
		if ($current_user_goal_plan) {
			//User is already assigned to this goal plan;
			$response['already_assigned']=1;
		}
		if(!isset($response['already_assigned'])) {
			if ($goal_plan->goal_plan_type_id != GoalPlanType::getIdByTypeSales()) {
				// Force the factors to 0 so we don't have to check the goal plan type when we do the awarding
				$user_goal['factor_before'] = 0;
				$user_goal['factor_after'] = 0;
			}
			$new_user_goal_plan = self::_insert($user_goal);
			if(!$new_user_goal_plan) {
				return false;
			}
			$response['user_goal_plan']=$new_user_goal_plan;
		} else {
			$new_user_goal_plan = $current_user_goal_plan;
		}
		// If we just created a new user goal and the goal plan is recurring, go ahead and create the user's future goal too
		if ($goal_plan->is_recurring && $goal_plan->next_goal_id) {
			// Create the user's future goal plan
			$future_user_goal = self::create_future_goal( $goal_plan, $new_user_goal_plan);
			$response['future_user_goal']= $future_user_goal;
		}
		// now we return the response back to the function caller*/
		return $response;
	}
	private static function _insert($user_goal) {
		//Create the user goal record $user_goal,$goal_plan
		if (! isset ( $user_goal['previous_user_goal_id'] ) || $user_goal['previous_user_goal_id'] < 1) {
			$user_goal['previous_user_goal_id'] = null;
		}
		if (! isset ( $user_goal['next_user_goal_id'] ) || $user_goal['next_user_goal_id'] < 1) {
			$user_goal['next_user_goal_id'] = null;
		}
		$new_user_goal = UserGoal::create($user_goal);
		return $new_user_goal;
	}
	public static function create_future_goal($goal_plan, $user_goal) {
		// set the new goal plan to begin when the previous one expires
		//Read next goal plan
		$future_goal_plan = GoalPlan::where(['id' =>$goal_plan->next_goal_id])->first();
		if(empty($future_goal_plan))
		return false;

		$existing_user_goal_plan = UserGoal::where(['user_id' =>$user_goal->user_id, 'goal_plan_id' => $future_goal_plan->id])->first();
		
		if ($existing_user_goal_plan) {
			//User is already assigned to this goal plan
			return false;
		}
		$future_ugp = $user_goal->toArray();
		unset($future_ugp['id']);
		$future_ugp['goal_plan_id'] = $future_goal_plan->id;
		// Determine what properties of the user goal to use or the future goal
		if ($user_goal->target_value == $goal_plan->default_target) {
			$future_ugp['target_value'] = $future_goal_plan->default_target;
		}
		if ($user_goal->factor_before == $goal_plan->factor_before) {
 			$future_ugp['factor_before'] = $future_goal_plan->factor_before;
		}
		if ($user_goal->factor_after == $goal_plan->factor_after) {
			$future_ugp['factor_after'] = $future_goal_plan->factor_after;
		}
		// Create the Future Goal Plan
		$future_user_goal = self::_insert($future_ugp);
		if(!$future_user_goal) {
			return false; //if no future goal plan created then return here. No need of update goal plan model updates below.
		}
		// Update the active goal plan's next goal id with the future goal plan id
		$future_user_goal_id = $future_user_goal->id;
		UserGoal::where(['id'=>$user_goal->id])->update(['next_user_goal_id'=>$future_user_goal_id]);
		// Update the future user goal plan's previous user goal with the previous user goal id
		UserGoal::where(['id'=>$future_user_goal_id])->update(['previous_user_goal_id'=>$user_goal->id]);
		return $future_user_goal;
	}
}
