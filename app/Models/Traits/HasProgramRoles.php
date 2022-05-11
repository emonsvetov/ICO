<?php
namespace App\Models\Traits;

use App\Models\Program;
use App\Models\Role;
use App\Models\User;

trait HasProgramRoles
{
    private $programRoles = null;
    private $isProgramManager = false;
    private $isParticipant = false;
    protected function getArrayableAppends()
    {
        $this->appends = array_unique(array_merge($this->appends, ['isProgramManager', 'isParticipant']));
        return parent::getArrayableAppends();
    }
    protected function getIsProgramManagerAttribute()
    {
        $programRoles = $this->getProgramRoles();
        if( $programRoles ) {
            foreach( $programRoles as $programRole )    {
                if( $programRole->name == config('roles.program_manager') ) return true;
            }
        }
    }
    protected function getIsParticipantAttribute()
    {
        $programRoles = $this->getProgramRoles();
        if( $programRoles ) {
            foreach( $programRoles as $programRole )    {
                if( $programRole->name == config('roles.participant') ) return true;
            }
        }
    }
    public function getProgramRoles( $byProgram = null )
    {
        if( !$byProgram ) {
            return $this->roles()->wherePivot( 'program_id', '!=', 0)->withPivot('program_id')->get();
        }
        $programId = self::extractId($byProgram);
        return $this->roles()->wherePivot( 'program_id', '=', $programId)->withPivot('program_id')->get();
    }
    public function getProgramsRoles( $byProgram = null )
    {
        $_roles = $this->getProgramRoles( $byProgram );
        $programs = [];
        $roles = [];
        $programRoles = [];
        foreach( $_roles as $_role )  {
            $roleId = $_role->id;
            $programId = $_role->pivot->program_id;
            if( !isset( $programs[$programId] ) )   {
                $program = Program::where( 'id', $programId )->select('id', 'name')->first();
                $programs[$programId] = $program;
            }
            else 
            {
                $program = $programs[$programId];
            }
            if( !isset( $programRoles[$program->id] ) ) {
                $programRoles[$program->id] = $program->toArray();
            }
            $programRoles[$program->id]['roles'][$roleId] = $_role;
        }
        return $programRoles;
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
    public function hasRoleInProgram( $roleName, $program): bool 
    {

        if( trim($roleName) == "" || !$program ) return false;

        $program_id = self::extractId($program);

        if( !isset($program_id) || !$program_id )   return false;

        if( !$this->programs->pluck('id')->contains($program_id) )  {
            return false;
        }

        $programRoles = $this->getProgramRoles( $program_id );
        // pr($programRoles->toArray());
        if( !$programRoles ) return false;
        foreach($programRoles as $programRole)  {
           if( $programRole->name == $roleName )    {
               return true;
           }
        }
        return false;
    }
    //Deprecated method, use "isProgramManager" instead
    public function isManagerToProgram( $program ) {
        return $this->isProgramManager( $program );
    }
    //Deprecated method, use "isProgramParticipant" instead
    public function isParticipantToProgram( $program ) {
        return $this->isProgramParticipant( $program );
    }
    public function isProgramManager( $program ) {
        return $this->hasRoleInProgram( config('roles.program_manager'), $program);
    }
    public function isProgramParticipant( $program ) {
        return $this->hasRoleInProgram( config('roles.participant'), $program);
    }
    public function canReadProgram( $program, $withPermission = '' )    {
        if($this->hasAnyRoleInProgram([
            config('roles.participant'),
            config('roles.program_manager'),
            config('roles.program_admin')
        ], $program ))   {
            return true;
        }
        if( $withPermission ) return $this->can( $withPermission );
        return false;
    }
    public function canWriteProgram( $program, $withPermission = '' )    {
        if($this->hasAnyRoleInProgram([
            config('roles.program_manager'),
            config('roles.program_admin')
        ], $program ))   {
            return true;
        }
        if( $withPermission ) return $this->can( $withPermission );
        return false;
    }
    public function syncProgramRoles($programId, array $roles ) {
        if( !$programId || !$roles ) return;
        $newRoles = [];
        $columns = ['program_id' => $programId];
        $this->roles()->wherePivot('program_id','=',$programId)->detach();
        foreach($roles as $role_id)    {
            $newRoles[$role_id] = $columns;
        }
        $this->roles()->attach( $newRoles );
    }
}