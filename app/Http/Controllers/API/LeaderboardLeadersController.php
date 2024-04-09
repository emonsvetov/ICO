<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Leaderboard;
use App\Models\Program;
use App\Services\LeaderboardService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;

class LeaderboardLeadersController extends Controller
{

    public function index(Organization $organization, Program $program, LeaderboardService $leaderboardService)
    {
        $where = ['organization_id' => $organization->id, 'program_id' => $program->id];
        $leaderboards = Leaderboard::where($where)->with('leaderboard_type')->get();
        if ($leaderboards->isNotEmpty()) {
            $limit = request()->get('pageSize', 10);
            $page = request()->get('page', 1);
            $offset = ($page - 1) * $limit;
            $leaderboards = $leaderboardService->readEventLeaders($leaderboards, $limit, $offset);

            return response($leaderboards);
        }
        return response([]);
    }

    public function readEventLeadersAwardsByUser(Organization $organization, Program $program, $userID, $leaderboardID, LeaderboardService $leaderboardService)
    {
        $eventLeadersAwards = $leaderboardService->readEventLeadersAwardsByUser($leaderboardID,  $userID);
        return $eventLeadersAwards->isNotEmpty() ? response($eventLeadersAwards) : response([]);
    }

}
