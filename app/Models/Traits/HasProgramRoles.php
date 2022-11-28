<?php
namespace App\Models\Traits;

use App\Models\Program;
use App\Models\Domain;
use App\Models\Role;
use App\Models\User;
use DB;

trait HasProgramRoles
{
    private $isCompiled = false;
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
    protected function setCompiledProgramRoles( $programRoles )
    {
        $this->programRoles = $programRoles;
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

    public function getAllProgramRolesByDomain( $domain )   {
        $allRoles = [];
        $domain =  Domain::getModelByMixed($domain);
        //Lets try to find it in associated programs aka parent programs
        foreach( $domain->programs as $program)    {
            $programRoles = $this->getProgramRolesByProgram($program);
            if( $programRoles ) {
                $allRoles = array_merge($allRoles, $programRoles);
                // return $programRoles;
            }
        }
        // Not found in any of direct associated program
        foreach( $domain->programs as $program)    {
            $descendants = $program->descendants()->breadthFirst()->get();
            // pr($descendants->toArray());
            if( !$descendants->isEmpty() ) {
                foreach( $descendants as $child)    {
                    $programRoles = $this->getProgramRolesByProgram($child);
                    if( $programRoles ) {
                        // pr($programRoles);
                        $allRoles = array_merge($allRoles, $programRoles);
                        // return $programRoles;
                    }
                }
            }
        }
        return $allRoles;
        // pr("Here");
    }

    private function setIsCompiled( bool $flag )
    {
        $this->isCompiled = $flag;
    }

    private function getIsCompiled()
    {
        return $this->isCompiled;
    }

    public function getProgramRoles( $program = null, $domain = null, $refresh = false )
    {
        if( $refresh )
        {
            $this->setIsCompiled(false);
        }

        if( $this->getIsCompiled() ) $this->programRoles;

        $programRoles = null;

        if( !$program ) {
            if( $domain ) {
                $programRoles = $this->getProgramRoleByDomain( $domain );
            }   
            else {
                $programRoles = $this->roles()->wherePivot( 'program_id', '!=', 0)->withPivot('program_id')->get();
            }
        }
        else 
        {
            if( $domain ) {
                $programRoles = $this->getProgramRoleByDomainAndProgram($program, $domain);
            }
            else
            {
                $programRoles = $this->getProgramRolesByProgram($program);
            }
        }

        return $programRoles;
    }

    private function getProgramRolesByProgram( $program )
    {
        $programId = self::extractId($program);
        $roles = $this->roles()
        ->wherePivot( 'program_id', '=', $programId)
        ->withPivot('program_id')
        ->get();
        return $this->compileProgramRoles($roles);
    }

    public function getProgramRolesByDomain( $domain )
    {
        $domainId = self::extractId($domain);
        $roles = $this->roles()
        // ->whereHas('domains', function ($query) use($byDomain) {
        //     $query->where('name', $byDomain);
        // })
        ->join('programs', 'programs.id', '=', 'model_has_roles.program_id')
        ->join('domain_program', 'domain_program.program_id', '=', 'programs.id')
        ->join('domains', 'domains.id', '=', 'domain_program.domain_id')
        ->where('domains.id', $domainId)
        // ->wherePivot( 'program_id', '!=', 0)
        ->withPivot('program_id')
        ->get();
        return $this->compileProgramRoles($roles);
    }

    private function getProgramRoleByDomainAndProgram( mixed $domain, mixed $program )
    {
        $programId = self::extractId($program);
        $domainId = self::extractId($domain);
        $roles = $this->roles()
        ->join('domains', 'domains.name', '=', $domainId)
        ->join('domain_program', 'domain_program.domain_id', '=', 'domains.id')
        ->wherePivot( 'program_id', '=', $programId)
        ->withPivot('program_id')
        ->get();
        return $this->compileProgramRoles($roles);
    }

    private function compileProgramRoles($_roles)
    {
        if( !$_roles ) return null;
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
        $this->setCompiledProgramRoles($programRoles);
        $this->setIsCompiled(true);
        return $programRoles;
    }
    public function hasRolesInProgram( $roles, $program) {
        if( !$roles  || !$program ) return false;
        $result = [];
        foreach( $roles as $roleName)   {
            if( $this->hasRoleInProgram( $roleName, $program) )    {
                array_push($result, $roleName);
            }
        }
        return $result;
    }
    public function hasAnyRoleInProgram( $roles, $program) {
        if( !$roles  || !$program ) return false;
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
        $program = Program::getModelByMixed($program);
        if( !$program )   return false;
        $roles = $this->roles()
        ->where('roles.name', 'LIKE', $roleName)
        ->wherePivot( 'program_id', '=', $program->id)
        ->withPivot('program_id')
        ->count();
        return $roles > 0 ? true : false;
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
        $program = Program::getModelByMixed($program);
        $isManager = $this->hasRoleInProgram( config('roles.manager'), $program);
        if( $isManager )  return true;
        // Is not a manager in current program. Find manager role in anscestors!
        foreach( $program->ancestors()->get() as $ancestor )  {
            $isManager = $this->hasRoleInProgram( config('roles.manager'), $ancestor);
            if( $isManager )  return true;
        }
        return false;
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