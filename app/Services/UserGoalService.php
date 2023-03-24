<?php
namespace App\Services;

use Illuminate\Database\Query\Builder;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
use App\Models\User;
use App\Models\UserGoal;
use App\Models\GoalPlanType;
Use Exception;
use DB;
use DateTime;

class UserGoalService
{
    public static function create($goalPlan, $userGoal) {
		$response=[];
		/* TO DO
		// Make sure the program allows peer 2 peer
		$uses_goal_tracker_config = $this->programs_config_fields_model->read_config_field_by_name ( $program_account_holder_id, CONFIG_PROGRAM_USES_GOAL_TRACKER );
		if (! $uses_goal_tracker_config->value) {
			throw new UnexpectedValueException ( 'This program does allow goal plans', 400 );
		}
		*/
		$dateBegin = new DateTime ( $userGoal['date_begin'] );
		$dateEnd = new DateTime ( $userGoal['date_end'] );
		unset($userGoal['date_begin']);
		unset($userGoal['date_end']);
		if ($dateEnd < $dateBegin) {
			//$response['error']='Date begin cannot be less than Date end';
			//return $response;
			throw new Exception('Date begin cannot be less than Date end');
		}

		$currentUserGoalPlan = UserGoal::where(['user_id' =>$userGoal['user_id'], 'goal_plan_id' => $userGoal['goal_plan_id']])->first();
		//pr($currentUserGoalPlan); die;
		if ($currentUserGoalPlan) {
			//User is already assigned to this goal plan;
			$response['already_assigned']=1;
		}
		if(!isset($response['already_assigned'])) {
			if ($goalPlan->goal_plan_type_id != GoalPlanType::getIdByTypeSales()) {
				// Force the factors to 0 so we don't have to check the goal plan type when we do the awarding
				$userGoal['factor_before'] = 0;
				$userGoal['factor_after'] = 0;
			}
			$newUserGoalPlan = self::_insert($userGoal);
			if(!$newUserGoalPlan) {
				return false;
			}
			$response['user_goal_plan']=$newUserGoalPlan;
		} else {
			$newUserGoalPlan = $currentUserGoalPlan;
		}
		// If we just created a new user goal and the goal plan is recurring, go ahead and create the user's future goal too
		if ($goalPlan->is_recurring && $goalPlan->next_goal_id) {
			// Create the user's future goal plan

			$futureUserGoal = self::createFutureGoal( $goalPlan, self::_convertUserGoalData($newUserGoalPlan));
			$response['future_user_goal']= $futureUserGoal;
		}
		// now we return the response back to the function caller*/
		return $response;
	}
	private static function _insert($userGoal) {
		//Create the user goal record $userGoal,$goalPlan
		if (! isset ( $userGoal['previous_user_goal_id'] ) || $userGoal['previous_user_goal_id'] < 1) {
			$userGoal['previous_user_goal_id'] = null;
		}
		if (! isset ( $userGoal['next_user_goal_id'] ) || $userGoal['next_user_goal_id'] < 1) {
			$userGoal['next_user_goal_id'] = null;
		}
		$newUserGoal = UserGoal::create($userGoal);
		return $newUserGoal;
	}
	//Aliase for create_future_goal - TO DO - Fix $userGoal
	public static function createFutureGoal($goalPlan, $userGoal) {
		// set the new goal plan to begin when the previous one expires
		//Read next goal plan
		$futureGoalPlan = GoalPlan::where(['id' =>$goalPlan->next_goal_id])->first();

		if(empty($futureGoalPlan))
		return false;
		
		$futureUserGoalPlan = $userGoal;

		//$futureUserGoalPlan['date_begin'] = $futureGoalPlan->date_begin;
		//$futureUserGoalPlan['date_end'] = $futureGoalPlan->date_end;

		$existingUserGoalPlan = UserGoal::where(['user_id' =>$userGoal['user_id'], 'goal_plan_id' => $futureGoalPlan->id])->first();
		
		if ($existingUserGoalPlan) {
			//User is already assigned to this goal plan
			return false;
		}
		unset($futureUserGoalPlan['id']);

		$futureUserGoalPlan['goal_plan_id'] = $futureGoalPlan->id;

		// Determine what properties of the user goal to use or the future goal
		if ($userGoal['target_value'] == $goalPlan->default_target) {
			$futureUserGoalPlan['target_value'] = $futureGoalPlan->default_target;
		}
		if ($userGoal['factor_before'] == $goalPlan->factor_before) {
 			$futureUserGoalPlan['factor_before'] = $futureGoalPlan->factor_before;
		}
		if ($userGoal['factor_after'] == $goalPlan->factor_after) {
			$futureUserGoalPlan['factor_after'] = $futureGoalPlan->factor_after;
		}
		// Create the Future Goal Plan
		$futureUserGoal = self::_insert($futureUserGoalPlan);
		if(!$futureUserGoal) {
			return false; //if no future goal plan created then return here. No need of update goal plan model updates below.
		}
		// Update the active goal plan's next goal id with the future goal plan id
		$futureUserGoalId = $futureUserGoal->id;
		UserGoal::where(['id'=>$userGoal['id']])->update(['next_user_goal_id'=>$futureUserGoalId]);
		// Update the future user goal plan's previous user goal with the previous user goal id
		UserGoal::where(['id'=>$futureUserGoalId])->update(['previous_user_goal_id'=>$userGoal['id']]);
		return $futureUserGoal;
	}
	public static function _copyUserGoalDataFromGoalPlan($goalPlan) {
		$data=[];
		$data['goal_plan_id'] =  $goalPlan->id;
		$data['target_value'] = $goalPlan->default_target;
		$data['date_begin'] = $goalPlan->date_begin;
		$data['date_end'] = $goalPlan->date_end;
		$data['factor_before'] = $goalPlan->factor_before;
		$data['factor_after'] = $goalPlan->factor_after;
		$data['created_by'] =  auth()->user()->id; 
		$data['achieved_callback_id'] = $goalPlan->achieved_callback_id;
		$data['exceeded_callback_id'] = $goalPlan->exceeded_callback_id;
		return $data;
	}
	public static function _convertUserGoalData($userGoal) {
		$data=[];
		$data['id'] =  $userGoal->id;
		$data['user_id'] =  $userGoal->user_id;
		$data['goal_plan_id'] =  $userGoal->goal_plan_id;
		$data['next_user_goal_id'] =  $userGoal->next_user_goal_id;
		$data['previous_user_goal_id'] =  $userGoal->previous_user_goal_id;
		$data['target_value'] = $userGoal->target_value;
		$data['factor_before'] = $userGoal->factor_before;
		$data['factor_after'] = $userGoal->factor_after;
		$data['created_by'] =   $userGoal->created_by;
		$data['modified_by'] =   $userGoal->created_by;
		$data['achieved_callback_id'] = $userGoal->achieved_callback_id;
		$data['exceeded_callback_id'] = $userGoal->exceeded_callback_id;
		return $data;
	}
	public function createUserGoalPlans($organization,$program, $data) {
		$userIds = $data['user_id'] ?? [];

        if( sizeof($userIds) <=0 )
        {
            throw new InvalidArgumentException ( 'Invalid or no participants (s) selected', 400 );
        }
		// Read the program's goal plan, then copy over the necessary values
		$goalPlan = GoalPlan::getGoalPlan( $data['goal_plan_id']);
		//goalPlan = GoalPlan::getGoalPlan( $data['goal_plan_id'], $program->id);
		//TO DO - getGoalPlan - currently it is reading by goal plan id, pending to create read() function for goalplan for fecthing goalplan by id and program id both with other conditions
		//$goalPlan = $program_goal; //TO DO - fix
		//assets/js/manager/dialog-add-goal.js?v=1569381162),
		// Copy the submitted info into the user's goal plan object
		$userGoalPlan=[];
		$userGoalPlan['goal_plan_id'] = $goalPlan->id;
		$userGoalPlan['target_value'] = $data['target_value'];
		$userGoalPlan['date_begin'] = $data['date_begin'];
		$userGoalPlan['date_end'] = $data['date_end'];
		$userGoalPlan['factor_before'] = $data['factor_before'];
		$userGoalPlan['factor_after'] = $data['factor_after'];
		$userGoalPlan['created_by'] = auth()->user()->id;
		$userGoalPlan['achieved_callback_id'] = isset($data['achieved_callback_id']) ? $data['achieved_callback_id']: null;
		$userGoalPlan['exceeded_callback_id'] = isset($data['exceeded_callback_id']) ? $data['exceeded_callback_id']: null;
		$successUser=$failUser=$successFutureUser=$failFutureUser=$alreadyAssigned=$addedInfo=[];
		if(!empty($userIds)) { 
			$users = User::whereIn('id', $userIds)->get();
			foreach($users as $user) {
				$user_id = $user->id;
				//check for duplicates
				if(!empty($addedInfo)) {
					foreach($addedInfo as $val){
						if($val['goal_plan_id']==$goalPlan->id && $val['users_id']==$user_id){
							continue 2; //if already added then continue outer users loop
						}  
					}
				}
				$userGoalPlan['user_id'] = $user_id;
				//create
				$response = UserGoalService::create($goalPlan, $userGoalPlan);
				if(isset($response['already_assigned'])) {
					$alreadyAssigned[] =$user->email;
					continue;
				}
				if(!$response || !isset($response['user_goal_plan'])) {
					$failUser[]=$user->email;
				} else if(isset($response['user_goal_plan'])) {
					$addedInfo[]=array("goal_plan_id"=>$goalPlan->id,"users_id"=>$user_id);
					$successUser[]=$user->email;
				}
				//$goalPlan is_recurring
				if(isset($response['future_user_goal'])) { 
					if($response['future_user_goal'])
						$successFutureUser[]=$user->email;
					else
						$failFutureUser[]=$user->email;
				} //else no need of future goal	
			}	
		}
		//create response 
		$response['success_users']=$successUser;
		$response['fail_users']=$failUser; 
		$response['already_assigned']=$alreadyAssigned;
		return ['message'=>self::createUserGoalRes($response)];
	}

