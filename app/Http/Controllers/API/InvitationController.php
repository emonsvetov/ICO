<?php

namespace App\Http\Controllers\API;

use App\Notifications\UserInvitedNotifyUser;
use App\Services\UserService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Http\Requests\InvitationResendRequest;
use App\Http\Requests\InvitationRequest;
use App\Http\Controllers\Controller;
use App\Events\InvitationAccepted;
use App\Models\Organization;
use App\Events\UsersInvited;
use App\Events\UserInvited;
use App\Models\Program;
use App\Models\User;
use App\Models\Role;
use stdClass;

class InvitationController extends Controller
{
    /**
     * Participant Invitation
     */
    public function invite(InvitationRequest $request, Organization $organization, Program $program)
    {
        DB::beginTransaction();
		try {
            $validated = $request->validated();
            $validated['organization_id'] = $organization->id;
            $generatedPassword = rand();
            $validated['password'] = $generatedPassword;

            $user = User::createAccount( $validated );
            // $user = User::find( 553 );
            $token = Password::broker()->createToken($user);

            $roles[] = Role::getIdByName(config('roles.participant'));

            if( !empty($roles) )
            {
                $program->users()->sync( [ $user->id ], false );
                $user->syncProgramRoles($program->id, $roles);
            }
            event(new UserInvited($user, $program, $token));
            DB::commit();
            return response([ 'user' => $user ]);
        } catch (\Exception $e )    {
            DB::rollBack();
            if( config('app.env') != 'production')
            {
                $error = sprintf('Error inviting user (%d) to program (%d). Exception "%s" on line %d in file %s ', $user->id, $program->id, $e->getMessage(), $e->getLine(), $e->getFile());
            }
            else
            {
                $error = "Error inviting user to program";
            }
            return response(['errors' => $error], 422);
        }
	}
    public function resend(InvitationResendRequest $request, Organization $organization, Program $program)
    {
        //return auth()->user();
		try {
            $validated = $request->validated();
            $recipients = $validated['recipients'];
            $users = User::find($recipients);
            foreach($users as $user)
            {
                $user->token = Password::broker()->createToken($user);
            }
            event( new UsersInvited( $users, $program, true ) );
            return response([ 'success' => true ]);
        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }
	}

    public function accept(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed'
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => $request->password,
                    'remember_token' => Str::random(60),
                    'email_verified_at' => now(),
                    'user_status_id' => User::getIdStatusActive()
                ])->save();

                $user->tokens()->delete();

                event(new InvitationAccepted($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response([
                'message'=> 'Invitation accepted successfully'
            ]);
        }

        return response([
            'message'=> __($status)
        ], 500);
    }
}
