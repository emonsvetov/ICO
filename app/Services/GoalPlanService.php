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
		$response=[];
		if (!GoalPlan::CONFIG_PROGRAM_USES_GOAL_TRACKER) {
            return false;
        }
        //TO DO - not clear /git-clean/core-program/php_includes/application/controllers/manager/program_settings.php
		//CALLBACK_TYPE_GOAL_MET=Goal Met 
        /*
		 // If the program does not allow goals, kick them out
        if (!$this->config_fields[CONFIG_PROGRAM_USES_GOAL_TRACKER]->value) {
            redirect('/manager/program-settings');
        }
		$goal_met_program_callbacks = $this->external_callbacks_model->read_list_by_type((int) $this->program->account_holder_id, CALLBACK_TYPE_GOAL_MET);
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
         if (isset($data['goal_plan_type_id']) && ($data['goal_plan_type_id'] == GoalPlanType::GOAL_PLAN_TYPE_RECOGNITION)) {
            $event_type_needed = 5; // Badge event type; - TO DO need some constant here
         }
        /* TO DO - Get the appropriate events for this goal plan type - this is old site code - TO DO
        //$events = $this->event_templates_model->readListByProgram((int) $this->program->account_holder_id, array(
        // $event_type_needed,
        // ), 0, 9999);*/
        $new_goal_plan = GoalPlan::create(  $data +
        [
            'organization_id' => $organization->id,
            //'state_type_id'=>1, //TO DO - not found in create function of old system
            'program_id' => $program->id, 
            'progress_notification_email_id'=>1, //for now set any number, TO DO to make it dynamic
            'created_by'=>auth()->user()->id,
        ] );
     	//pr($new_goal_plan);
		 $response['goal_plan'] = $new_goal_plan;
        if (!empty($new_goal_plan->id)) {
            // Assign goal plans after goal plan created based on INC-206
            //if assign all current participants then run now
            if($data['assign_goal_all_participants_default']==1)	{
                //$new_goal_plan->id = $result;
                $assign_response =self::assign_all_participants_now($new_goal_plan, $program);
				$response['assign_msg'] = self::assign_all_participants_res($assign_response);
				//$response['assign_all_participants']=$assign_response;
            }
			//redirect('/manager/program-settings/edit-goal-plan/' . $result);
		}
			return $response;
		//redirect('/manager/program-settings/edit-goal-plan/' . $result);
		//after this code TO DO
		// unset($validated['custom_email_template']);
    }
	public function update_goal_plan($data, $goalplan, $organization, $program)
    {
		$response=[];
        //TO DO - not clear /git-clean/core-program/php_includes/application/controllers/manager/program_settings.php
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
		if (isset($data['goal_plan_type_id']) && ($data['goal_plan_type_id'] == GoalPlanType::GOAL_PLAN_TYPE_RECOGNITION)) {
            $event_type_needed = 5; // Badge event type; - TO DO need some constant here
         }
        /* TO DO - Get the appropriate events for this goal plan type - this is old site code - TO DO
        //$events = $this->event_templates_model->readListByProgram((int) $this->program->account_holder_id, array(
        // $event_type_needed,
        // ), 0, 9999);*/
		$data['modified_by']=auth()->user()->id;
        $updated_goal_plan = $goalplan->update( $data );
		$response['goal_plan'] = $goalplan;
        if (!empty($updated_goal_plan)) {
            // Assign goal plans after goal plan updated based on INC-206
            //if assign all current participants then run now
            if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'] == 1)	{
                $assign_response =self::assign_all_participants_now($goalplan, $program);
				$response['assign_msg'] = self::assign_all_participants_res($assign_response);
            }
		}
			return $response;
    }
	public function assign_all_participants_res($response) {
		$msg='';
		if(!empty($response['success_count']) && $response['success_count'] >= 1) {
			$msg = $response['success_count']. " participant(s) assigned!";
		}
		if(!empty($response['fail_count']) && $response['fail_count'] >= 1) {
			$msg = $response['fail_count']. " participant(s) assignment failed!";
		}
		return $msg;
	}
    public function assign_all_participants_now($goal_plan, $program) {
		//
	    //$max = 50000;
        //This is temporary solution - TO DO implemntation of original function
        //pr($goal_plan);
		$response=[];
        $users =  $this->programService->getParticipants($program, true);
        $users->load('status');
       //TO DO to implement this large function 
	   //$data = $this->users_model->readParticipantListWithProgramAwardLevelObject((int) $account_holder_id, 0, '', 0, $max, 'last_name', 'asc', array());
	    $available_statuses = array("Active","TO DO Activation","New");
        $added_info = array();
       // pr($users);
	  // $future_goal_plan_failure = 0;
	  $success_user=$fail_user=$success_future_user=$fail_future_user=$already_assigned=[];
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
               
             	//pr($goal_plan); die;
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
				
				$date_begin = new DateTime ( $user_goal['date_begin'] );
				$date_end = new DateTime ( $user_goal['date_end'] );
				if ($date_end < $date_begin) {
					//no need to loop other users and stop it here because goal plan data is same for all
					//it should be in validation code -TO DO
					$response['error']='Date begin cannot be less than Date end';
					break;
				}
				unset($user_goal['date_begin']);
				unset($user_goal['date_end']);

				$response = self::add_user_goal($goal_plan, $user_goal);
	
				if(isset($response['already_assigned'])) {
					$already_assigned[]=$user_id; // User is already assigned to this goal plan
					continue;
				}
				if(!$response || !isset($response['user_goal_plan'])) {
					$fail_user[]=$user_id;
				} else if(isset($response['user_goal_plan'])) {
					$added_info[]=array("goal_plan_id"=>$goal_plan->id,"users_id"=>$user_id);
					$success_user[]=$user_id;
				}
				//$goal_plan is_recurring
				if(isset($response['future_user_goal'])) { 
					if($response['future_user_goal'])
						$success_future_user[]=$user_id;
					else
						$fail_future_user[]=$user_id;
				} //else no need of future goal	
    		}
			//create response 
			$response['success_count']=count($success_user);
			$response['fail_count']=count($fail_user);
        }
		return $response;
	}
    public function add_user_goal($goal_plan, $user_goal) {
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
			$new_user_goal_plan = self::_insert_user_goal($user_goal);
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
	private function _insert_user_goal($user_goal) {
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
	public function create_future_goal($goal_plan, $user_goal) {
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
		$future_user_goal = self::_insert_user_goal($future_ugp);
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
