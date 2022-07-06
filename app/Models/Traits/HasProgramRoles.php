<?php
namespace App\Models\Traits;

use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use DB;

trait HasProgramRoles
{
    private $programRoles = null;
    private $isManager = false;
    private $isParticipant = false;
    protected function getArrayableAppends()
    {
        $this->appends = array_unique(array_merge($this->appends, ['isManager', 'isParticipant']));
        return parent::getArrayableAppends();
    }
    protected function getIsManagerAttribute()
    {
        $programRoles = $this->getProgramRoles();
        if( $programRoles ) {
            foreach( $programRoles as $programRole )    {
                if( $programRole->name == config('roles.manager') ) return true;
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
    public function getParentProgramRoles( $program, $byDomain = null )
    {
        $program = Program::getModelByMixed($program);
        $parent = $program->parent()->get();
        return $parent->getProgramRoles();
    }

    public function getProgramRoles( $byProgram = null, $byDomain = null )
    {
        if( !$byProgram ) {
            if( $byDomain ) {
                return $this->roles()
                // ->whereHas('domains', function ($query) use($byDomain) {
                //     $query->where('name', $byDomain);
                // })
                ->join('programs', 'programs.id', '=', 'model_has_roles.program_id')
                ->join('domain_program', 'domain_program.program_id', '=', 'programs.id')
                ->join('domains', 'domains.id', '=', 'domain_program.domain_id')
                ->where('domains.id', $byDomain)
                // ->wherePivot( 'program_id', '!=', 0)
                ->withPivot('program_id')
                ->get();
            }   else return $this->roles()->wherePivot( 'program_id', '!=', 0)->withPivot('program_id')->get();
        }
        $programId = self::extractId($byProgram);
        if( $byDomain ) {
            return $this->roles()
            ->join('domains', 'domains.name', '=', $byDomain)
            ->join('domain_program', 'domain_program.domain_id', '=', 'domains.id')
            ->wherePivot( 'program_id', '=', $programId)
            ->withPivot('program_id')
            ->get();
        }
        return $this->roles()
        ->wherePivot( 'program_id', '=', $programId)
        ->withPivot('program_id')
        ->get();
    }
    public function getProgramsRoles( $byProgram = null, $byDomain = null )
    {
        $_roles = $this->getProgramRoles( $byProgram, $byDomain );
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
    public function hasRoleInAncestors($roleName, $program)   {
        if( trim($roleName) == "" || !$program ) return false;
        $program = Program::getModelByMixed($program);
        // pr($program->toArray());
        if( $program->parent_id )   {
            $role = $this->hasRoleInAncestor($roleName, $program);
            if( $role ) return $role;
            else {
                $parent = $program->parent()->first();
                if( $parent )   {
                    return $this->hasRoleInAncestors($roleName, $parent);
                }
            }
        }
    }
    public function hasRoleInAncestor($roleName, $program)   {
        if( trim($roleName) == "" || !$program || !$program->parent_id ) return false;
        $program = Program::getModelByMixed($program);
        $roles = $this->roles()
        ->where('roles.name', 'LIKE', $roleName)
        ->wherePivot( 'program_id', '=', $program->parent_id)
        ->withPivot('program_id')
        ->count();
        return $roles;
    }

    public function hasRoleInProgram( $roleName, $program): bool 
    {
        if( trim($roleName) == "" || !$program ) return false;
        // $program_id = self::extractId($program);
        $program = Program::getModelByMixed($program);
        if( !$program )   return false;
        if( !$this->programs->pluck('id')->contains($program->id) )  {
            $hasRoleInAnscestors = $this->hasRoleInAncestors($roleName, $program );
            // pr($hasRoleInAnscestors);
            return $hasRoleInAnscestors;
        }   else {
            $roles = $this->roles()
            ->where('roles.name', 'LIKE', $roleName)
            ->wherePivot( 'program_id', '=', $program->id)
            ->withPivot('program_id')
            ->count();
            return $roles > 0 ? true : false;
        }
        return false;
    }
    //Deprecated method, use "isManager" instead
    public function isManagerToProgram( $program ) {
        return $this->isManager( $program );
    }
    //Deprecated method, use "isProgramParticipant" instead
    public function isParticipantToProgram( $program ) {
        return $this->isProgramParticipant( $program );
    }
    public function isManager( $program ) {
        return $this->hasRoleInProgram( config('roles.manager'), $program);
    }
    public function isProgramParticipant( $program ) {
        return $this->hasRoleInProgram( config('roles.participant'), $program);
    }
    public function canReadProgram( $program, $withPermission = '' )    {
        if($this->hasAnyRoleInProgram([
            config('roles.participant'),
            config('roles.manager'),
            config('roles.admin')
        ], $program ))   {
            return true;
        }
        if( $withPermission ) return $this->can( $withPermission );
        return false;
    }
    public function canWriteProgram( $program, $withPermission = '' )    {
        if($this->hasAnyRoleInProgram([
            config('roles.manager'),
            config('roles.admin')
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