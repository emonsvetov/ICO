<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GoalPlanRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
use App\Services\ProgramService;
use App\Models\User;
use App\Models\UserGoal;
//use App\Models\EmailTemplate;
//use App\Models\User;
//use App\Models\Role;
use DB;


class GoalPlanController extends Controller
{
    public function store(GoalPlanRequest $request, Organization $organization, Program $program, ProgramService $programService)
    {
        //pr($request->all()); die;
        if ( !( $organization->id == $program->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }
        $data = $request->validated();

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
         if( empty($data['date_end']) )   { //default custom expire date to 1 year from today
            $data['date_end'] = date('Y-m-d', strtotime('+1 year'));
         }
        // Default custom expire date to 1 year from today
        //$request->request->add(['date_end'=>date('Y-m-d', strtotime('+1 year'))]);
        //$request->goal_measurement_label = '$';
      //  if( empty($data['state_type_id']) )   {
            $data['state_type_id'] = GoalPlan::calculateStatusId($data['date_begin'], $data['date_end']);
        //}
       // pr($data['state_type_id']);
         // All goal plans use standard events except recognition goal
         $event_type_needed = 1;//standard
         //if Recognition Goal selected then set 
         if (isset($request->goal_plan_type_id) && ($request->goal_plan_type_id == 3)) {
            $event_type_needed = 5; // Badge event type;
         }
        // Get the appropriate events for this goal plan type - this is old site code - Pending
        //$events = $this->event_templates_model->readListByProgram((int) $this->program->account_holder_id, array(
        // $event_type_needed,
        // ), 0, 9999);\
        $user_id=1;
        $new_goal_plan = GoalPlan::create(  $data +
        [
            'organization_id' => $organization->id,
            //'state_type_id'=>1, //not found in create function of old system
            'program_id' => $program->id, 
            'progress_notification_email_id'=>1, //pending
            'created_by'=>auth()->user()->id, //pending
        ] );
        $assign_all_participants_now=1;
     //   pr($new_goal_plan);
        //if ($new_goal_plan > 0) {
            // Assign goal plans after goal plan created based on INC-206
            //if assign all current participants then run now
           // $users = $programService->getParticipants($program, true);
           // pr($users);
            if($assign_all_participants_now==1){
                //$new_goal_plan->id = $result;
                $this->assign_all_participants_now($user_id, $new_goal_plan, $program, $programService);
            }
           // debug('there');
            //redirect('/manager/program-settings/edit-goal-plan/' . $result);
       // } else {
        
       //     return response(['errors' => 'Goal plan Creation failed'], 422);
       // }
        //after this code pending
        // unset($validated['custom_email_template']);
        return response([ 'new_goal_plan' => $new_goal_plan ]);
       
	}
    public function index( Organization $organization, Program $program )
    {
        if ( $organization->id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }
        $where[]=['program_id','=', $program->id];
        $today = today()->format('Y-m-d');
        $state_type_id =  request()->get('status');
        
        if($state_type_id)
            $where[]=['state_type_id', '=', $state_type_id];



        //pr($where); 
       // die;
        $goal_plans = GoalPlan::where('organization_id', $organization->id)
                        //->where('program_id', $program->id)
                        ->where($where)
                        ->orderBy('name')
                        ->with(['goalPlanType'])
                        ->get();

        if ( $goal_plans->isNotEmpty() )
        {
            return response( $goal_plans );
        }

        return response( [] );
    }
    public function show( Organization $organization, Program $program, GoalPlan $goalplan )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $goalplan->program_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        if ( $goalplan )
        {
            $goalplan->load('GoalPlanType');
            return response( $goalplan );
        }

        return response( [] );
    }

    public function update(GoalPlanRequest $request, Organization $organization, Program $program, GoalPlan $goalplan )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $goalplan->program_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        if ( $goalplan->organization_id != $organization->id )
        {
            return response(['errors' => 'No Program Found'], 404);
        }

        $data = $request->validated();
        $data['state_type_id'] = GoalPlan::calculateStatusId($data['date_begin'], $data['date_end']);
        $goalplan->update( $data );

        return response([ 'goalplan' => $goalplan ]);
    }
    public function destroy(Organization $organization, Program $program, GoalPlan $goalplan)
    {
        $goalplan->delete();
        return response(['success' => true]);
    }
    protected function assign_all_participants_now($user_id, $goal_plan, $program, $programService) {
	    //$max = 50000;
        //This is temporary solution - pending implemntation of original function
        pr($goal_plan);
        $users = $programService->getParticipants($program, true);
        $users->load('status');
       //Pending to implement this large function 
	   //$data = $this->users_model->readParticipantListWithProgramAwardLevelObject((int) $account_holder_id, 0, '', 0, $max, 'last_name', 'asc', array());
	    $available_statuses = array("Active","Pending Activation","New");
        $added_info = array();
        if(!empty($users)) { 
            foreach($users as $user){
                $valid_check = true;
                //check for duplicates
                foreach($added_info as $val){
                    if($val['goal_plan_id']==$goal_plan->id && $val['users_id']==$user_id){
                        $valid_check = false;
                        break;
                    }  
	            }
                $user_goal=[];
                $user_id = $user->id;
              //  pr($goal_plan->date_begin);
                // Copy the submitted info into the user's goal plan object
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
                if (in_array($user->status->status, $available_statuses) && $valid_check) {
                    pr($user_goal);
                    $added_info=array("goal_plan_id"=>$goal_plan->id,"users_id"=>$user_id);
                    $response = UserGoal::add($user_goal,$goal_plan);
                    pr($response);
                  // $new_user_goal[] = UserGoal::create($user_goal);
    		}
        }
	    }
	}

}