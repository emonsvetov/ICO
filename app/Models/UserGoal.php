<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\GoalPlanType;
use App\Models\GoalPlanModel;
use DateTime;

class UserGoal extends Model
{
	/*protected $fillable = [
		'goal_plan_id',
	];*/
	protected $guarded = [];
    use HasFactory;
    /** create()
	 *
	 * This function creates a user goal given a program and user goal object
	 *
	 * @param int $program_account_holder_id        
	 * @param UserGoalObject $event_object        
	 * @throws InvalidArgumentException If $program_account_holder_id is not an unsigned int > 0
	 * @throws InvalidArgumentException If $program_account_holder_id is not in our records
	 * @throws InvalidArgumentException If the passed UserGoalObject is not valid
	 * @throws RuntimeException If internal query fails
	 * @return int $goal_plan_id */
	public function add($user_goal,$goal_plan) {
		$response=[];
		$active_goal_plan_id = 0;
		$valid = true;
		
		$date_begin = new DateTime ( $user_goal['date_begin'] );
		$date_end = new DateTime ( $user_goal['date_end'] );
		if ($date_end < $date_begin) {
			//throw new InvalidArgumentException ( 'user_goal->date_begin cannot be less than user_goal->date_end' );
			$response['error']='date_begin cannot be less than date_end';
			return $response;
		} 
		unset($user_goal['date_begin']);
		unset($user_goal['date_end']);
		
		//$current_user_goal_plan = $this->read_user_goals_for_plan ( $program_account_holder_id, ( int ) $user_goal->user_account_holder_id, ( int ) $user_goal->goal_plan_id );

		$current_user_goal_plan = self::where(['user_id' =>$user_goal['user_id'], 'goal_plan_id' => $user_goal['goal_plan_id']])->first();
		if ($current_user_goal_plan) {
			$valid = false;
			$response['error']='User is already assigned to this goal plan';
			return $response;
			//throw new InvalidArgumentException ( 'User is already assigned to this goal plan' );
		}
		if ($goal_plan->goal_plan_type_id != GoalPlanType::getIdByTypeSales()) {
			// Force the factors to 0 so we don't have to check the goal plan type when we do the awarding
			$user_goal['factor_before'] = 0;
			$user_goal['factor_after'] = 0;
		}
		// set special expiration flags on the goal plan
		// $goal_plan->custom_expire_offset= isset($custom_expire_offset) ? $custom_expire_offset : null;
		// $goal_plan->custom_expire_units= isset($custom_expire_units) ? $custom_expire_units : null;
		// $goal_plan->annual_expire_month= isset($annual_expire_month) ? $annual_expire_month : null;
		// $goal_plan->annual_expire_day= isset($annual_expire_day) ? $annual_expire_day : null;
		// Store the active goal plan id for returning and updating later
		if($valid){
			try {
				$new_user_goal_plan = self::_insert ( $user_goal,$goal_plan );
				if(!empty($new_user_goal_plan)) {
					$response['new_user_goal_plan']=$new_user_goal_plan;
				}
				// If we just created a new user goal and the goal plan is recurring, go ahead and create the user's future goal too
				if ($goal_plan->is_recurring && isset ( $goal_plan->next_goal_id ) && $goal_plan->next_goal_id > 0) {
					// Create the user's future goal plan
					$future_goal_plan_id = self::create_future_goal ( $goal_plan, $new_user_goal_plan );
				}
			} catch ( Exception $x ) {
				$response['error'] = ["Failure to Create Goal Plan: " . $x->getMessage () . " @{$x->getFile()}:{$x->getLine()} {$x->getCode()}"];
				//throw new RuntimeException ( "Failure to Create Goal Plan: " . $x->getMessage () . " @{$x->getFile()}:{$x->getLine()} {$x->getCode()}", 500 );
			}
		}
		
		// now we return the ID back to the function caller
		// cause they might need it...
		
		return $response;
	
	}
	private function _insert($goal_plan, $user_goal) {
		// Create the user goal record$user_goal,$goal_plan
		if (! isset ( $user_goal['previous_user_goal_id'] ) || $user_goal['previous_user_goal_id'] < 1) {
			$user_goal['previous_user_goal_id'] = null;
		}
		if (! isset ( $user_goal['next_user_goal_id'] ) || $user_goal['next_user_goal_id'] < 1) {
			$user_goal['next_user_goal_id'] = null;
		}
		$new_user_goal_plan = self::create($user_goal);
		if(!empty($new_user_goal_plan)) {
			return $new_user_goal_plan;
		} else {
			return false;
		}
		// build the query to INSERT
		/*$sql = "
			INSERT INTO
				" . USER_GOALS_TBL . "
			SET
				`users_id`				= {$this->write_db->escape($user_goal->user_account_holder_id)},
				`goal_plan_id`			= {$this->write_db->escape($user_goal->goal_plan_id)},
				`target_value`				= {$this->write_db->escape((float)$user_goal->target_value)},
				`achieved_callback_id`		= {$this->write_db->escape($user_goal->achieved_callback_id)},
				`exceeded_callback_id`		= {$this->write_db->escape($user_goal->exceeded_callback_id)},
				`factor_before`				= {$this->write_db->escape($user_goal->factor_before)},
				`factor_after`				= {$this->write_db->escape($user_goal->factor_after)},
				`created_by`         		= {$this->write_db->escape($user_goal->created_by)},
				`modified_by`            = {$this->write_db->escape($user_goal->created_by)},
				`next_user_goal_id`            = {$this->write_db->escape($user_goal->next_user_goal_id)},
				`previous_user_goal_id`            = {$this->write_db->escape($user_goal->previous_user_goal_id)}
			";
		$this->write_db->query ( $sql );*/
		// check if we have insert 1 row, cause if we inserted less than 1, then that's wrong...
		// and even worst is that we inserted more than 1, cause clearly we are inserting 1 row...
		// also make sure that we have a inserted ID
		/*if ($this->write_db->affected_rows () != 1 || $this->write_db->insert_id () < 1) {
			throw new RuntimeException ( $sql . ' Internal query failed, please contact the API administrator', 500 );
		}
		// Store the active goal plan id for returning and updating later
		$user_goal_id = $this->write_db->insert_id ();
		return ( int ) $user_goal_id;*/
	
	}
	public function create_future_goal($goal_plan, $user_goal) {
		// set the new goal plan to begin when the previous one expires
		$active_goal_start = $user_goal->date_begin;
		$active_goal_end = $user_goal->date_end;
		//Read next goal plan
		$future_goal_plan = GoalPlanModel::where(['id' =>$goal_plan->next_goal_id])->first();

		$user_goal->date_begin = $future_goal_plan->date_begin;
		$user_goal->date_end = $future_goal_plan->date_end;
		// $state_future_id= (int)$this->state_types_model->get_goal_future_state();
		$active_goal_plan_id = ( int ) $goal_plan->id;
		$user_goal->goal_plan_id = $future_goal_plan->id;
		// Determine what properties of the user goal to use or the future goal
		if ($user_goal->target_value == $goal_plan->default_target) {
			$user_goal->target_value = $future_goal_plan->default_target;
		}
		if ($user_goal->factor_before == $goal_plan->factor_before) {
			$user_goal->factor_before = $future_goal_plan->factor_before;
		}
		if ($user_goal->factor_after == $goal_plan->factor_after) {
			$user_goal->factor_after = $future_goal_plan->factor_after;
		}
		// ------------------------
		// Create the Future Goal Plan
		$future_goal_plan = self::_insert ( $future_goal_plan, $user_goal );
		// Update the active goal plan's next goal id with the future goal plan id
		// build the query to INSERT an event then run it!
		$future_goal_plan_id = $future_goal_plan->id;
		//$leaderboard->update( $data );
		//$user_goal['next_user_goal_id']=$future_goal_plan_id;
		GoalPlanModel::where(['id'=>$user_goal->id])->update(['next_user_goal_id'=>$future_goal_plan_id]);
		/*$sql = "
            UPDATE
                " . USER_GOALS_TBL . " gp
            SET
                `next_user_goal_id` = {$future_goal_plan_id}
            WHERE
                `id` = {$user_goal->id}
        ";
		$this->write_db->query ( $sql );
		// check if we have insert 1 row, cause if we inserted less than 1, then that's wrong...
		// and even worst is that we inserted more than 1, cause clearly we are inserting 1 row...
		if ($this->write_db->affected_rows () < 0) {
			throw new RuntimeException ( 'Internal query failed, please contact the API administrator ' . $sql, 500 );
		}*/

		// Update the active goal plan's next goal id with the future goal plan id
		// build the query to INSERT an event then run it!
		GoalPlanModel::where(['id'=>$future_goal_plan_id])->update(['previous_user_goal_id'=>$user_goal->id]);
		/*$sql = "
            UPDATE
                " . USER_GOALS_TBL . " gp
            SET
                `previous_user_goal_id` = {$user_goal->id}
            WHERE
                `id` = {$future_goal_plan_id}
        ";
		$this->write_db->query ( $sql );
		// check if we have insert 1 row, cause if we inserted less than 1, then that's wrong...
		// and even worst is that we inserted more than 1, cause clearly we are inserting 1 row...
		if ($this->write_db->affected_rows () < 0) {
			throw new RuntimeException ( 'Internal query failed, please contact the API administrator ' . $sql, 500 );
		}*/
		return $future_goal_plan_id;
	
	}
	public function goal_plans()
    {
        return $this->belongsToMany(GoalPlan::class);
    }
}
