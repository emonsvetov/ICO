<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AwardLevelRequest;
use Illuminate\Http\Request;

use App\Services\ProgramEventService;
use App\Http\Requests\EventRequest;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Event;

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

    public function show( Organization $organization, Program $program, Event $event , ProgramEventService $programEventService)
    {
        if ( !( $organization->id == $program->organization_id && ($program->id == $event->program_id || $program->getParentProgramId($program->id) == $event->program_id) ) ) // Consider subprogram
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $event->eventIcon;
        $event->eventType;
        $event->eventAwardsLevel = $programEventService->getEventAwardsLevel($event->id);
        if (count($event->eventAwardsLevel)) {
            $event->max_awardable_amount = $event->eventAwardsLevel[0]->amount;
        }

        if ( $event )
        {
            return response( $event );
        }

        return response( [] );
    }

    public function update(EventRequest $request, Organization $organization, Program $program, Event $event, ProgramEventService $programEventService)
    {
        $validated = $request->validated();
        try {
            return response(['event' => $programEventService->update($event, $validated + [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ])]);
        }
        catch(\Throwable $e)
        {
            return response(['errors' => 'Error updating program event', 'e' => sprintf('Error %s in line  %d', $e->getMessage(), $e->getLine())], 422);
        }
    }

    public function delete(Organization $organization, Program $program, Event $event )
    {
        try {
            $deleted = ['deleted' => 1];
            $event->delete();
            return response( $deleted );
        }
        catch(\Throwable $e)
        {
            return response(['errors' => 'Error deleting program event', 'e' => sprintf('Error %s in line  %d', $e->getMessage(), $e->getLine())], 422);
        }
    }

    public function storeAwardLevel(AwardLevelRequest $request, $organizationId, $programId, $eventId,ProgramEventService $programEventService)
    {
        $data = $request->validated();
        $res = $programEventService->storeAwardLevel($data);
        return response([$res]);
    }

    public function deleteAwardLevel(AwardLevelRequest $request, $organizationId, $programId, $eventId,ProgramEventService $programEventService)
    {
        $data = $request->validated();
        $res = $programEventService->deleteAwardLevel($data);
        return response([$res]);
    }
}
