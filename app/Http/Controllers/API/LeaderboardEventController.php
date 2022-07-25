<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use App\Http\Requests\LeaderboardEventRequest;
use App\Models\Organization;
use App\Models\Leaderboard;
use App\Models\Program;
use App\Models\Event;

class LeaderboardEventController extends Controller
{
    public function index( Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        return response( $leaderboard->events()->get() );
    }

    public function assignable( Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        $leaderboardEvents = $leaderboard->events()->get()->pluck('id');
        $events = Event::where(
            [
                'organization_id'=>$organization->id,
                'program_id'=>$program->id
            ]
        )->whereNotIn('id', $leaderboardEvents)
        ->get();
        return response( $events );
    }
}
