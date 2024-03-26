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
use App\Models\GoalPlansEvent;

use Validator;

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

		// check if we have a valid $goal_plan->name format and that it is unique
		if ($this->isValidGoalPlanByName ( $program->id, $data['name'] )) {
			throw new \InvalidArgumentException ( 'Invalid goal plan name passed.Goal plan name ' . $data['name'] . ' is already taken', 400 );
		}
		$data['organization_id'] = $organization->id;
		$data['program_id'] = $program->id;
		$data['created_by'] = auth()->user()->id;

		$expiration_rule = ExpirationRule::find($data['expiration_rule_id']);
		//Create goal plan
		$state_future_id = Status::get_goal_future_state ();
		$newGoalPlan = self::_insert($data, $state_future_id,$expiration_rule);
		$response['goal_plan'] = $newGoalPlan;

        if (!empty($newGoalPlan->id)) {
			//this goal has been activated
			/*TO DO - If someone create goal plan in future or expired then need check and set them also. Currently it is like old system
			TO DO Get statusid from date_begin & date_end and then apply activate/future/expire goal plan function accordingly*/
			self::activateGoalPlan ( $newGoalPlan->program_id, $newGoalPlan );
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
		//unset($validated['custom_email_template']);
    }
	private static function _insert($data, $state_type_id, $expiration_rule) {

		$expiration_date = ExpirationRule::compile($expiration_rule, $data['date_begin'], $data['date_end'], isset ( $data['custom_expire_offset'] ) ? $data['custom_expire_offset'] : null, isset ( $data['custom_expire_units'] ) ? $data['custom_expire_units'] : null, isset ( $data['annual_expire_month'] ) ? $data['annual_expire_month'] : null, isset ( $data['annual_expire_day'] ) ? $data['annual_expire_day'] : null );

		$data['date_end'] =  $expiration_date;

		// build the query to INSERT an event then run it!
		//$data['state_type_id'] = GoalPlan::calculateStatusId($data['date_begin'], $data['date_end']);
		$data['state_type_id'] = $state_type_id;
		if(isset($data['id'])) {
			unset($data['id']);
		}
		$newGoalPlan = GoalPlan::create(  $data );
		return $newGoalPlan;
	}
	public function editGoalPlan(GoalPlan $goalPlan, $data, $program, $organization) {
		$response=[];

		$data['modified_by']=auth()->user()->id;

		//set any fields here
		//if assign all current participants then run now
		if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'])	{
			$assignResponse =self::assignAllParticipantsNow($goalPlan, $program);
			$response['assign_msg'] = self::assignAllParticipantsRes($assignResponse);
		}
		//Update
		try {
            $goalPlanEvents = (array) $data['events'];
            unset($data['events']);

			$result = $this->update($goalPlan, $data);

            GoalPlansEvent::where('goal_plans_id', $goalPlan->id)->delete();
            if (!empty($goalPlanEvents)) {
                foreach ($goalPlanEvents as $goalPlanEventID) {
                    GoalPlansEvent::create([
                        'goal_plans_id' => $goalPlan->id,
                        'event_id' => $goalPlanEventID,
                    ]);
                }
            }

			return $result;
			/* TO DO

			* // If the event template was created redirect the user to the edit page
			TO DO if (has_resource_permission(RESOURCE_GOAL_PLANS_TIE_EVENT)) {*/
				/*try {
					$gpes = self::readListGoalPlanEvents($program->id,[$goalPlanId]);
					$gpe = $gpes[(int) $goalPlanId];
					//TO DO
					$this->update_tied_goal_plan_events($goalPlanId, $gpe, $assigned_events, $unassigned_events);
				} catch (Exception $e) {
					$this->_add_error_message($e->getMessage());
				}
			}*/

			//php_includes\application\controllers\manager\program_settings.php
			//TO DO - Find if goal plan is editable
			//TO DO - Event goals event_goals code unassigned_events/assigned_events
			//TO DO - Load events which is different from create goalplan(Pending to discusss)
		 }
		catch (Exception $e) {
			return response(['errors' => $e->getMessage()], 422);
		}

	}
	private function update($goalPlan, $data)
    {
		// Read the goal plan type so we can determine how to further validate the submitted data (Code is in GoalPlanRequest)
		$validator = Validator::make($data, [
			'achieved_event_id'=>'sometimes|integer|achieved_event_type_standard|achieved_event_type_badge',
            'exceeded_event_id'=>'required_if:goal_plan_type_id,1|exceeded_event_type_check',
			'date_begin'=> 'required|date_format:Y-m-d',
            'date_end'=>'required_if:expiration_rule_id,6|date_format:Y-m-d|after:date_begin', //if expiration_rule_id is specific date
		]);
		// check if we have a valid $goal_plan->name format and that it is unique
		if (! $this->isValidGoalPlanByNameNotThisId ( $goalPlan->program_id, $data['name'], $goalPlan->id )) {
			throw new \InvalidArgumentException ( 'Invalid goal plan name passed. Goal plan name ' . $data['name'] . ' is already taken', 400 );
		}

		$response=[];
		$currentGoalPlan = clone $goalPlan;

		$active_state_id = Status::get_goal_active_state ();
		$future_state_id = Status::get_goal_future_state ();
		$expired_state_id = Status::get_goal_expired_state ();

		// This means only the most recent expired plan can be edited
		if ($currentGoalPlan->state_type_id == $expired_state_id) {
			if ($data['is_recurring'] && !empty ( $currentGoalPlan->next_goal_id )) {
				$nextGoalPlan = GoalPlan::getGoalPlan( $currentGoalPlan->next_goal_id);
				//$this->read ( $program_account_holder_id, ( int ) $goalPlan->next_goal_id );
				if(!empty($nextGoalPlan)) {
					if ($nextGoalPlan->state_type_id != $active_state_id && $nextGoalPlan->state_type_id != $future_state_id) {
						throw new \RuntimeException ( 'Only the most recent expired goal plan can be edited.' );
					}
				}
			}
		}
		// read the current goal plans expiration rules
		$expirationRule = ExpirationRule::getExpirationRule ($currentGoalPlan->expiration_rule_id );
		// Don't allow the goal start date to overlap with the previous goal cycle
		if ($data['is_recurring'] && ! empty ( $currentGoalPlan->previous_goal_id )) {
			$previousGoalPlan = GoalPlan::getGoalPlan( $currentGoalPlan->previous_goal_id);
			if (isset ( $previousGoalPlan )) {
				//pr($previousGoalPlan);
				$date1 = new DateTime ( $previousGoalPlan->date_end );
				$date2 = new DateTime ( $data['date_begin'] );
				if ($date1 > $date2) {
					throw new \InvalidArgumentException ( 'The Goal Plans date_begin cannot be less than the previous goal cycles date_end (' . $previousGoalPlan->date_end . ')' );
				}
			}
		}

		// $goalPlan updated here
		$update_result  = $goalPlan->update( $data );
		$response['goal_plan']= $goalPlan;
		// If the goal is set to active....
		if ($currentGoalPlan->state_type_id == $active_state_id) {
			// Hate to do it like this...
			// Check if the goal plan is set to recurring. Create\Delete the future goal plan as needed
			if ($goalPlan->is_recurring && empty($currentGoalPlan->next_goal_id)) {
				$futureGoalPlan = $this->createFuturePlan ($goalPlan, $expirationRule ); // TO DO
			} else if (! $goalPlan->is_recurring && !empty ( $currentGoalPlan->next_goal_id )) {
				self::deleteFuturePlan ( $currentGoalPlan );
			}
		}

		if ($goalPlan->goal_plan_type_id == GoalPlanType::getIdByTypeEventcount()) {
			if (!empty($goalPlan->achieved_event_id)) {
				 $this->untieEventFromGoalPlan ( ( int ) $goalPlan->program_id, ( int ) $goalPlan->id, ( int ) $goalPlan->achieved_event_id );
			}
			if (!empty($goalPlan->exceeded_event_id)) {
				$this->untieEventFromGoalPlan ( ( int ) $goalPlan->program_id, ( int ) $goalPlan->id, ( int ) $goalPlan->exceeded_event_id );
			}
		}

		//In future goal plan creation some fieds are updated
		$currentGoalPlan = GoalPlan::getGoalPlan($goalPlan->id);
		if (self::needsActivated($currentGoalPlan)) {
			self::activateGoalPlan( $currentGoalPlan->program_id, $currentGoalPlan ); // TO DO
		}
		if (self::needsExpired ( $currentGoalPlan )) {
			self::expireGoalPlan ( $currentGoalPlan->program_id, $currentGoalPlan ); // TO DO
		}
		if (self::needsFutured ( $currentGoalPlan )) {
			self::futureGoalPlan( $currentGoalPlan->program_id, $currentGoalPlan ); // TO DO
		}

		// RULES FOR MOVING THE DATES ON THE FUTURE GOAL PLAN
		if ($goalPlan->is_recurring && !empty( $currentGoalPlan->next_goal_id )) {
			$nextGoalPlan = GoalPlan::getGoalPlan( $currentGoalPlan->next_goal_id);
			if (isset ( $nextGoalPlan )) {
				$date1 = new DateTime ( $nextGoalPlan->date_begin );
				$date2 = new DateTime ( $goalPlan->date_end );
				if ($date1 < $date2) {
					// throw new InvalidArgumentException('The Goal Plans date_begin cannot be less than the previous goal cycles date_end (' . $previous_goal_plan->date_end . ')');
					$end_date_sql =  "'".$goalPlan->date_end."'";
					// Store the active goal plan's end date
					$active_goal_end_date = $goalPlan->date_end;
					// Create the Future Goal Plan
					// use the end date of the active goal plan and the expiration rule to set the end date for the future goal goal
					switch ($expirationRule->name) {
						case "Custom" :
						case "Specified" :
							// Need to do some math to figure out how many days are between the active goal plans start and end dates
							$date1 = new DateTime ( $goalPlan->date_begin );
							$date2 = new DateTime ( $active_goal_end_date );
							$diff_in_days = $date2->diff ( $date1 )->format ( "%a" );
							$end_date_sql = "date_add('".$active_goal_end_date."', interval {$diff_in_days} DAY)";
							break;
						case "End of Following Year" :
						case "End of Next Year" :
						case "1 Year" :
							$active_goal_end_date = date ( "Y-12-31", strtotime ( date ( "Y-m-d", strtotime ( $goalPlan->date_end ) ) . " +1 year" ) );
							$end_date_sql = "'".$active_goal_end_date."'";
							break;
						case "12 Months" :
						case "9 Months" :
						case "6 Months" :
						case "3 Months" :
						default :
							$offset = $expirationRule->expire_offset;
							$units = $expirationRule->expire_units;
							$start_date =$active_goal_end_date;
							$end_date_sql = "date_add('".$start_date."', interval {$offset} {$units})";
					}
					$sql = "select {$end_date_sql} as expires";
					$results = DB::select( DB::raw($sql));
					$nextGoalPlan->date_begin = $goalPlan->date_end;
					//$nextGoalPlan->achieved_event_id  = 3;
					$nextGoalPlan->date_end = $results[0]->expires;
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
	//Alias for assign_all_participants_now
    public function assignAllParticipantsNow($goalPlan, $program) {
	    //$max = 50000;
        //This is temporary solution - TO DO implementation of original function
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
	  	$success_user= $fail_user = $success_future_user = $fail_future_user = $already_assigned = [];
        if(!empty($users)) {
			$user_goal=[];
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

			$user_goal = $this->userGoalService::_copyUserGoalDataFromGoalPlan($goalPlan);

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

	//Alias to read_active_by_program
	public static function readActiveByProgram($program, $offset = 0, $limit = 10, $order_column = 'name', $order_direction = 'asc') {
		$state = Status::get_goal_active_state();
		return self::readListByProgramAndState ( $program, $state, $offset, $limit, $order_column, $order_direction );

	}
	//Alias to read_list_by_program_and_state
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
            throw new \Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
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

	//Alias to _select_goal_plan_info
	private static function _selectGoalPlanInfo() {
		$query = DB::table('goal_plans AS gp');
		//$query = GoalPlan::from( 'goal_plans as gp' );
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
	//Alias for  needs_activated
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
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		return ( bool ) ($result[0]->status == 'active');
	}

	//Alias for  needs_expired
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
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		return ( bool ) ($result[0]->status == 'expired');
	}
	//Alias for needs_futured
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
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		return ( bool ) ($result[0]->status == 'future');
	}
	//Alias for activate_goal_plan
	public function activateGoalPlan($program_id, $goalPlan) {
		// 1. set the user_goals record to expired
		// 2. identify the future goal and promote it to active
		//
		$expired_state_id = Status::get_goal_expired_state ();
		$active_state_id = Status::get_goal_active_state ();
		// advance current the future goal to be active
		$result= GoalPlan::whereRaw("id = {$goalPlan->id} and now() between date_begin and date_end")->update(['state_type_id'=>$active_state_id,'expired'=>null]);

		if ($result == 0) {
			// maybe there isn't a future goal, or it's start date has been changed
			return false;
			// TODO: have different cron to look for goal plans that need to be activated?
		} else if ($result > 1) {
			throw new \RuntimeException ( "Data Corruption: More than 1 record was changed!", 500 );
		} else {
			if (isset ( $goalPlan->is_recurring ) && $goalPlan->is_recurring) {
				if (! isset ( $goalPlan->next_goal_id ) || $goalPlan->next_goal_id <= 0) {
					// goal plan is recurring, but there is no future goal plan defined
					$expirationRule = ExpirationRule::getExpirationRule ($goalPlan->expiration_rule_id );
					$futureGoalPlan =$this->createFuturePlan ($goalPlan, $expirationRule );
				}
			}
			return true;
		}

	}
	//Alias for future_goal_plan
	public function futureGoalPlan($program_id, $goalPlan) {
		// 1. set the user_goals record to expired
		// 2. identify the future goal and promote it to active
		//
		$future_state_id = Status::get_goal_future_state();
		$result = GoalPlan::where(['id'=>$goalPlan->id])->update(['state_type_id'=>$future_state_id,'expired'=>null]);

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
	}
	//Alias for expire_goal_plan
	public function expireGoalPlan($programId, $goalPlan) {
		// 1. set the user_goals record to expired
		// 2. identify the future goal and promote it to active
		//
		$expiredStateId = Status::get_goal_expired_state();
		$result = GoalPlan::where(['id'=>$goalPlan->id])->update(['state_type_id'=>$expiredStateId,'expired'=>now()]);
		if (! $result) {
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		if (isset ( $goalPlan->is_recurring ) && $goalPlan->is_recurring) {
			if (isset ( $goalPlan->next_goal_id ) && $goalPlan->next_goal_id ) {
				// advance the future goal to be active
				$nextGoalId = ( int ) $goalPlan->next_goal_id;
				$nextGoalPlan = GoalPlan::getGoalPlan( $nextGoalId);
				$goalPlan->load('goalPlanType');

				if($goalPlan->goal_plan_type_id == GoalPlanType::getIdByTypeEventcount()) {
					// re-link the events TO DO
					$goalPlanEvents = $this->readListGoalPlanEvents ( ( int ) $programId, array (
							( int ) $goalPlan->id
					) );
					foreach ( $goalPlanEvents [$goalPlan->id]->events as $eventGoal ) {
						$this->tieEventToGoalPlan ( ( int ) $programId, ( int ) $nextGoalId, ( int ) $eventGoal->event_id );
					}
				}
				// activate it
				try {
					self::activateGoalPlan( $programId, $nextGoalPlan );
				} catch ( Error $x ) {
					echo "ERROR: {$x->getMessage()}" . PHP_EOL;
				}
			} else {
				// create a new future goal and tie it back to this one.
				$expirationRule = ExpirationRule::find( $goalPlan->expirations_rule_id);
				$futureGoalPlan = $this->createFuturePlan ( $goalPlan, $expirationRule );
				$nextGoalPlan = GoalPlan::getGoalPlan( $futureGoalPlan->id);
				self::activateGoalPlan( $programId, $nextGoalPlan );
			}
		}

	}
	//Alias for create_future_plan
	public function createFuturePlan($goalPlan, $expirationRule) {

		if (! isset ( $expirationRule )) {
			// if we were not given an expiration rule, go get it from the goal_plan
			$expirationRule = ExpirationRule::find($goalPlan->expiration_rule_id);
		}
		$nextGoalPlan = self::_newFutureGoal($goalPlan);
		$activeGoalPlanId = $goalPlan->id;

		// Create the Future Goal Plan
		$state_future_id = Status::get_goal_future_state ();
		$futureGoalPlan = self::_insert ( $nextGoalPlan->toArray(), $state_future_id, $expirationRule );
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
		if (!isset($result)) {
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		// All of the participants that were assigned to the goal plan, need to also be assigned to the future goal
		$userGoalsToProjectIntoTheFuture = $this->userGoalService->readListByProgramAndGoal ($goalPlan->program_id, $goalPlan->id );

		if (!empty ( $userGoalsToProjectIntoTheFuture ) && $userGoalsToProjectIntoTheFuture->count() > 0) {
			foreach ( $userGoalsToProjectIntoTheFuture as $UserGoal ) {
				$userGoalData = $this->userGoalService::_convertUserGoalData($UserGoal);
				// Create the user's future goal plan
				$futureUserGoal = $this->userGoalService::createFutureGoal( $goalPlan, $userGoalData);
			}
		}

		return $futureGoalPlan;
	}
	/* Copy all essential data from given goal plan into a new GoalPlanObject that can be used on to create a new future goal plan.*/
	//Alias for _new_future_goal
	private function _newFutureGoal($goalPlan) {
		$nextGoalPlan = clone $goalPlan;
		unset($nextGoalPlan->expired);
		unset($nextGoalPlan->created_at);
		unset($nextGoalPlan->updated_at);
		$nextGoalPlan->modified_by = auth()->user()->id;
		// set the new goal plan to begin when the previous one expires

		$nextGoalPlan->date_end = ExpirationRule::speculateNextSpecifiedEndDate ( $goalPlan->date_begin, $goalPlan->date_end );
		//Do this after above calculation
		$nextGoalPlan->date_begin = $goalPlan->date_end;

		$nextGoalPlan->state_type_id =  Status::get_goal_future_state ();
		return $nextGoalPlan;
	}

	//Alias for delete_future_plan
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
		return;
	}
	//Alias for read_list_goal_plan_events
	public function readListGoalPlanEvents($programId, $goalPlanIds = array()) {
		$query = self::_selectGoalPlanInfo();
		$query->addSelect([
			'events.name as event_name',
			'events.id as event_id'
		]);
		$query->leftJoin('goal_plans_events AS gpe', 'gpe.goal_plans_id', '=', 'gp.id');
		$query->leftJoin('events AS events', 'events.id', '=', 'gpe.event_id');
		$query->whereIn('gp.id', $goalPlanIds );
		$query->where('gp.program_id', '=', $programId);

		try {
            $results = $query->get();
        } catch (Exception $e) {
            throw new \Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
		//TO DO - Check other possible ways to achieve this
		$return_data = array ();
		$results= $results->toArray();
		if (is_array ( $results ) && count ( $results ) > 0) {
			foreach ( $results as $row ) {
				$events = array ();
				if (! isset ( $return_data [$row->id] )) {
					$return_data [$row->id] = (object)[];
					$return_data [$row->id]->events = array ();
				}
				foreach ( $row as $n => $v ) {
					if (! isset ( $return_data [$row->id]->events [$row->event_id] )) {
						$return_data [$row->id]->events [$row->event_id] = (object)[];
					}
					$return_data [$row->id]->events [$row->event_id]->$n = $v;
				}
			}
		}
		return $return_data;
	}

	//Alias for tie_event_to_goal_plan
	public function tieEventToGoalPlan($programId = 0, $goalPlanId = 0, $eventId = 0) {

		/*if (! $this->is_valid_goal_plan ( $program_account_holder_id, $goal_plan_id )) {
			throw new InvalidArgumentException ( 'Invalid "goal_plan_id" passed, record not found (' . $goal_plan_id . ')', 400 );
		}
		if (! $this->event_templates_model->is_valid_event_template ( $program_account_holder_id, $event_template_id )) {
			throw new InvalidArgumentException ( 'Invalid "event_template_id" passed, record not found (' . $event_template_id . ')', 400 );
		}*/
		// Only allow certain types of events to be assigned
		$allowed_event_types = array (
			config('global.event_type_standard'),
			config('global.event_type_peer2peer'),
			config('global.event_type_badge'),
			config('global.event_type_peer2peer_badge'),
		);
		// Do not allow events of type Activation to be added
		$event = Event::read( $programId, $eventId)->load("eventType");
		if (! in_array ( $event->eventType->name, $allowed_event_types )) {
			throw new \InvalidArgumentException ( 'Invalid "event_id" passed, only events of these types can be assigned to a leaderboard: ' . implode ( ', ', $allowed_event_types ), 400 );
		}
		// Do not allow an event to be tied more than once

		$goalPlans = self::readListGoalPlanEvents($programId,[$goalPlanId]);
		//TO DO testing
		$goalPlan = $goalPlans [$goalPlanId];
		if (count ( $goalPlan->events ) > 0) {
			foreach ( $goalPlan->events as $tiedEventId => $tiedEventName ) {
				if ($tiedEventId == $eventId) {
					return true;
					// throw new InvalidArgumentException('Invalid "event_template_id" passed, this event is already tied to this goal_plan', 400);
				}
			}
		}
		$newGoalPlan = GoalPlansEvent::create(['event_id'=> $eventId,'goal_plans_id'=> $goalPlanId]);
		if (! $newGoalPlan) {
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		return true;
	}
	//Alias for untie_event_from_goal_plan
	public function untieEventFromGoalPlan($programId = 0, $goalPlanId = 0, $eventId = 0) {
		/* TO DO Required or Not?
		if (! $this->is_valid_goal_plan ( $program_account_holder_id, $goal_plan_id )) {
			throw new InvalidArgu
			mentException ( 'Invalid "goal_plan_id" passed, record not found (' . $goal_plan_id . ')', 400 );
		}
		if (! $this->event_templates_model->is_valid_event_template ( $program_account_holder_id, $event_template_id )) {
			throw new InvalidArgumentException ( 'Invalid "event_template_id" passed, record not found (' . $event_template_id . ')', 400 );
		}*/
		try {
			$result = GoalPlansEvent::where(['event_id'=> $eventId, 'goal_plans_id'=> $goalPlanId])->delete();

        } catch (Exception $e) {
            throw new \Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
		return true;
	}

	//Alias for is_valid_goal_plan_by_name_not_this_id
	public function isValidGoalPlanByNameNotThisId($programId = 0, $goalPlanName = '', $goalPlanId = 0) {

		$linkedListIds = implode ( ',', $this->getGoalPlanList ( $goalPlanId ) );
		// build the query statement to check if we have this $goalPlanId
		$query = GoalPlan::from( 'goal_plans as gp' );
		$query->selectRaw('COUNT(gp.`id`) AS count');
		$query->where('gp.program_id', '=', $programId);
		$query->where('gp.name', '=', $goalPlanName);
		$query->whereRaw("gp.`id` NOT IN ({$linkedListIds})");
		$result = $query->get();
		if (! $result || $result->count() < 1) {
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		// check if $program_id is in the database and return the resulting boolean
		// back to the function caller
		return ( bool ) (( int ) $result[0]->count == 0);
	}

	/** Returns a list of all of the future and past goal plans that are linked together
	 * via this id */
	//Alias for get_goal_plan_list
	protected function getGoalPlanList($goalPlanId) {
		$linkedListIds = array ();
		// Select all of the id's descending the linked list
		$sql = " SELECT
                     @r AS _id,
                     (
                        SELECT  @r := next_goal_id
                        FROM    goal_plans
                        WHERE   id = _id
                     ) AS parent,
                     @l := @l + 1 AS lvl
                    FROM
                        (
                         SELECT  @r := {$goalPlanId},
                                 @l := 0
                         ) vars,
                         goal_plans h
                    WHERE    @r <> 0
                    ORDER BY lvl DESC";
		// run the query that we built above
		$descendant_ids = DB::select( DB::raw($sql));
		if (is_array ( $descendant_ids ) && count ( $descendant_ids ) > 0) {
			foreach ( $descendant_ids as $descendant ) {
				if (isset ( $descendant->_id ) && $descendant->_id > 0) {
					$linkedListIds [] = $descendant->_id;
				}
			}
		}
		// Select all of the id's descending the linked list
		$sql = "     SELECT
                     @r AS _id,
                     (
                        SELECT  @r := previous_goal_id
                        FROM    goal_plans
                        WHERE   id = _id
                     ) AS parent,
                     @l := @l + 1 AS lvl
                    FROM
                        (
                         SELECT  @r := {$goalPlanId},
                                 @l := 0
                         ) vars,
                         goal_plans h
                    WHERE    @r <> 0
                    ORDER BY lvl DESC";
		// run the query that we built above
		$ancestor_ids = DB::select( DB::raw($sql));
		if (is_array ( $ancestor_ids ) && count ( $ancestor_ids ) > 0) {
			foreach ( $ancestor_ids as $ancestor ) {
				if (isset ( $ancestor->_id ) && $ancestor->_id > 0) {
					$linkedListIds [] = $ancestor->_id;
				}
			}
		}
		return array_unique ( $linkedListIds );

	}

	//Alias for is_valid_goal_plan_by_name
	public function isValidGoalPlanByName($programId = 0, $goalPlanName = '') {
		// build the query statement to check if we have this $goal_plan_id
		$query = GoalPlan::from( 'goal_plans as gp' );
		$query->selectRaw('COUNT(gp.`id`) AS count');
		$query->where('gp.program_id', '=', $programId);
		$query->where('gp.name', '=', $goalPlanName);
		$result = $query->get();
		// check if we have a valid query object
		if (! $result) {
			throw new \RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		// check if $program_id is in the database and return the resulting boolean
		// back to the function caller
		return ( bool ) (( int ) $result[0]->count > 0);
	}

	//Alias for update_tied_goal_plan_events
    /*private function updateTiedGoalPlanEvents($goalPlanId, $gpe, $assigned, $unassigned)
    {
        // Get a list of all of the award levels and their amounts that are currently tied to this event from SOAP API
        // $already_assigned_events = $this->leaderboards_model->read_leaderboard((int)$this->program->account_holder_id, (int)$event_template->id, 0, 999);
        // get the list of already assigned things from the EventLeaderboardObj
        // this array does not contain a full EventTemplate object, only the id and the name
        $already_assigned = $gpe->events;
        // Determine which award levels need to be tied and/or updated
        if (is_array($assigned) && count($assigned) > 0 && is_array($already_assigned) && count($already_assigned) > 0) {
            for ($i = 0; $i < count($assigned); ++$i) {
                foreach ($already_assigned as $assigned_event_id => $goal_plan) {
                    // if the assigned award level id appears in the already assigned levels list, set the tied flag
                    if ($assigned[$i] == $assigned_event_id) {
                        unset($assigned[$i]);
                        break;
                    }
                }
            }
            $assigned = array_values($assigned);
        }
        // Determine which award levels need to be untied
        if (is_array($unassigned) && count($unassigned) > 0 && is_array($already_assigned) && count($already_assigned) > 0) {
            for ($i = 0; $i < count($unassigned); ++$i) {
                foreach ($already_assigned as $assigned_event_id => $event) {
                    // if the assigned award level id appears in the already assigned levels list, set the tied flag
                    if ($unassigned[$i] == $assigned_event_id) {
                        // $unassigned[$i]['untie'] = true;
                        $this->goal_plans_model->untie_event_from_goal_plan((int) $this->program->account_holder_id, (int) $goal_plan_id, (int) $assigned_event_id);
                        break;
                    }
                }
            }
        }
        $can_tie_award_level = has_resource_permission(RESOURCE_GOAL_PLANS_TIE_EVENT);
        // loop through the assigned award levels and make sure they are tied and update their amounts as needed
        if (is_array($assigned) && count($assigned) > 0 && $can_tie_award_level) {
            foreach ($assigned as $assigned_event) {
                // Try to tie the award level to the event template, this will throw an exception if it is already done
                $this->goal_plans_model->tie_event_to_goal_plan((int) $this->program->account_holder_id, (int) $goal_plan_id, (int) $assigned_event);
            }
        }
    }*/

}
