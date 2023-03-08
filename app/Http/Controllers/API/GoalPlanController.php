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
    public function store(GoalPlanRequest $request, Organization $organization, Program $program, GoalPlanService $goalPlanService)
    {
		if (!GoalPlan::CONFIG_PROGRAM_USES_GOAL_TRACKER) {
            return response(['errors' => "You can't add goal plan in this program."], 422);
        }
        if ($program->isShellProgram()) {
            return response(['errors' => "Invalid program id passed, you cannot create a goal plan in a shell program"], 422);
        }
        
        $data = $request->validated();
        try{
            $response= $goalPlanService->create($data,$organization,$program);
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

        $goalPlans = GoalPlan::where($where)
        ->orderBy('name')
        ->with(['goalPlanType'])
        ->get();

        if ( $goalPlans->isNotEmpty() )
        {
            return response( $goalPlans );
        }

        return response( [] );
    }
    
    public function show( Organization $organization, Program $program, GoalPlan $goalPlan )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $goalPlan->program_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        if ( $goalPlan )
        {
            $goalPlan->load('GoalPlanType');
            return response( $goalPlan );
        }
    

        return response( [] );
    }

    public function update(GoalPlanRequest $request, Organization $organization, Program $program, GoalPlan $goalPlan, GoalPlanService $goalPlanService )
    {
        if (!GoalPlan::CONFIG_PROGRAM_USES_GOAL_TRACKER) {
            return response(['errors' => "You can't add goal plan in this program."], 422);
        }
        try{
        $data = $request->validated();
        $response= $goalPlanService->update($data, $goalPlan, $organization, $program);
       // $response = $update_goal_plan;
        /*if (!empty($goalplan->id)) { //already done on service
            // Assign goal plans after goal plan updated based on INC-206
            //if assign all current participants then run now
            if(isset($data['assign_goal_all_participants_default']) && $data['assign_goal_all_participants_default'])	{
                //$ew_goal_plan->id = $result;
                $assign_response = $goalPlanService->assign_all_participants_now($goalplan, $program);
				$response['assign_msg'] = $goalPlanService->assign_all_participants_res($assign_response);
            }
		}*/
    }
    catch (\Exception $e )    {
        return response(['errors' => $e->getMessage()], 422);
    }
        return $response;
    }

    public function destroy(Organization $organization, Program $program, GoalPlan $goalPlan)
    {
        $goalPlan->delete();
        return response(['success' => true]);
    }

    public  function readActiveByProgram(Organization $organization, Program $program, GoalPlanService $goalPlanService) {
        $limit = request()->get('pageSize', 10);
        $page = request()->get('page', 1);
        $order_direction = request()->get('order_direction', 'asc');
        $order_column = request()->get('order_column', 'name');
        $offset = ($page - 1) * $limit;
		return $goalPlanService::ReadActiveByProgram($program,$offset, $limit, $order_column, $order_direction);
	}
}