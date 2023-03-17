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

		$data['date_end'] =  $expiration_date;

		// build the query to INSERT an event then run it!
		$data['state_type_id'] = GoalPlan::calculateStatusId($data['date_begin'], $data['date_end']);
		if(isset($data['id'])) {
			unset($data['id']);
		}
		$newGoalPlan = GoalPlan::create(  $data );
		return $newGoalPlan;
	}
	public function editGoalPlan(GoalPlan $goalPlan, $data, $program, $organization) {
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
		//$data['modified_by']=auth()->user()->id;

		$data['modified_by']=auth()->user()->id;

		//set fields here

		 //if assign all current participants then run now
		if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'])	{
			$assignResponse =self::assignAllParticipantsNow($goalPlan, $program);
			$response['assign_msg'] = self::assignAllParticipantsRes($assignResponse);
		}	
		//Update
		try {
			$this->update($goalPlan, $data);
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
		}
		catch (Exception $e) {
			
		}
		
	}
	private function update($goalPlan, $data)
    {
		$response=[];

		//VALIDATE HERE TO for recursively called functions
		//Code - 
		$active_state_id = Status::get_goal_active_state ();
		$future_state_id = Status::get_goal_future_state ();
		$expired_state_id = Status::get_goal_expired_state ();

		// This means only the most recent expired plan can be edited
		if ($goalPlan->state_type_id == $expired_state_id) {
			if ($data['is_recurring'] && !empty ( $goalPlan->next_goal_id )) {
				$nextGoalPlan = GoalPlan::getGoalPlan( $goalPlan->next_goal_id); 
				//pr($nextGoalPlan);
				//$this->read ( $program_account_holder_id, ( int ) $goalPlan->next_goal_id );
				if ($nextGoalPlan->state_type_id != $active_state_id && $nextGoalPlan->state_type_id != $future_state_id) {
					throw new \RuntimeException ( 'Only the most recent expired goal plan can be edited.' );
				}
			}
		}
		// read the current goal plans expiration rules
		$expirationRule = ExpirationRule::getExpirationRule ($goalPlan->expiration_rule_id );
		// Don't allow the goal start date to overlap with the previous goal cycle
		if ($data['is_recurring'] && ! empty ( $goalPlan->previous_goal_id )) {
			$previousGoalPlan = GoalPlan::getGoalPlan( $goalPlan->previous_goal_id); 
			//$previousGoalPlan = $this->read ( $program_account_holder_id, ( int ) $goalPlan->previous_goal_id ); //TO DO
			if (isset ( $previousGoalPlan )) {
				$date1 = new DateTime ( $previousGoalPlan->date_end );
				$date2 = new DateTime ( $previousGoalPlan->date_begin );
				if ($date1 > $date2) {
					throw new \InvalidArgumentException ( 'The Goal Plans date_begin cannot be less than the previous goal cycles date_end (' . $previousGoalPlan->date_end . ')' );
				}
			}
		}
		// If the goal is set to active....
		if ($goalPlan->state_type_id == $active_state_id) {
			// Hate to do it like this...
			// Check if the goal plan is set to recurring. Create\Delete the future goal plan as needed
			if ($data['is_recurring'] && empty($goalPlan->next_goal_id)) {
				$futureGoalPlan = $this->createFuturePlan ((object) $data, $expirationRule ); // TO DO
			} else if (! $data['is_recurring'] && !empty ( $goalPlan->next_goal_id )) {
				self::deleteFuturePlan ( $goalPlan );
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


        $update_result  = $goalPlan->update( $data );
		$response['goal_plan'] = $goalPlan; 

		//$current_goal_plan = $this->read ( $program_account_holder_id, $goal_plan->id );
		if (self::needsActivated($goalPlan)) {
			self::activateGoalPlan( $goalPlan->program_id, $goalPlan ); // TO DO 
		}
		if (self::needsExpired ( $goalPlan )) {
			self::expireGoalPlan ( $goalPlan->program_id, $goalPlan ); // TO DO 
		}
		if (self::needsFutured ( $goalPlan )) {
			self::futureGoalPlan( $goalPlan->program_id, $goalPlan ); // TO DO 
		}

		// RULES FOR MOVING THE DATES ON THE FUTURE GOAL PLAN
		if ($data['is_recurring'] && isset ( $goalPlan->next_goal_id ) && $goalPlan->next_goal_id > 0) {
			//$next_goal_plan = $this->read ( $program_account_holder_id, ( int ) $goalPlan->next_goal_id );
			$nextGoalPlan = GoalPlan::getGoalPlan( $goalPlan->next_goal_id); 
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
					switch ($expirationRule->name) {
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
							$offset = $expirationRule->expire_offset;
							$units = $expirationRule->expire_units;
							$start_date = $this->write_db->escape ( $active_goal_end_date );
							$end_date_sql = "date_add({$start_date}, interval {$offset} {$units})";
					}
					$sql = "select {$end_date_sql} as expires";
					$results = DB::select( DB::raw($sql));
					$nextGoalPlan->date_begin = $data['date_end'];
					$nextGoalPlan->date_end = $results[0]->expires;
					//pr($nextGoalPlan); TO DO errors
					$this->update ($nextGoalPlan, $nextGoalPlan->toArray());
				}
			}
		}
        
		

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
		$query->limit($limit)->offset($offset);
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
	public function activateGoalPlan($program_id, $goalPlan) {
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
	//Aliases for future_goal_plan
	public function futureGoalPlan($program_id, $goalPlan) {
		// 1. set the user_goals record to expired
		// 2. identify the future goal and promote it to active
		//
		$future_state_id = Status::get_goal_future_state();
		$result = GoalPlan::where(['id'=>$goalPlan->id])->update(['state_type_id'=>$future_state_id,'expired'=>null]);
		/*$sql = "
        update goal_plans set
            expired= NULL
            , state_type_id = {$this->write_db->escape((int)$future_state_id)}
        where
            id = {$this->write_db->escape((int)$goal_plan->id)}
        ";
		$query = $this->write_db->query ( $sql );
		if (! $query) {
			throw new RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}*/
		// if (isset($goal_plan->is_recurring) && $goal_plan->is_recurring) {
		if (isset ( $goalPlan->next_goal_id ) && $goalPlan->next_goal_id > 0) {
			// advance the future goal to be active 
			$nextGoalPlan = GoalPlan::getGoalPlan( $goalPlan->next_goal_id); 
			//$next_goal_plan = $this->read ( $program_id, $next_goal_id );
			// Only allow 1 future goal plan
			try {
				$this->futureGoalPlan ( ( int ) $nextGoalPlan->program_id, $nextGoalPlan );
			} catch ( Error $x ) {
				echo "ERROR: {$x->getMessage()}" . PHP_EOL;
			}
		}
		// }
	}
	//Aliases for expire_goal_plan
	public function expireGoalPlan($program_id, $goalPlan) {
		// 1. set the user_goals record to expired
		// 2. identify the future goal and promote it to active
		//
		//date("Y-m-d H:i:s")
		$expired_state_id = Status::get_goal_expired_state();
		$result = GoalPlan::where(['id'=>$goalPlan->id])->update(['state_type_id'=>$expired_state_id,'expired'=>now()]);
		//pr($result); die;
		/*$sql = "
		update goal_plans set
			expired= now()
			, state_type_id = {$this->write_db->escape((int)$expired_state_id)}
		where
			id = {$this->write_db->escape((int)$goal_plan->id)}
		";
		$query = $this->write_db->query ( $sql );
		if (! $query) {
			throw new RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}*/
		if (isset ( $goalPlan->is_recurring ) && $goalPlan->is_recurring) {
			if (isset ( $goalPlan->next_goal_id ) && $goalPlan->next_goal_id > 0) {
				// advance the future goal to be active
				$next_goal_id = ( int ) $goalPlan->next_goal_id;
				$nextGoalPlan = GoalPlan::getGoalPlan( $next_goal_id); 
				$goalPlan->load('goalPlanType');
				//$next_goal_plan = $this->read ( $program_id, $next_goal_id );
				
				//if ($goalPlan->goalPlanType->name == GOAL_PLAN_TYPE_EVENT_COUNT) {
				if($goalPlan->goal_plan_type_id == GoalPlanType::getIdByTypeEventcount()) {
					// re-link the events TO DO
					$goal_plan_events = $this->read_list_goal_plan_events ( ( int ) $program_id, array (
							( int ) $goal_plan->id 
					) );
					foreach ( $goal_plan_events [$goal_plan->id]->events as $event_goal ) {
						$this->tie_event_to_goal_plan ( ( int ) $program_id, ( int ) $next_goal_id, ( int ) $event_goal->event_template_id );
					}
				}
				// activate it
				try {
					self::activateGoalPlan( $program_id, $nextGoalPlan );
				} catch ( Error $x ) {
					echo "ERROR: {$x->getMessage()}" . PHP_EOL;
				}
			} else {
				// create a new future goal and tie it back to this one.
				//$expiration_rule = $this->expiration_rules_model->read ( $program_id, $goal_plan->expiration_rule_id );
				$expiration_rule = ExpirationRule::find( $goalPlan->expirations_rule_id);
				//TO DO
				$future_goal_plan_id = $this->create_future_plan ( $goalPlan, $expiration_rule );
				//$next_goal_plan = $this->read ( $program_id, $future_goal_plan_id );
				$nextGoalPlan = GoalPlan::getGoalPlan( $future_goal_plan_id); 
				self::activateGoalPlan( $program_id, $nextGoalPlan );
				//$this->activate_goal_plan ( $program_id, $next_goal_plan );
			}
		}
	
	}
	//Aliases for create_future_plan
	public function createFuturePlan($goalPlan, $expirationRule) {
		if (! isset ( $expirationRule )) {
			// if we were not given an expiration rule, go get it from the goal_plan
			$expirationRule = ExpirationRule::find($goalPlan->expiration_rule_id);
		}
		$nextGoalPlan = self::_newFutureGoal($goalPlan);
		$activeGoalPlanId = $goalPlan->id;
		//pr($nextGoalPlan);
		// Create the Future Goal Plan 
		$futureGoalPlan =self::_insert ( $nextGoalPlan->toArray(), $expirationRule );
		//pr($nextGoalPlan);Check data here
		$futureGoalPlanId = $futureGoalPlan->id;
		$goalPlan->next_goal_id = $futureGoalPlanId;
		// Update the active goal plan's next goal id with the future goal plan id
		// build the query to INSERT an event then run it!

		$result = GoalPlan::where(['id'=>$activeGoalPlanId])->update(['next_goal_id'=>$futureGoalPlanId]);

		// check if we have insert 1 row, cause if we inserted less than 1, then that's wrong...
		// and even worst is that we inserted more than 1, cause clearly we are inserting 1 row...
		if ($result < 0) {
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		// Update the active goal plan's next goal id with the future goal plan id
		// build the query to INSERT an event then run it!

		$result = GoalPlan::where(['id'=>$futureGoalPlanId])->update(['previous_goal_id'=>$activeGoalPlanId]);

		// check if we have insert 1 row, cause if we inserted less than 1, then that's wrong...
		// and even worst is that we inserted more than 1, cause clearly we are inserting 1 row...
		if ($result < 0) {
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		// All of the participants that were assigned to the goal plan, need to also be assigned to the future goal
		$userGoalsToProjectIntoTheFuture = $this->userGoalService->readListByProgramAndGoal ($goalPlan->program_id, $goalPlan->id );
	
		if (!empty ( $userGoalsToProjectIntoTheFuture ) && $userGoalsToProjectIntoTheFuture->count() > 0) {
			foreach ( $userGoalsToProjectIntoTheFuture as $UserGoal ) {
				$userGoalData = [
					'id'=>	$UserGoal->id,
					'user_id'=>	$UserGoal->user_id,
					'user_id'=>	$UserGoal->user_id,
					'goal_plan_id'=> $UserGoal->goal_plan_id,
					'target_value'=> $UserGoal->target_value,
					'achieved_callback_id' => $UserGoal->achieved_callback_id,
					'exceeded_callback_id' => $UserGoal->exceeded_callback_id,
					'factor_before'=> $UserGoal->factor_before,
					'factor_after'=> $UserGoal->factor_after,
					'created_by' => $UserGoal->created_by,
					'modified_by'=> $UserGoal->created_by,
					'next_user_goal_id'=> $UserGoal->next_user_goal_id,
					'previous_user_goal_id'=>$UserGoal->previous_user_goal_id
				];
				// Create the user's future goal plan
				$futureUserGoal = $this->userGoalService::createFutureGoal( $goalPlan, $userGoalData);
			}
		}

		return $futureGoalPlan;
	}
	/* Copy all essential data from given goal plan into a new GoalPlanObject that can be used on to create a new future goal plan.*/
	//Aliases for _new_future_goal
	private function _newFutureGoal($goalPlan) {

		$nextGoalPlan = $goalPlan;
		// set the new goal plan to begin when the previous one expires
		$nextGoalPlan->date_begin = $goalPlan->date_end;
		$nextGoalPlan->date_end = ExpirationRule::speculateNextSpecifiedEndDate ( $goalPlan->date_begin, $goalPlan->date_end );
		$nextGoalPlan->state_type_id =  Status::get_goal_future_state ();
		return $nextGoalPlan;
	}

	//Aliases for delete_future_plan
	public function deleteFuturePlan($goalPlan) {
		// Delete the future goal plan
		GoalPlan::where('id', $goalPlan->next_goal_id)->delete();
		// Update the active goal plan's next goal id with the future goal plan id
		// build the query to INSERT an event then run it!
		$result = GoalPlan::where(['id'=>$goalPlan->id])->update(['next_goal_id'=>null]);
		
		// Delete all of the participant goal plans that were assigned to this future plan - Happens via Cascading delete in the DB,
		//TO DO IN DATABASE MIGRATION - Cascading delete
		// but we need to null out the "next_user_goal_id" column on the active goal plan
		$result = UserGoal::where(['goal_plan_id'=>$goalPlan->id])->update(['next_user_goal_id'=>null]);

	}

}