	public function createUserGoalRes($response) {
		$msg='';
		if(!empty($response['already_assigned'])) {
			$msg .= "Already existing goal plan for selected user(s):".(implode(",",$response['already_assigned'])). " \n";
		}
		if(!empty($response['success_users'])) {
			$msg.= "Successfully created goal plan for ".count($response['success_users'])." user(s). \n";
		}
		if(!empty($response['fail_users'])) {
			$msg .= "Failed to create goal plan for user(s):".(impload(",",$response['fail_users'])). ".\n";
		}
		return $msg;
	}

	private static function _selectUserGoalInfo() {
		$query = GoalPlan::from( 'goal_plans as gp' )
		->select([
			'gp.id as goal_plan_id',
			'gp.program_id',
			'gp.name as plan_name',
			'gp.email_template_id',
			'gp.notification_body',
			'gp.created_by as plan_created_by',
			'gp.created_at as plan_created',
			'gp.goal_plan_type_id',
			'gt.name as goal_plan_type_name',
			'ug.id as id',
			'ug.next_user_goal_id as next_user_goal_id',
			'ug.previous_user_goal_id as previous_user_goal_id',
			'ug.user_id',
			'ug.achieved_callback_id',
			'ug.exceeded_callback_id',
			'gp.achieved_event_id',
			'ae.name as achieved_event_name',
			'ae.event_icon_id as achieved_event_icon',
			'gp.exceeded_event_id',
			'ee.name as  exceeded_event_name',
			'ee.event_icon_id as exceeded_event_icon',
			'ug.target_value',
			'gp.goal_measurement_label',
			'ug.calc_progress_total',
			'ug.calc_progress_percentage',
			'ug.factor_before',
			'gp.state_type_id',
			'st.status as goal_state_name',
			'gp.is_recurring',
			'gp.award_per_progress',
			'gp.progress_requires_unique_ref_num',
			'ug.factor_after',
			'gp.date_begin',
			'gp.date_end',
			'ug.date_met',
			'ug.created_at',
			'ug.created_by',
			'ug.updated_at',
			'ug.modified_by',
			'ug.iterations'
		])
		->join('goal_plan_types AS gt', 'gt.id', '=', 'gp.goal_plan_type_id')
		->join('user_goals AS ug', 'ug.goal_plan_id', '=', 'gp.id')
		->join('statuses AS st', 'st.id', '=', 'gp.state_type_id') 
		->join('events AS ae', 'ae.id', '=', 'gp.achieved_event_id')
		->leftJoin('events AS ee', 'ee.id', '=', 'gp.exceeded_event_id');
		return $query;
	}

