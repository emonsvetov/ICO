<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GoalPlanRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
use App\Services\ProgramService;
use App\Services\GoalPlanService;
use App\Models\User;
use App\Models\UserGoal;
//use App\Models\EmailTemplate;
//use App\Models\User;
//use App\Models\Role;
use DB;


class GoalPlanController extends Controller
{
    public function store(GoalPlanRequest $request, Organization $organization, Program $program, GoalPlanService $goalplanservice)
    {
        //pr($request->all()); die;
        $data = $request->validated();
        $response=[];
        try{
            $new_goal_plan= $goalplanservice->add_goal_plan($data,$organization,$program);
            if(!empty($new_goal_plan['goal_plan'])) {
                $response['goal_plan'] = $new_goal_plan['goal_plan'];
                if(!empty($new_goal_plan['assign_all_participants']['success_count']) && $new_goal_plan['assign_all_participants']['success_count'] >= 1) {
                    $response['msg'] = $new_goal_plan['assign_all_participants']['success_count']. " participant(s) assigned!";
                }
                if(!empty($new_goal_plan['assign_all_participants']['fail_count']) && $new_goal_plan['assign_all_participants']['fail_count'] >= 1) {
                    $response['msg'] = $new_goal_plan['assign_all_participants']['fail_count']. " participant(s) assignment failed!";
                }
            } else {
                return $response(['errors' => "Goal plan Creation failed"], 422);
            }   

        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }
        return $response;
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