<?php
namespace App\Models\Traits;

trait OrgHasUserViaProgram
{
    public function orgHasUserViaProgram(\App\Models\Organization $organization, \App\Models\User $user, $attach = false) {
        $exists = \App\Models\Organization::join('programs', 'programs.organization_id', '=', 'organizations.id')
        ->join('program_user', 'program_user.program_id', '=', 'programs.id')
        ->where('program_user.user_id', '=', $user->id)
        ->where('organizations.id', '=', $organization->id)
        ->select('organizations.*')
        ->first();
        if( $exists ) {
            if( $attach )   {
                //Handy if user is in organization' program but missing direct assoc
                $user->organizations()->attach($organization->id);
            }
            return true;
        }
        return false;
    }
}
