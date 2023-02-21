<?php
namespace App\Services;

use App\Services\UserGoalService; 

use Illuminate\Database\Query\Builder;
use App\Http\Requests\GoalPlanRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
use App\Services\ProgramService;
use App\Models\User;
use App\Models\UserGoal;
use App\Models\Event;
use App\Models\EventType;
use App\Models\GoalPlanType;
use App\Models\ExternalCallback;
use App\Models\EmailTemplate;
use App\Models\Status;
use DB;
//use App\Services\EmailTemplateService;
use DateTime;

class GoalPlanService 
{

    public function __construct(
        ProgramService $programService
        )
	{
        $this->programService = $programService;
    }
	public function add_goal_plan( $data, $organization, $program)
    {   
		/* TO DO
		// check if we have a valid $goal_plan->name format and that it is unique
		if ($this->is_valid_goal_plan_by_name ( $program_account_holder_id, $goal_plan->name )) {
			throw new InvalidArgumentException ( 'Invalid "goal_plan->name" passed, goal_plan->name = ' . $goal_plan->name . ' is already taken', 400 );
		}
		*/
		/* TO DO
		$goal_met_program_callbacks = $this->external_callbacks_model->read_list_by_type((int) $this->program->account_holder_id, CALLBACK_TYPE_GOAL_MET); //CALLBACK_TYPE_GOAL_MET = Goal Met
        $goal_exceeded_program_callbacks = $this->external_callbacks_model->read_list_by_type((int) $this->program->account_holder_id, CALLBACK_TYPE_GOAL_EXCEEDED);
        $empty_callback = new stdClass();
        $empty_callback->id = 0;
        $empty_callback->name = $this->lang->line('txt_none');
        array_unshift($goal_met_program_callbacks, $empty_callback);
        array_unshift($goal_exceeded_program_callbacks, $empty_callback);
        */
		 /* TO DO
         // All goal plans use standard events except recognition goal
         $event_type_needed = EventType::getIdByTypeStandard();//standard
         //if Recognition Goal selected then set 
         if (isset($data['goal_plan_type_id']) && ($data['goal_plan_type_id'] == GoalPlanType::getIdByTypeRecognition())) {
            $event_type_needed = EventType::getIdByTypeBadge(); // Badge event type; - TO DO need some constant here
         }
		 // Get the appropriate events for this goal plan type
        $events = $this->event_templates_model->readListByProgram((int) $this->program->account_holder_id, array(
            $event_type_needed,
        ), 0, 9999);
		 */
		 //TO DO
		 /*if ($this->programs_model->is_shell_program ( $program_account_holder_id )) {
			throw new InvalidArgumentException ( 'Invalid "program_account_holder_id" passed, you cannot create a goal plan in a shell program', 400 );
		}
		if (! isset ( $goal_plan->goal_measurement_label )) {
			$goal_plan->goal_measurement_label = '';
		}*/
		
        $new_goal_plan = GoalPlan::create(  $data +
        [
            'organization_id' => $organization->id,
            'program_id' => $program->id, 
            //'progress_notification_email_id'=>1, //for now set any number, TO DO to make it dynamic
            'created_by'=>auth()->user()->id,
        ] );
     	//pr($new_goal_plan);
		 $response['goal_plan'] = $new_goal_plan;
        if (!empty($new_goal_plan->id)) {
            // Assign goal plans after goal plan created based on INC-206
            //if assign all current participants then run now
			if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'])	{
                //$new_goal_plan->id = $result;
                $assign_response =self::assign_all_participants_now($new_goal_plan, $program);
				$response['assign_msg'] = self::assign_all_participants_res($assign_response);
				//$response['assign_all_participants']=$assign_response;
            }
			//redirect('/manager/program-settings/edit-goal-plan/' . $result);
		}
			return $response;
		//redirect('/manager/program-settings/edit-goal-plan/' . $result);
		// unset($validated['custom_email_template']);
    }
	public function update_goal_plan($data, $goalplan, $organization, $program)
    {
		$response=[];
        //TO DO - not clear /git-clean/core-program/php_includes/application/controllers/manager/program_settings.php
		/*if( empty($data['date_begin']) )   {
            $data['date_begin'] = date("Y-m-d"); //default goal plan start start date to be today
         }
		// Default custom expire date to 1 year from today
         if( empty($data['date_end']) )   { //default custom expire date to 1 year from today
            $data['date_end'] = date('Y-m-d', strtotime('+1 year'));
         }*/
		/*TO DO
		$goal_met_program_callbacks = $this->external_callbacks_model->read_list_by_type((int) $this->program->account_holder_id, CALLBACK_TYPE_GOAL_MET);
		$goal_exceeded_program_callbacks = $this->external_callbacks_model->read_list_by_type((int) $this->program->account_holder_id, CALLBACK_TYPE_GOAL_EXCEEDED);
		$email_templates = $this->email_templates_model->read_list_program_email_templates_by_type((int) $this->program->account_holder_id, "Goal Progress", 0, 9999);
		$this->view_params['email_templates'] = $email_templates;
		$empty_callback = new stdClass();
		$empty_callback->id = 0;
		$empty_callback->name = $this->lang->line('txt_none');
		array_unshift($goal_met_program_callbacks, $empty_callback);
		array_unshift($goal_exceeded_program_callbacks, $empty_callback);	 
		*/
        //$request->goal_measurement_label = '$';
         //$data['state_type_id'] = GoalPlan::calculateStatusId($data['date_begin'], $data['date_end']);
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
            if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'])	{
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
		$response=[];
        $users =  $this->programService->getParticipants($program, true);
		if(!empty($users)) {
        	$users->load('status');
		}
       //TO DO to implement this large function 
	   //$data = $this->users_model->readParticipantListWithProgramAwardLevelObject((int) $account_holder_id, 0, '', 0, $max, 'last_name', 'asc', array());
	    $available_statuses = array("Active","TO DO Activation","New");
        $added_info = [];
	  	// $future_goal_plan_failure = 0;
	  	$success_user=$fail_user=$success_future_user=$fail_future_user=$already_assigned=[];
        if(!empty($users)) {
			$user_goal=[];
			//pr($goal_plan); die;
			// Copy the submitted info into the user's goal plan array
			/*
				Already in create function 
				$date_begin = new DateTime ( $user_goal['date_begin'] );
				$date_end = new DateTime ( $user_goal['date_end'] );

				if ($date_end < $date_begin) {
					//no need to loop other users and stop it here because goal plan data is same for all
					//it should be in validation code -TO DO
					$response['error']='Date begin cannot be less than Date end';
					break;
				}
			unset($user_goal['date_begin']);
			unset($user_goal['date_end']);*/
			$user_goal['goal_plan_id'] =  $goal_plan->id;
			$user_goal['target_value'] = $goal_plan->default_target;
			$user_goal['date_begin'] = $goal_plan->date_begin;
			$user_goal['date_end'] = $goal_plan->date_end;
			$user_goal['factor_before'] = $goal_plan->factor_before;
			$user_goal['factor_after'] = $goal_plan->factor_after;
			$user_goal['created_by'] =  auth()->user()->id; //$goal_plan->created_by;
			$user_goal['achieved_callback_id'] = $goal_plan->achieved_callback_id;
			$user_goal['exceeded_callback_id'] = $goal_plan->exceeded_callback_id;
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
                $user_goal['user_id'] = $user_id;
				//create
				$response = UserGoalService::create($goal_plan, $user_goal);
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

	 /** 
     * @method readActiveByProgram - alias to read_active_by_program
     * 
	 * @param Program $program
     * @return Collection
     * @throws Exception
     */
	public static function readActiveByProgram($program, $offset = 0, $limit = 10, $order_column = 'name', $order_direction = 'asc') {
		$state = Status::get_goal_active_state();
		return self::readListByProgramAndState ( $program, $state, $offset, $limit, $order_column, $order_direction );
	
	}

	 /** 
     * @method readListByProgramAndState - alias to read_list_by_program_and_state
     * 
	 * @param Program $program
     * @param $goal_plan_state_id
     * @return Collection
     * @throws Exception
     */
	public static function readListByProgramAndState($program, $goal_plan_state_id = 0, $offset = 0, $limit = 10, $order_column = 'name', $order_direction = 'asc') {
		$query = self::_selectGoalPlanInfo();
		$query->where('gp.program_id', '=', $program->id);
		$query->where('gp.state_type_id', '=', $goal_plan_state_id);
		$query->limit($limit)->offset($offset)->get();
		$query->orderBy($order_column,$order_direction);
		try {
            $result = $query->get();
            return $result;
        } catch (Exception $e) {
            throw new Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
		// build and run the query
		/*$sql = "
            SELECT
                " . $this->_select_goal_plan_info () . "
            WHERE
                gp.`program_account_holder_id` = {$program_account_holder_id}
                AND gp.`state_type_id` = {$goal_plan_state_id}
            ORDER BY
                gp.`{$order_column}` {$order_direction}
            LIMIT
                {$offset}, {$limit};
        ";
		// $handle = fopen('x.txt', 'w');fwrite($handle, $sql);
		$query = $this->read_db->query ( $sql );
		// check if the query has run right
		if (! $query) {
			throw new RuntimeException ( 'Internal query failed, please contact API administrator', 500 );
		}
		// now we finally give back the result to the FUNCTION caller
		return $query->result ();*/
		/**try {
            return [
                'data' => $query->limit($limit)->offset($offset)->get(),
                'total' => $query->count()
            ];
        } catch (Exception $e) {
            throw new Exception('DB query failed.', 500);
        } */
	}

	  /** 
     * @method _selectGoalPlanInfo - alias to _select_goal_plan_info
     * @return Collection
     */
	private static function _selectGoalPlanInfo() {
		$query = DB::table('goal_plans AS gp');
		$query->addSelect([
			'gp.id',
			'gp.next_goal_id as next_goal_id',
			'gp.previous_goal_id as previous_goal_id',
			'gp.program_id',
			'gp.name',
			'gp.default_target',
			'gp.goal_measurement_label',
			'gp.email_template_id',
			'gp.expiration_rule_id',
			'gp.custom_expire_offset',
			'gp.custom_expire_units',
			'gp.annual_expire_month',
			'gp.annual_expire_day',
			'gp.notification_body',
			'gp.achieved_callback_id',
			'gp.exceeded_callback_id',
			'gp.achieved_event_id',
			'gp.exceeded_event_id',
			
			'gp.achieved_event_id',
			'ae.name as achieved_event_name',
			'ae.event_icon_id as achieved_event_icon',
			
			'gp.exceeded_event_id',
			'ee.name as  exceeded_event_name',
			'ee.event_icon_id as exceeded_event_icon',
			
			'gp.factor_before',
			'gp.factor_after',
			'gp.date_begin',
			'gp.date_end',
			'gp.created_at',
			'gp.created_by',
			'gp.updated_at',
			'gp.modified_by',
			'gp.goal_plan_type_id',
			'gp.state_type_id',
			'gp.is_recurring',
			'gp.award_per_progress',
			'gp.award_email_per_progress',
			'gp.progress_notification_email_id',
			'gp.progress_requires_unique_ref_num',
			'gp.assign_goal_all_participants_default',
			'gp.automatic_progress',
			'gp.automatic_frequency',
			'gp.automatic_value',
			'gt.name as goal_plan_type_name',
			'er.name as expiration_rule_name',
			'st.status as state_type_name',
		]);
		// (SELECT COUNT(*) FROM " . USER_GOALS_TBL . " WHERE `goal_plan_id` = gp.`id`) as has_participants,
		/*$query->addSelect(
            DB::raw("count(*) FROM user_goals WHERE `goal_plan_id` = `gp.id` as `has_participants`")
        );*/
		$query->addSelect(['has_participants' => function (Builder $builder) {
			$builder->from('user_goals')->selectRaw('count(*) as has_participants')->whereColumn('user_goals.goal_plan_id', 'gp.id');
		}]);
		//(SELECT COUNT(*) FROM " . USER_GOAL_PROGRESS_TBL . " WHERE `goal_plan_id` = gp.`id`) as has_participant_progress
		/*$query->addSelect(
            DB::raw("count(*) FROM user_goal_progress WHERE goal_plan_id = gp.id as has_participant_progress")
        );*/
		$query->addSelect(['has_participant_progress' => function (Builder $builder) {
			$builder->from('user_goal_progress')->selectRaw('count(*) as has_participant_progress')->whereColumn('user_goal_progress.goal_plan_id', 'gp.id');
		}]);
		$query->join('goal_plan_types AS gt', 'gt.id', '=', 'gp.goal_plan_type_id');
		$query->leftJoin('statuses AS st', 'st.id', '=', 'gp.state_type_id'); 
		$query->leftJoin('expiration_rules AS er', 'er.id', '=', 'gp.expiration_rule_id'); 
		$query->join('events AS ae', 'ae.id', '=', 'gp.achieved_event_id');
		$query->leftJoin('events AS ee', 'ee.id', '=', 'gp.exceeded_event_id');
		
		return $query;
	}

}
