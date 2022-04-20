<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\EventRequest;
use App\Models\ParticipantGroup;
use App\Models\Organization;
use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\Event;
use App\Models\EmailTemplate;
use DB;

class EventController extends Controller
{
    public function index( Organization $organization, Program $program )
    {

        // $journalEvents = JournalEventType::get();

        // $types = [];

        // foreach( $journalEvents as $journalEventType)   {
        //     $types[] = $journalEventType;
        // }

        // return $types;

        if ( $organization->id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $events = Event::where('organization_id', $organization->id)
                        ->where('program_id', $program->id)
                        ->orderBy('name')
                        ->with('icon')
                        ->get();

        if ( $events->isNotEmpty() )
        {
            return response( $events );
        }

        return response( [] );
    }

    public function store(EventRequest $request, Organization $organization, Program $program )
    {

        if ( !( $organization->id == $program->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $validated = $request->validated();
        $custom_email_template  = request()->get('custom_email_template');
        if($custom_email_template){
            $template['name']  = request()->get('template_name');
            $template['content']= request()->get('email_template');
            $template['type']= 'program_event';
            $newTemplate = EmailTemplate::create( $template);
            if ( !$newTemplate )
            {
                return response(['errors' => 'Email Template Creation failed'], 422);
            }
            $validated['email_template_id'] = $newTemplate->id;
        }

        $newEvent = Event::create(
                                    $validated +
                                    [
                                        'organization_id' => $organization->id,
                                        'program_id' => $program->id
                                    ]
                                );

        if ( !$newEvent )
        {
            return response(['errors' => 'Event Creation failed'], 422);
        }



        return response([ 'event' => $newEvent ]);
    }

    public function show( Organization $organization, Program $program, Event $event )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $event->program_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $event->icon;

        if ( $event )
        {
            return response( $event );
        }

        return response( [] );
    }

    public function update(EventRequest $request, Organization $organization, Program $program, Event $event )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $event->program_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        if ( $event->organization_id != $organization->id )
        {
            return response(['errors' => 'No Program Found'], 404);
        }
        $validated = $request->validated();
        $custom_email_template  = request()->get('custom_email_template');
        if($custom_email_template){
            $template['name']  = request()->get('template_name');
            $template['content']= request()->get('email_template');
            $template['type']= 'program_event';
            $newTemplate = EmailTemplate::create( $template);
            if ( !$newTemplate )
            {
                return response(['errors' => 'Email Template Creation failed'], 422);
            }
            $validated['email_template_id'] = $newTemplate->id;
        }

        $event->update( $validated );

        return response([ 'event' => $event ]);
    }
}
