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
        $response=[];
		if (!GoalPlan::CONFIG_PROGRAM_USES_GOAL_TRACKER) {
            return response(['errors' => "You can't add goal plan in this program."], 422);
        }
        $data = $request->validated();
        //$response=[];
        try{
            $new_goal_plan= $goalplanservice->add_goal_plan($data,$organization,$program);
            if(!empty($new_goal_plan['goal_plan'])) {
               return $new_goal_plan;
            } else {
                return $response(['errors' => "Goal plan Creation failed"], 422);
            }   

        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }
        return $new_goal_plan;
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

    public function update(GoalPlanRequest $request, Organization $organization, Program $program, GoalPlan $goalplan, GoalPlanService $goalplanservice )
    {
        try{
        $data = $request->validated();
        $update_goal_plan= $goalplanservice->update_goal_plan($data, $goalplan, $organization, $program);
        $response['goal_plan'] = $update_goal_plan;
        if (!empty($goalplan->id)) {
            // Assign goal plans after goal plan updated based on INC-206
            //if assign all current participants then run now
            if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'] == 1)	{
                //$ew_goal_plan->id = $result;
                $assign_response = $goalplanservice->assign_all_participants_now($goalplan, $program);
				$response['assign_msg'] = $goalplanservice->assign_all_participants_res($assign_response);
            }
		}
    }
    catch (\Exception $e )    {
        return response(['errors' => $e->getMessage()], 422);
    }
        return $response;
    }

    public function destroy(Organization $organization, Program $program, GoalPlan $goalplan)
    {
        $goalplan->delete();
        return response(['success' => true]);
    }
}