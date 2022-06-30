<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\InvitationRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use DB;

class InvitationController extends Controller
{

    public function invite(InvitationRequest $request, Organization $organization, Program $program)
    {
		//file_put_contents("test.txt",json_encode($request->all()));
     //return auth()->user();
		try {
            $validated = $request->validated();
            $validated['organization_id'] = $organization->id;
            $validated['password']="123456";
            $user = User::createAccount( $validated );
			//$validated['roles'] get prpgram here
			//file_put_contents("test.txt",json_encode($user));
          //  if( !empty($validated['roles']))   {
               // $user->syncRoles( [$validated['roles']] );
			 //   $user->syncProgramRoles($program->id, $validated['roles']); //here pass program id
           // }
		   // $program_id = $validated['program_id'];
            $program_id=$program->id;
			$columns = []; //any additional columns set here
			//$user->programs()->sync( [ $validated['program_id'] => $columns ], false); need to discuss this

          //  $roles = $validated['roles'];
          $roles=[6];

            if( !empty($roles) ) {
                $user->syncProgramRoles($program_id, $roles);
            }
            return response([ 'user' => $user ]);
        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }
		//"This person is already a participant
	}
}
