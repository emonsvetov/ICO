<?php
namespace App\Services;

use App\Services\Program\ReadCompiledInvoiceService;
use App\Services\Program\ReadInvoicePaymentsService;
use App\Services\Program\CreateInvoiceService;
use App\Http\Requests\GoalPlanRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
use App\Services\ProgramService;
use App\Models\User;
use App\Models\UserGoal;
use App\Models\GoalPlanType;
use DateTime;

class GoalPlanService 
{

    public function __construct(
        ProgramService $programService
        )
	{
        $this->programService = $programService;
    }
	public function add_goal_plan($data, $organization, $program)
    {
        //PENDING - not clear /git-clean/core-program/php_includes/application/controllers/manager/program_settings.php
		//CALLBACK_TYPE_GOAL_MET=Goal Met 
        /*$goal_met_program_callbacks = $this->external_callbacks_model->read_list_by_type((int) $this->program->account_holder_id, CALLBACK_TYPE_GOAL_MET);
        $goal_exceeded_program_callbacks = $this->external_callbacks_model->read_list_by_type((int) $this->program->account_holder_id, CALLBACK_TYPE_GOAL_EXCEEDED);
        $email_templates = $this->email_templates_model->read_list_program_email_templates_by_type((int) $this->program->account_holder_id, "Goal Progress", 0, 9999);
        $empty_callback = new stdClass();
        $empty_callback->id = 0;
        $empty_callback->name = $this->lang->line('txt_none');
        array_unshift($goal_met_program_callbacks, $empty_callback);
        array_unshift($goal_exceeded_program_callbacks, $empty_callback);
        */
		if( empty($data['date_begin']) )   {
            $data['date_begin'] = date("Y-m-d"); //default goal plan start start date to be today
         }
		// Default custom expire date to 1 year from today
         if( empty($data['date_end']) )   { //default custom expire date to 1 year from today
            $data['date_end'] = date('Y-m-d', strtotime('+1 year'));
         }
        //$request->goal_measurement_label = '$';
         $data['state_type_id'] = GoalPlan::calculateStatusId($data['date_begin'], $data['date_end']);
         // All goal plans use standard events except recognition goal
         $event_type_needed = 1;//standard
         //if Recognition Goal selected then set 
         if (isset($request->goal_plan_type_id) && ($request->goal_plan_type_id == GoalPlanType::GOAL_PLAN_TYPE_RECOGNITION)) {
            $event_type_needed = 5; // Badge event type;
         }
        /* PENDING - Get the appropriate events for this goal plan type - this is old site code - Pending
        //$events = $this->event_templates_model->readListByProgram((int) $this->program->account_holder_id, array(
        // $event_type_needed,
        // ), 0, 9999);*/
        $user_id=1;
        $new_goal_plan = GoalPlan::create(  $data +
        [
            'organization_id' => $organization->id,
            //'state_type_id'=>1, //PENDING - not found in create function of old system
            'program_id' => $program->id, 
            'progress_notification_email_id'=>1, //for now set any number, PENDING to make it dynamic
            'created_by'=>auth()->user()->id,
        ] );
     	//pr($new_goal_plan);
        if (!empty($new_goal_plan->id)) {
            // Assign goal plans after goal plan created based on INC-206
            //if assign all current participants then run now
            if($data['assign_goal_all_participants_default']==1)	{
                //$new_goal_plan->id = $result;
                $this->assign_all_participants_now($new_goal_plan, $program);
            }
			//redirect('/manager/program-settings/edit-goal-plan/' . $result);
			return response([ 'goal_plan' => $new_goal_plan ]);
		} else {
            return response(['errors' => 'Goal plan Creation failed'], 422);
        }
           // debug('there');
            //redirect('/manager/program-settings/edit-goal-plan/' . $result);
       // } else {
        
       //     return response(['errors' => 'Goal plan Creation failed'], 422);
       // }
        //after this code pending
        // unset($validated['custom_email_template']);
    }
    protected function assign_all_participants_now($goal_plan, $program) {
		//
	    //$max = 50000;
        //This is temporary solution - pending implemntation of original function
        //pr($goal_plan);
        $users =  $this->programService->getParticipants($program, true);
        $users->load('status');
       //Pending to implement this large function 
	   //$data = $this->users_model->readParticipantListWithProgramAwardLevelObject((int) $account_holder_id, 0, '', 0, $max, 'last_name', 'asc', array());
	    $available_statuses = array("Active","Pending Activation","New");
        $added_info = array();
       // pr($users);
        if(!empty($users)) { 
            foreach($users as $user){
				if (!in_array($user->status->status, $available_statuses)) {
				 continue;
				}
                $user_id = $user->id;
                //check for duplicates
				if(!empty($added_info)) {
					foreach($added_info as $val){
						if($val['goal_plan_id']==$goal_plan->id && $val['users_id']==$user_id){
							continue 2; //if already added then continue outer users loop
						}  
					}
				}
                $user_goal=[];
               
              //  pr($goal_plan->date_begin);
                // Copy the submitted info into the user's goal plan array
                $user_goal['goal_plan_id'] =  $goal_plan->id;
                $user_goal['target_value'] = $goal_plan->default_target;
                $user_goal['date_begin'] = $goal_plan->date_begin;
                $user_goal['date_end'] = $goal_plan->date_end;
                $user_goal['factor_before'] = $goal_plan->factor_before;
                $user_goal['factor_after'] = $goal_plan->factor_after;
                $user_goal['created_by'] =  $goal_plan->created_by;
                $user_goal['achieved_callback_id'] = $goal_plan->achieved_callback_id;
                $user_goal['exceeded_callback_id'] = $goal_plan->exceeded_callback_id;
                $user_goal['user_id'] = $user_id;
				
				$response = self::add_user_goal($user_goal,$goal_plan);
				$added_info[]=array("goal_plan_id"=>$goal_plan->id,"users_id"=>$user_id);
    		}
			//pending to return response
        }
	}
    public function add_user_goal($user_goal,$goal_plan) {
		$response=[];
		$active_goal_plan_id = 0;
		$valid = true;
		/* PENDING
		// Make sure the program allows peer 2 peer
		$uses_goal_tracker_config = $this->programs_config_fields_model->read_config_field_by_name ( $program_account_holder_id, CONFIG_PROGRAM_USES_GOAL_TRACKER );
		if (! $uses_goal_tracker_config->value) {
			throw new UnexpectedValueException ( 'This program does allow goal plans', 400 );
		}
		*/
		$date_begin = new DateTime ( $user_goal['date_begin'] );
		$date_end = new DateTime ( $user_goal['date_end'] );
		if ($date_end < $date_begin) {
			$response['error'][]='date_begin cannot be less than date_end';
			return $response;
		} 
		unset($user_goal['date_begin']);
		unset($user_goal['date_end']);

		$current_user_goal_plan = UserGoal::where(['user_id' =>$user_goal['user_id'], 'goal_plan_id' => $user_goal['goal_plan_id']])->first();
		if ($current_user_goal_plan) {
			$response['error'][]='User is already assigned to this goal plan';
			return $response;
		}
		if ($goal_plan->goal_plan_type_id != GoalPlanType::getIdByTypeSales()) {
			// Force the factors to 0 so we don't have to check the goal plan type when we do the awarding
			$user_goal['factor_before'] = 0;
			$user_goal['factor_after'] = 0;
		}
		try {
			$new_user_goal_plan = self::_insert_user_goal($user_goal,$goal_plan );
			if(!empty($new_user_goal_plan)) {
				$response['new_user_goal_plan']=$new_user_goal_plan;
			}
			// If we just created a new user goal and the goal plan is recurring, go ahead and create the user's future goal too
			if ($goal_plan->is_recurring && isset ( $goal_plan->next_goal_id ) && $goal_plan->next_goal_id > 0) {
				// Create the user's future goal plan
				$future_goal_plan_id = self::create_future_goal ( $goal_plan, (array) $new_user_goal_plan );
			}
		} catch ( Exception $x ) {
			$response['error'][] = ["Failure to Create Goal Plan: " . $x->getMessage () . " @{$x->getFile()}:{$x->getLine()} {$x->getCode()}"];
			//throw new RuntimeException ( "Failure to Create Goal Plan: " . $x->getMessage () . " @{$x->getFile()}:{$x->getLine()} {$x->getCode()}", 500 );
		}
		// now we return the response back to the function caller
		return $response;
	}
	private function _insert_user_goal($user_goal,$goal_plan) {
		// Create the user goal record$user_goal,$goal_plan
		if (! isset ( $user_goal['previous_user_goal_id'] ) || $user_goal['previous_user_goal_id'] < 1) {
			$user_goal['previous_user_goal_id'] = null;
		}
		if (! isset ( $user_goal['next_user_goal_id'] ) || $user_goal['next_user_goal_id'] < 1) {
			$user_goal['next_user_goal_id'] = null;
		}
		$new_user_goal_plan = UserGoal::create($user_goal);
		if(!empty($new_user_goal_plan)) {
			return $new_user_goal_plan;
		} else {
			return false;
		}
	}
	public function create_future_goal($goal_plan, $user_goal) {
		// set the new goal plan to begin when the previous one expires
		$active_goal_start = $user_goal->date_begin;
		$active_goal_end = $user_goal->date_end;
		//Read next goal plan
		$future_goal_plan = GoalPlan::where(['id' =>$goal_plan->next_goal_id])->first();

		$user_goal->date_begin = $future_goal_plan->date_begin;
		$user_goal->date_end = $future_goal_plan->date_end;
		
		$active_goal_plan_id = $goal_plan->id;
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
		// Create the Future Goal Plan
		$future_goal_plan = self::_insert_user_goal($user_goal, $future_goal_plan);
		// Update the active goal plan's next goal id with the future goal plan id
		// build the query to INSERT an event then run it!
		$future_goal_plan_id = $future_goal_plan->id;
		
		GoalPlanModel::where(['id'=>$user_goal->id])->update(['next_user_goal_id'=>$future_goal_plan_id]);
		// Update the active goal plan's next goal id with the future goal plan id
		// build the query to INSERT an event then run it!
		GoalPlanModel::where(['id'=>$future_goal_plan_id])->update(['previous_user_goal_id'=>$user_goal->id]);
		return $future_goal_plan_id;
	}
}
