<?php
namespace App\Models\Traits;

use App\Models\Program;
use App\Models\Role;

trait HasProgramRoles
{
    public $allRoles = null;
    public $programRoles = null;
    public $isProgramManager = null;
    public $isParticipant = null;
    public $isProgramAdmin = null;
    protected function getArrayableAppends()
    {
        $this->appends = array_unique(array_merge($this->appends, ['allRoles', 'programRoles', 'isProgramManager', 'isParticipant', 'isProgramAdmin']));
        return parent::getArrayableAppends();
    }
    protected function getAllRolesAttribute()
    {
        return $this->allRoles;
    }
    protected function getIsProgramManagerAttribute()
    {
        return $this->isProgramManager;
    }
    protected function getIsParticipantAttribute()
    {
        return $this->isParticipant;
    }
    protected function getIsProgramAdminAttribute()
    {
        return $this->isProgramAdmin;
    }
    protected function getProgramRolesAttribute()
    {
        return $this->programRoles;
    }
    public function getRoles( $byProgram = null) 
    {
        return [
            'roles' => $this->getRoleNames()->toArray(), 
            'programRoles' => $this->getProgramRoles( $byProgram )
        ];
    }
    public function getProgramRoles( $byProgram = null )
    {
        if( $byProgram ) {
            $byProgram = self::extractId($byProgram);
        }
        $permissions = $this->getPermissionNames();
        if( $permissions )  {
            $programs = [];
            $roles = [];
            $allRoles = [];
            foreach( $permissions as $permission )  {
                preg_match('/program.(\d)\.role\.(\d)/', $permission, $matches, PREG_UNMATCHED_AS_NULL);
                if( $matches )    {
                    $programId = $matches[1];
                    if( $byProgram && $byProgram != $programId)
                    {
                        continue;
                    }
                    $roleId = $matches[2];
                    if( !isset( $programs[$programId] ) )   {
                        $program = Program::where( 'id', $programId )->select('id', 'name')->first();
                        $programs[$programId] = $program;
                    }
                    else 
                    {
                        $program = $programs[$programId];
                    }
                    if( !isset( $roles[$roleId] ) )   {
                        $role = Role::where( 'id', $roleId )->select('id', 'name')->first();
                        $roles[$roleId] = $role;
                        if( !in_array( $role->name, $allRoles ))    {
                            array_push( $allRoles, $role->name );
                        }
                        if( config('global.program_manager_role_name') == $role->name ) {
                            $this->isProgramManager = true;
                        }
                        if( config('global.participant_role_name') == $role->name ) {
                            $this->isParticipant = true;
                        }
                        if( config('global.admin_role_name') == $role->name ) {
                            $this->isAdmin = true;
                        }
                    }
                    else 
                    {
                        $role = $roles[$roleId];
                    }

                    if( !isset( $this->programRoles[$program->id] ) ) {
                        $this->programRoles[$program->id] = $program->toArray();
                    }
                    $this->programRoles[$program->id]['roles'][$role->id] = $role->toArray();
                }
            }
        }
        if( $allRoles )   {
            $this->allRoles = $allRoles;
        }
        return $this->programRoles;
    }
    public function hasRolesInProgram( $roles = [], $program) {
        if( !$roles ) return false;
        $result = null;
        foreach( $roles as $roleName)   {
            if( $this->hasRoleInProgram( $roleName, $program) )    {
                array_push($result, $roleName);
            }
        }
        return $result;
    }
    public function hasAnyRoleInProgram( $roles = [], $program) {
        if( !$roles ) return false;
        foreach( $roles as $roleName)   {
            if( $this->hasRoleInProgram( $roleName, $program) )    {
                return true;
            }
        }
        return false;
    }
    public function hasRoleInProgram( $roleName, $program) {

        if( trim($roleName) == "" || !$program ) return false;

        $program_id = self::extractId($program);

        if( !isset($program_id) || !$program_id )   return false;

        if( !$this->programs->pluck('id')->contains($program_id) )  {
            return false;
        }

        if( !$this->programRoles )  {
            $this->programRoles = $this->getProgramRoles();
        }

        if( !$this->programRoles ) return false;

        foreach( $this->programRoles as $programId => $programRoles)  {
            $programRoles = (object) $programRoles;
            if( $programId == $program_id)    {
                foreach($programRoles->roles as $programRole)   {
                    $programRole = (object) $programRole;
                    if( $programRole->name == $roleName )    {
                       return true;
                    }
                }
            }
        }
        return false;
    }
    //Deprecated method, use "isManagerInProgram" instead
    public function isManagerToProgram( $program ) {
        return $this->isManagerInProgram( $program );
    }
    //Deprecated method, use "isParticipantInProgram" instead
    public function isParticipantToProgram( $program ) {
        return $this->isParticipantInProgram( $program );
    }
    public function isManagerInProgram( $program ) {
        return $this->hasRoleInProgram( config('global.program_manager_role_name'), $program);
    }
    public function isParticipantInProgram( $program ) {
        return $this->hasRoleInProgram( config('global.participant_role_name'), $program);
    }
    public function canReadProgram( $program, $withPermission = '' )    {
        if($this->hasAnyRoleInProgram([
            config('global.participant_role_name'),
            config('global.program_manager_role_name'),
            config('global.program_admin_role_name')
        ], $program ))   {
            return true;
        }
        if( $withPermission ) return $this->can( $withPermission );
        return false;
    }
    public function canWriteProgram( $program, $withPermission = '' )    {
        if($this->hasAnyRoleInProgram([
            config('global.program_manager_role_name'),
            config('global.program_admin_role_name')
        ], $program ))   {
            return true;
        }
        if( $withPermission ) return $this->can( $withPermission );
        return false;
    }
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
    public function syncRolesByProgram($programId, array $roles ) {
        $permissions = [];
        foreach( $roles as $roleId)    {
            $permisssionName = "program.{$programId}.role.{$roleId}";
            $permission = Permission::firstOrCreate(['name' => $permisssionName, 'organization_id' => $this->organization_id]);
            if( $permission )   {
                array_push($permissions, $permission->id);
            }
        }
        if( $permissions )  {
            $this->syncPermissionsByProgram($programId, $permissions);
        }
        return $this;
    }
    public function syncPermissionsByProgram($programId, array $permissions)
    {
        $permissionIds = Permission::where('name', 'LIKE', "program.{$programId}.role.%")->get()->pluck('id'); //filter by program to narrow down the change
        $current = $this->permissions->filter(function($permission) use ($permissionIds) {
            return in_array($permission->pivot->permission_id, $permissionIds->toArray());
        })->pluck('id');
    
        $detach = $current->diff($permissions)->all();
        $attach_ids = collect($permissions)->diff($current)->all();

        $attach_pivot = [];

        foreach( $attach_ids as $permission_id )  {
            $attach_pivot[] = ['permission_id' => $permission_id];
        }
        $attach = array_combine($attach_ids, $attach_pivot);

        $this->permissions()->detach($detach);
        $this->permissions()->attach($attach);
    
        return $this;
    }
}