<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaderboardEventRequest;
use App\Http\Requests\LeaderboardGoalPlansRequest;
use App\Models\GoalPlan;
use App\Models\Organization;
use App\Models\Leaderboard;
use App\Models\Program;
use App\Models\Event;
use App\Models\State;

class LeaderboardGoalPlanController extends Controller
{
    public function index( Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        $goalPlans = $leaderboard->goalPlans()->with(['goalPlanType', 'status'])->get()->toArray();
        if (!empty($goalPlans)) {
            foreach ($goalPlans as $key => $goalPlan) {
                $goalPlanStatus = $goalPlan["status"]["status"] ?? '';
                $goalPlanDateEnd = $goalPlan["date_end"] ?? '';
                $goalPlanDateBegin = $goalPlan["date_begin"] ?? '';

                $goalPlans[$key]['name'] .= " [{$goalPlanStatus}] ($goalPlanDateBegin - $goalPlanDateEnd)";
            }
        }

        return response((array) $goalPlans);

    }

    public function assignable( Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        $leaderboardGoalPlans = $leaderboard->goalPlans()->get()->pluck('id');
        $goalPlans = GoalPlan::where(
            [
                'organization_id'=>$organization->id,
                'program_id'=>$program->id
            ]
        )->whereNotIn('id', $leaderboardGoalPlans)
        ->with(['goalPlanType', 'status'])->get()->toArray();

        if (!empty($goalPlans)) {
            foreach ($goalPlans as $key => $goalPlan) {
                $goalPlanStatus = $goalPlan["status"]["status"] ?? '';
                $goalPlanDateEnd = $goalPlan["date_end"] ?? '';
                $goalPlanDateBegin = $goalPlan["date_begin"] ?? '';

                $goalPlans[$key]['name'] .= " [{$goalPlanStatus}] ($goalPlanDateBegin - $goalPlanDateEnd)";
            }
        }

        return response( $goalPlans );
    }

    public function assign(LeaderboardGoalPlansRequest $request, Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        $data = $request->validated();
        $action  = $data['action'];
        $goal_plan_id  = $data['goal_plan_id'];
        if( $action == 'assign')    {
            if($leaderboard->goalPlans->contains($goal_plan_id))   {
                return response(['errors' => 'Goal plan already assigned to leaderboard'], 422);
            }
            $leaderboard->goalPlans()->attach($goal_plan_id);
        }   else if ($action == 'unassign') {
            if( !$leaderboard->goalPlans->contains($goal_plan_id) )   {
                return response(['errors' => 'Goal plan is not assigned to leaderboard'], 422);
            }
            $leaderboard->goalPlans()->detach($goal_plan_id);
        }

        return response(['success'=>true]);
    }
}
