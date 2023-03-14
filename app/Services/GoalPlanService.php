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
use App\Models\Status;
use App\Models\ExpirationRule;

use DB;
//use App\Services\EmailTemplateService;
use DateTime;

class GoalPlanService 
{

    public function __construct(
        ProgramService $programService,
		UserGoalService $userGoalService
        )
	{
        $this->programService = $programService;
		$this->userGoalService = $userGoalService;
    }
	public function create( $data, $organization, $program)
    {   
		//$custom_expire_offset = 12, $custom_expire_units = 'month', $annual_expire_month = 0, $annual_expire_day = 0
		 //TO DO
		/*if (! isset ( $goal_plan->goal_measurement_label )) {
			$goal_plan->goal_measurement_label = '';
		}*/
		//$data['state_type_id'] = GoalPlan::calculateStatusId($data['date_begin'], $data['date_end']);
		
		//'progress_notification_email_id'=>1, //for now set any number, TO DO to make it dynamic
		$data['organization_id'] = $organization->id;
		$data['program_id'] = $program->id;
		$data['created_by'] = auth()->user()->id;

		$expiration_rule = ExpirationRule::find($data['expiration_rule_id']);
		//Create goal plan
		$newGoalPlan = self::_insert($data, $expiration_rule);
        
		 $response['goal_plan'] = $newGoalPlan;
		 
        if (!empty($newGoalPlan->id)) {
            // Assign goal plans after goal plan created based on INC-206
            //if assign all current participants then run now
			if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'])	{
                //$newGoalPlan->id = $result;
                $assignResponse =self::assignAllParticipantsNow($newGoalPlan, $program);
				$response['assign_msg'] = self::assignAllParticipantsRes($assignResponse);
				//$response['assign_all_participants']=$assignResponse;
            }
			//redirect('/manager/program-settings/edit-goal-plan/' . $result);
		}
			return $response;
		//redirect('/manager/program-settings/edit-goal-plan/' . $result);
		// unset($validated['custom_email_template']);
    }
	private static function _insert($data, $expiration_rule) {

		$expiration_date = ExpirationRule::compile($expiration_rule, $data['date_begin'], $data['date_end'], isset ( $data['custom_expire_offset'] ) ? $data['custom_expire_offset'] : null, isset ( $data['custom_expire_units'] ) ? $data['custom_expire_units'] : null, isset ( $data['annual_expire_month'] ) ? $data['annual_expire_month'] : null, isset ( $data['annual_expire_day'] ) ? $data['annual_expire_day'] : null );

		$data['date_end'] =  $expiration_date[0]->expires;

		// build the query to INSERT an event then run it!
		$data['state_type_id'] = GoalPlan::calculateStatusId($data['date_begin'], $data['date_end']);
		$newGoalPlan = GoalPlan::create(  $data );
		return $newGoalPlan;
	}

	public function update($data, $currentGoalPlan, $organization, $program)
    {
	
		pr(self::activateGoalPlan($program->id,$currentGoalPlan ));
		die;
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
         /*$event_type_needed = 1;//standard
         //if Recognition Goal selected then set 
		if (isset($data['goal_plan_type_id']) && ($data['goal_plan_type_id'] == GoalPlanType::GOAL_PLAN_TYPE_RECOGNITION)) {
            $event_type_needed = 5; // Badge event type; - TO DO need some constant here
         }*/
        /* TO DO - Get the appropriate events for this goal plan type - this is old site code - TO DO
        //$events = $this->event_templates_model->readListByProgram((int) $this->program->account_holder_id, array(
        // $event_type_needed,
        // ), 0, 9999);*/
		$data['modified_by']=auth()->user()->id;
		//VALIDATE HERE TO for recursively called functions
		//Code - 
		$active_state_id = Status::get_goal_active_state ();
		$future_state_id = Status::get_goal_future_state ();
		$expired_state_id = Status::get_goal_expired_state ();
		// This means only the most recent expired plan can be edited
		if ($currentGoalPlan->state_type_id == $expired_state_id) {
			if ($data['is_recurring'] && isset ( $currentGoalPlan->next_goal_id ) && $currentGoalPlan->next_goal_id != null && $currentGoalPlan->next_goal_id > 0) {
				$nextGoalPlan = GoalPlan::getGoalPlan( $currentGoalPlan->next_goal_id); 
				//$this->read ( $program_account_holder_id, ( int ) $currentGoalPlan->next_goal_id );
				if ($nextGoalPlan->state_type_id != $active_state_id && $nextGoalPlan->state_type_id != $future_state_id) {
					throw new RuntimeException ( 'Only the most recent expired goal plan can be edited.' );
				}
			}
		}

		// read the current goal plans expiration rules
		$expiration_rule = ExpirationRule::getExpirationRule ($currentGoalPlan->expiration_rule_id );
		// Don't allow the goal start date to overlap with the previous goal cycle
		if ($data['is_recurring'] && isset ( $currentGoalPlan->previous_goal_id ) && $currentGoalPlan->previous_goal_id > 0) {
			$previousGoalPlan = $this->read ( $program_account_holder_id, ( int ) $currentGoalPlan->previous_goal_id ); //TO DO
			if (isset ( $previousGoalPlan )) {
				$date1 = new DateTime ( $previousGoalPlan->date_end );
				$date2 = new DateTime ( $previousGoalPlan->date_begin );
				if ($date1 > $date2) {
					throw new InvalidArgumentException ( 'The Goal Plans date_begin cannot be less than the previous goal cycles date_end (' . $previousGoalPlan->date_end . ')' );
				}
			}
		}
		// If the goal is set to active....
		if ($currentGoalPlan->state_type_id == $active_state_id) {
			// Hate to do it like this...
			// Check if the goal plan is set to recurring. Create\Delete the future goal plan as needed
			if ($data['is_recurring'] && (! isset ( $currentGoalPlan->next_goal_id ) || $currentGoalPlan->next_goal_id == null || $currentGoalPlan->next_goal_id < 1)) {
				// TO DO $future_goal_plan_id = $this->create_future_plan ( $data, $expiration_rule ); // TO DO
			} else if (! $data['is_recurring'] && isset ( $currentGoalPlan->next_goal_id ) && $currentGoalPlan->next_goal_id > 0) {
				$this->delete_future_plan ( $currentGoalPlan );
			}
		}

		/*TO DO
		if ($data->goal_plan_type_name == GOAL_PLAN_TYPE_EVENT_COUNT) {
			if (( int ) $goal_plan->achieved_event_template_id > 0) {
				$this->untie_event_from_goal_plan ( ( int ) $program_account_holder_id, ( int ) $goal_plan->id, ( int ) $goal_plan->achieved_event_template_id );
			}
			if (( int ) $goal_plan->exceeded_event_template_id > 0) {
				$this->untie_event_from_goal_plan ( ( int ) $program_account_holder_id, ( int ) $goal_plan->id, ( int ) $goal_plan->exceeded_event_template_id );
			}
		}*/


        $updated_goal_plan  = $currentGoalPlan->update( $data );
		$response['goal_plan'] = $currentGoalPlan; // TO DO testing here

		//$current_goal_plan = $this->read ( $program_account_holder_id, $goal_plan->id );
		if (self::needsActivated($currentGoalPlan)) {
			$this->activate_goal_plan ( $program_account_holder_id, $currentGoalPlan ); // TO DO 
		}
		if (self::needsExpired ( $currentGoalPlan )) {
			$this->expire_goal_plan ( $program_account_holder_id, $currentGoalPlan ); // TO DO 
		}
		if (self::needsFutured ( $currentGoalPlan )) {
			$this->future_goal_plan ( $program_account_holder_id, $currentGoalPlan ); // TO DO 
		}

		// RULES FOR MOVING THE DATES ON THE FUTURE GOAL PLAN
		if ($data['is_recurring'] && isset ( $currentGoalPlan->next_goal_id ) && $currentGoalPlan->next_goal_id > 0) {
			//$next_goal_plan = $this->read ( $program_account_holder_id, ( int ) $currentGoalPlan->next_goal_id );
			$nextGoalPlan = GoalPlan::getGoalPlan( $currentGoalPlan->next_goal_id); 
			if (isset ( $nextGoalPlan )) {
				$date1 = new DateTime ( $nextGoalPlan->date_begin );
				$date2 = new DateTime ( $data['date_end'] );
				if ($date1 < $date2) {
					// throw new InvalidArgumentException('The Goal Plans date_begin cannot be less than the previous goal cycles date_end (' . $previous_goal_plan->date_end . ')');
					$end_date_sql = $this->write_db->escape ( $goal_plan->date_end );
					// Store the active goal plan's end date
					$active_goal_end_date = $goal_plan->date_end;
					// Create the Future Goal Plan
					// use the end date of the active goal plan and the expiration rule to set the end date for the future goal goal
					switch ($expiration_rule->name) {
						case "Custom" :
						case "Specified" :
							// Need to do some math to figure out how many days are between the active goal plans start and end dates
							$date1 = new DateTime ( $goal_plan->date_begin );
							$date2 = new DateTime ( $active_goal_end_date );
							$diff_in_days = $date2->diff ( $date1 )->format ( "%a" );
							$end_date_sql = "date_add({$this->write_db->escape($active_goal_end_date)}, interval {$diff_in_days} DAY)";
							break;
						case "End of Following Year" :
						case "End of Next Year" :
						case "1 Year" :
							$active_goal_end_date = date ( "Y-12-31", strtotime ( date ( "Y-m-d", strtotime ( $goal_plan->date_end ) ) . " +1 year" ) );
							$end_date_sql = $this->write_db->escape ( $active_goal_end_date );
							break;
						case "12 Months" :
						case "9 Months" :
						case "6 Months" :
						case "3 Months" :
						default :
							$offset = $expiration_rule->expire_offset;
							$units = $expiration_rule->expire_units;
							$start_date = $this->write_db->escape ( $active_goal_end_date );
							$end_date_sql = "date_add({$start_date}, interval {$offset} {$units})";
					}
					$sql = "select {$end_date_sql} as expires";
					$results = DB::select( DB::raw($sql));
					 $expiration_date[0]->expires;
					$nextGoalPlan->date_begin = $data['date_end'];
					$nextGoalPlan->date_end = $expiration_date[0]->expires;
					$data = $nextGoalPlan->validated();
					//pr($nextGoalPlan); TO DO errors
					$this->update ($data, $currentGoalPlan, $organization, $program);
				}
			}
		}
        if (!empty($updated_goal_plan)) {
            // Assign goal plans after goal plan updated based on INC-206
            //if assign all current participants then run now
            if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'])	{
                $assignResponse =self::assignAllParticipantsNow($currentGoalPlan, $program);
				$response['assign_msg'] = self::assignAllParticipantsRes($assignResponse);
            }
		}
		/* TO DO
		 * // If the event template was created redirect the user to the edit page
			if (has_resource_permission(RESOURCE_GOAL_PLANS_TIE_EVENT)) {
				try {
					$gpes = $this->goal_plans_model->read_list_goal_plan_events((int) $this->program->account_holder_id, array(
						(int) $goal_plan_id,
					));
					$gpe = $gpes[(int) $goal_plan_id];
					$this->update_tied_goal_plan_events($goal_plan_id, $gpe, $assigned_events, $unassigned_events);
				} catch (Exception $e) {
					$this->_add_error_message($e->getMessage());
				}
			}
		 */
		//php_includes\application\controllers\manager\program_settings.php
		//TO DO - Find if goal plan is editable
		//TO DO - Event goals event_goals code unassigned_events/assigned_events
	   //TO DO - Load events which is different from create goalplan(Pending to discusss)

		return $response;
    }
	public function assignAllParticipantsRes($response) {
		$msg='';
		if(!empty($response['success_count']) && $response['success_count'] >= 1) {
			$msg = $response['success_count']. " participant(s) assigned!";
		}
		if(!empty($response['fail_count']) && $response['fail_count'] >= 1) {
			$msg = $response['fail_count']. " participant(s) assignment failed!";
		}
		return $msg;
	}
	//Aliases for assign_all_participants_now
    public function assignAllParticipantsNow($goalPlan, $program) {
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
			$user_goal['goal_plan_id'] =  $goalPlan->id;
			$user_goal['target_value'] = $goalPlan->default_target;
			$user_goal['date_begin'] = $goalPlan->date_begin;
			$user_goal['date_end'] = $goalPlan->date_end;
			$user_goal['factor_before'] = $goalPlan->factor_before;
			$user_goal['factor_after'] = $goalPlan->factor_after;
			$user_goal['created_by'] =  auth()->user()->id; //$goalPlan->created_by;
			$user_goal['achieved_callback_id'] = $goalPlan->achieved_callback_id;
			$user_goal['exceeded_callback_id'] = $goalPlan->exceeded_callback_id;
            foreach($users as $user){
				if (!in_array($user->status->status, $available_statuses)) {
				 continue;
				}
                $user_id = $user->id;
                //check for duplicates
				if(!empty($added_info)) {
					foreach($added_info as $val){
						if($val['goal_plan_id']==$goalPlan->id && $val['users_id']==$user_id){
							continue 2; //if already added then continue outer users loop
						}  
					}
				}
                $user_goal['user_id'] = $user_id;
				//create
				$response = $this->userGoalService::create($goalPlan, $user_goal);
				if(isset($response['already_assigned'])) {
					$already_assigned[]=$user_id; // User is already assigned to this goal plan
					continue;
				}
				if(!$response || !isset($response['user_goal_plan'])) {
					$fail_user[]=$user_id;
				} else if(isset($response['user_goal_plan'])) {
					$added_info[]=array("goal_plan_id"=>$goalPlan->id,"users_id"=>$user_id);
					$success_user[]=$user_id;
				}
				//$goalPlan is_recurring
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
	//Aliases for  needs_activated
	public static function needsActivated($goalPlan) {
		$sql = "
    		select
    			if(gp.date_end <= now(), 'expired'
    				, if(now() < gp.date_begin, 'future'
    				, 'active')
    			) status
    		from goal_plans gp
        		where
        		gp.id = {$goalPlan->id}
        		";
		$result = DB::select( DB::raw($sql));
		if (! $result) {
			throw new RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		return ( bool ) ($result[0]->status == 'active');
	}

	//Aliases for  needs_expired
	public static function needsExpired($goalPlan) {
		$sql = "
    		select
    			if(gp.date_end <= now(), 'expired'
    				, if(now() < gp.date_begin, 'future'
    				, 'active')
    			) status
    		from goal_plans gp
    		where
    			gp.id = {$goalPlan->id}
    	";
		$result = DB::select( DB::raw($sql));
		if (! $result) {
			throw new RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		return ( bool ) ($result[0]->status == 'expired');
	}
	//Aliases for needs_futured
	public function needsFutured($goalPlan) {
		$sql = "
    		select
    			if(gp.date_end <= now(), 'expired'
    				, if(now() < gp.date_begin, 'future'
    				, 'active')
    			) status
    		from goal_plans gp
    		where
    			gp.id = {$goalPlan->id}
    	";
		$result = DB::select( DB::raw($sql));
		if (! $result) {
			throw new RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		return ( bool ) ($result[0]->status == 'future');
	}
	//Aliases for activate_goal_plan
	public function activateGoalPlan($program_id = 0, $goalPlan) {
		// 1. set the user_goals record to expired
		// 2. identify the future goal and promote it to active
		//
		$expired_state_id = Status::get_goal_expired_state ();
		$active_state_id = Status::get_goal_active_state ();
		// advance current the future goal to be active
		/*$sql = "
			update goal_plans set
				state_type_id = {$active_state_id},
				expired = NULL
			where
				id = {$goal_plan->id}		
                and now() between date_begin and date_end
		";*/
		$result= GoalPlan::whereRaw("id = {$goalPlan->id} and now() between date_begin and date_end")->update(['state_type_id'=>$active_state_id,'expired'=>null]);

		//where(['id'=>$goalPlan->id,'now()' =>' between date_begin and date_end'])->update(['state_type_id'=>$active_state_id,'expired'=>null ]);
		//$result = DB::select( DB::raw($sql));
		/*if (! $result) {
			//throw new RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}*/
		if ($result == 0) {
			// maybe there isn't a future goal, or it's start date has been changed
			return false;
			// TODO: have different cron to look for goal plans that need to be activated?
		} else if ($result > 1) {
			throw new RuntimeException ( "Data Corruption: More than 1 record was changed!", 500 );
		} else {
			if (isset ( $goalPlan->is_recurring ) && $goalPlan->is_recurring) {
				if (! isset ( $goalPlan->next_goal_id ) || $goalPlan->next_goal_id <= 0) {
					// goal plan is recurring, but there is no future goal plan defined
					$expiration_rule = ExpirationRule::getExpirationRule ($goalPlan->expiration_rule_id );
					// TO DO $future_goal_plan = $this->create_future_plan ( $goalPlan, $expiration_rule ); //TO DO
				}
			}
			return true;
		}
	
	}
}
