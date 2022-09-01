<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GoalPlanRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
//use App\Models\EmailTemplate;
//use App\Models\User;
//use App\Models\Role;
use DB;


class GoalPlanController extends Controller
{
    public function store(GoalPlanRequest $request, Organization $organization, Program $program)
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
       
        $new_goal_plan = GoalPlan::create(  $data +
        [
            'organization_id' => $organization->id,
            //'state_type_id'=>1, //not found in create function of old system
            'program_id' => $program->id, 
            'progress_notification_email_id'=>1, //pending
            'created_by'=>1, //pending
        ] );
        
        if ( !$new_goal_plan )
        {
        return response(['errors' => 'Goal plan Creation failed'], 422);
        }   else {
            // Assign goal plans after goal plan created based on INC-206
            //if assign all current participants then run now
            /*if($assign_all_participants_now==1){
                $goal_plan->id = $result;
                $this->assign_all_participants_now($this->program->account_holder_id, $goal_plan);
            }
            redirect('/manager/program-settings/edit-goal-plan/' . $result);*/
        }
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

}