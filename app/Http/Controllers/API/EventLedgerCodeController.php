<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\EventLedgerCodeRequest;
use App\Http\Controllers\Controller;
use App\Models\EventLedgerCode;
use App\Models\Organization;

use App\Models\Program;

class EventLedgerCodeController extends Controller
{
    public function index(Organization $organization, Program $program)
    {
        return response()->json($program->ledger_codes()->select(['id', 'ledger_code'])->get());
    }

    public function store(EventLedgerCodeRequest $request, Organization $organization, Program $program )
    {
        $newEventLedgerCode = EventLedgerCode::create(
            $request->validated() +
            [
                'program_id' => $program->id
            ]
        );

        if ( !$newEventLedgerCode )
        {
            return response(['errors' => 'EventLedgerCode creation failed'], 422);
        }

        return response([ 'eventLedgerCode' => $newEventLedgerCode ]);
    }

    public function update(EventLedgerCodeRequest $request, Organization $organization, Program $program, EventLedgerCode $eventLedgerCode )
    {
        $eventLedgerCode->update( $request->validated() );
        return response([ 'eventLedgerCode' => $eventLedgerCode ]);
    }

    public function delete(Organization $organization, Program $program, EventLedgerCode $eventLedgerCode )
    {
        $eventLedgerCode->delete();
        return response()->json( true );
    }
}
