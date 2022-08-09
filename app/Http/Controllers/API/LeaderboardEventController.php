<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaderboardEventRequest;
use App\Models\Organization;
use App\Models\Leaderboard;
use App\Models\Program;
use App\Models\Event;

class LeaderboardEventController extends Controller
{
    public function index( Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        return response( $leaderboard->events()->with(['eventType'])->get() );
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
        ->with(['eventType'])->get();
        return response( $events );
    }

    public function assign(LeaderboardEventRequest $request, Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        $data = $request->validated();
        $action  = $data['action'];
        $event_id  = $data['event_id'];
        if( $action == 'assign')    {
            if($leaderboard->events->contains($event_id))   {
                return response(['errors' => 'Event already assigned to leaderboard'], 422);
            }
            $leaderboard->events()->attach($event_id);
        }   else if ($action == 'unassign') {
            if( !$leaderboard->events->contains($event_id) )   {
                return response(['errors' => 'Event is not assigned to leaderboard'], 422);
            }
            $leaderboard->events()->detach($event_id);
        }

        return response(['success'=>true]);
    }
}
