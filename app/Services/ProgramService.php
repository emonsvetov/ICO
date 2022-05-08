<?php
namespace App\Services;
use App\Models\Traits\IdExtractor;
use App\Models\Role;
use App\Models\User;

class ProgramService 
{
    use IdExtractor;

    public function getParticipants($program, $paginate = false)   {
        $program_id = self::extractId($program);
        if( !$program_id ) return;
        $role = Role::where('name', config('global.participant_role_name'))->first();
        if( !$role ) return response(['errors' => 'Invalid Role'], 422);
        $permissionName = "program.{$program_id}.role.{$role->id}";
        $query = User::join('program_user AS pu', 'pu.user_id', '=', 'users.id')
        ->join('model_has_permissions AS mhp', 'mhp.model_id', '=', 'users.id')
        ->join('permissions AS perm', 'perm.id', '=', 'mhp.permission_id')
        ->where([
            'pu.program_id' => $program_id,
            'mhp.model_type' => 'App\Models\User',
            'perm.name' => $permissionName,
        ])
        ->select(['users.id', 'users.first_name', 'users.last_name', 'users.email']);
        if( $paginate ) {
            return $query->paginate();
        }   else    {
            return $query->get();
        }
    }
}
