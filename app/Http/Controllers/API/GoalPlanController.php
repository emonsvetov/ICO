<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GoalPlanRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
use App\Services\GoalPlanService;

class GoalPlanController extends Controller
{
    public function store(GoalPlanRequest $request, Organization $organization, Program $program, GoalPlanService $goalplanservice)
    {

		if (!GoalPlan::CONFIG_PROGRAM_USES_GOAL_TRACKER) {
            return response(['errors' => "You can't add goal plan in this program."], 422);
        }
        if ($program->isShellProgram()) {
            return response(['errors' => "Invalid program id passed, you cannot create a goal plan in a shell program"], 422);
        }
        
        $data = $request->validated();

        try{
            $response= $goalplanservice->add_goal_plan($data,$organization,$program);
            return response($response);

        } catch (\Exception $e )    {
            return response(['errors' => 'Goal plan Creation failed','e'=>$e->getMessage()], 422);
        }
	}

    public function index( Organization $organization, Program $program )
    {
        if ( $organization->id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $where = [
            'program_id' => $program->id,
            'organization_id' => $organization->id
        ];

        $status =  request()->get('status');
        
        if( $status )
        {
            $status_id = GoalPlan::getStatusIdByName($status);
            if( $status_id) {
                $where[] = ['state_type_id', '=', $status_id];
            }
        }

        $goal_plans = GoalPlan::where($where)
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
        if (!GoalPlan::CONFIG_PROGRAM_USES_GOAL_TRACKER) {
            return response(['errors' => "You can't add goal plan in this program."], 422);
        }
        try{
        $data = $request->validated();
        $response= $goalplanservice->update_goal_plan($data, $goalplan, $organization, $program);
       // $response = $update_goal_plan;
        /*if (!empty($goalplan->id)) { //already done on service
            // Assign goal plans after goal plan updated based on INC-206
            //if assign all current participants then run now
            if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'])	{
                //$ew_goal_plan->id = $result;
                $assign_response = $goalplanservice->assign_all_participants_now($goalplan, $program);
				$response['assign_msg'] = $goalplanservice->assign_all_participants_res($assign_response);
            }
		}*/
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

    public  function readActiveByProgram(Organization $organization, Program $program, GoalPlanService $goalplanservice) {
        $limit = request()->get('pageSize', 10);
        $page = request()->get('page', 1);
        $order_direction = request()->get('order_direction', 'asc');
        $order_column = request()->get('order_column', 'name');
        $offset = ($page - 1) * $limit;
		return $goalplanservice::ReadActiveByProgram($program,$offset, $limit, $order_column, $order_direction);
	}
}