	/* Alias for read_list_by_program_and_goal() */ 
	public function readListByProgramAndGoal($programId = 0, $goal_plan_id = 0, $offset = 0, $limit = 10, $order_column = 'name', $order_direction = 'asc') {
		$allowedColumns = array (
				'name' 
		);
		// make sure $order_column is allowed column
		if (! in_array ( $order_column, $allowedColumns )) {
			throw new \UnexpectedValueException ( 'Invalid "order_column" passed, column value is not allowed', 400 );
		}
		// make sure that the $order_direction is uppercase
		// cause we want it to be standard SQL :)
		$order_direction = strtoupper ( $order_direction );
		// make sure that $order_direction is either ASC or DESC, for the same reason we checked the $order_column
		if ($order_direction != 'ASC' && $order_direction != 'DESC') {
			throw new \InvalidArgumentException ( 'Invalid "order_direction" passed, must be either "ASC" or "DESC"', 400 );
		}
		// build and run the query
		$query = self::_selectUserGoalInfo();
		$query->where('gp.program_id', '=', $programId);
		$query->where('goal_plan_id', '=', $goal_plan_id);
		$query->limit($limit)->offset($offset);
		$query->orderBy('gp.'.$order_column,$order_direction);
		try {
            $result = $query->get();
            return $result;
        } catch (Exception $e) {
            throw new Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
	}
}
