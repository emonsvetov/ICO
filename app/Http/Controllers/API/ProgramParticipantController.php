<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProgramParticipantStatusRequest;
use App\Http\Controllers\Controller;
use App\Services\ProgramService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class ProgramParticipantController extends Controller
{

    public function index(ProgramService $programService, Organization $organization, Program $program )
    {
        $users = $programService->getParticipants($program, true);
        $users->load('status');
        if( $users  )    {
            return response( $users );
        }
        return response( [] );
    }

    public function changeStatus(ProgramParticipantStatusRequest $request, Organization $organization, Program $program )
    {
        $validated = $request->validated();
        $userIds = $validated['users'];
        $status = User::getStatusByName($validated['status']);
        if( !$status->exists() ) {
            return response( ['errors' => 'Status does not exists'], 422 );
        }
        $result = User::whereIn('id', $userIds)->update(['user_status_id' => $status->id]);
        return response( $result );
    }
}
