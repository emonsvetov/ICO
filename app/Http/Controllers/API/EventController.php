<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Services\ProgramEventService;
use App\Http\Requests\EventRequest;
use App\Models\ParticipantGroup;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Event;
use App\Models\EmailTemplate;
use DB;

class EventController extends Controller
{

    public function index( Organization $organization, Program $program, Request $request )
    {
        return response(Event::getIndexData($organization, $program, $request->all()) ?? []);
    }

    public function store(EventRequest $request, Organization $organization, Program $program, ProgramEventService $programEventService)
    {
        $validated = $request->validated();
        try {
            return response(['event' => $programEventService->create($validated + [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ])]);
        }
        catch(\Throwable $e)
        {
            return response(['errors' => 'Event Creation failed', 'e' => sprintf('Error %s in line  %d', $e->getMessage(), $e->getLine())], 422);
        }
    }

    public function show( Organization $organization, Program $program, Event $event )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $event->program_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $event->icon;
        $event->eventType;

        if ( $event )
        {
            return response( $event );
        }

        return response( [] );
    }

    public function update(EventRequest $request, Organization $organization, Program $program, Event $event, ProgramEventService $programEventService )
    {
        $validated = $request->validated();
        try {
            return response(['event' => $programEventService->update($event, $validated + ['organization_id' => $organization->id])]);
        }
        catch(\Throwable $e)
        {
            return response(['errors' => 'Error updating program event', 'e' => sprintf('Error %s in line  %d', $e->getMessage(), $e->getLine())], 422);
        }
    }
}
