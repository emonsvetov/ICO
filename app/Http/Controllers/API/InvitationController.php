<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\InvitationResendRequest;
use App\Http\Requests\InvitationRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Events\UsersInvited;
use App\Events\UserInvited;
use App\Models\Program;
use App\Models\User;
use App\Models\Role;

class InvitationController extends Controller
{
    /**
     * Participant Invitation 
     */
    public function invite(InvitationRequest $request, Organization $organization, Program $program)
    {
        //return auth()->user();
        DB::beginTransaction();
		try {
            $validated = $request->validated();
            $validated['organization_id'] = $organization->id;
            $generatedPassword = rand();
            $validated['password'] = $generatedPassword;

            $user = User::createAccount( $validated );

            $roles[] = Role::getIdByName(config('roles.participant'));

            if( !empty($roles) ) 
            {
                $program->users()->sync( [ $user->id ], false );
                $user->syncProgramRoles($program->id, $roles);
            }
            UserInvited::dispatch( $user, $program);
            DB::commit();
            return response([ 'user' => $user ]);
        } catch (\Exception $e )    {
            DB::rollBack();
            return response(['errors' => $e->getMessage()], 422);
        }
	}
    public function resend(InvitationResendRequest $request, Organization $organization, Program $program)
    {
        //return auth()->user();
		try {
            $validated = $request->validated();
            $recipients = $validated['recipients'];
            $users = User::find($recipients);
            event( new UsersInvited( $users, $program, true ) );
            return response([ 'success' => true ]);
        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }
	}
}
