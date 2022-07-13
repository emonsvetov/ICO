<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\InvitationRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Events\UserInvited;
use App\Models\Program;
use App\Models\User;
use App\Models\Role;
use DB;

class InvitationController extends Controller
{
    /**
     * Participant Invitation 
     */
    public function invite(InvitationRequest $request, Organization $organization, Program $program)
    {
        //return auth()->user();
		try {
            $validated = $request->validated();
            $validated['organization_id'] = $organization->id;
            $generatedPassword = rand();
            $validated['password'] = $generatedPassword;

            $user = User::createAccount( $validated );

            $roles[] = Role::getIdByName(config('roles.participant'));

            if( !empty($roles) ) 
            {
                $user->syncProgramRoles($program->id, $roles);
            }
            event( new UserInvited( $user, $program ) );
            return response([ 'user' => $user ]);
        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }
	}
}
