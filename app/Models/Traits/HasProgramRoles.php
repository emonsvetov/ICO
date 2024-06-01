<?php
namespace App\Models\Traits;

use App\Models\AwardLevelHasUser;
use App\Models\Program;
use App\Models\Domain;
use FontLib\TrueType\Collection;
use Illuminate\Support\Facades\DB;

trait HasProgramRoles
{
    public static $withoutAppends = false;
    private $programRoles = null;
    protected function getArrayableAppends()
    {
        if( self::$withoutAppends ){
            return [];
        }
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

    public function getAllProgramRolesByDomain( $domain )   {
        $allRoles = collect();
        if( !$domain instanceof \App\Models\Domain )   {
            $domain =  Domain::getModelByMixed($domain)->with('programs');
        }
        $domain->load('programs');
        //Lets try to find it in associated programs aka parent programs
        $loadedFor = [];
        foreach( $domain->programs as $program)    {
            $programRoles = $this->getProgramRolesByProgram($program);
            if( $programRoles->isNotEmpty() ) {
                $allRoles = $allRoles->merge($programRoles);
                array_push($loadedFor, $program->id);
                // return $programRoles;
            }
        }
        // Not found in any of direct associated program
        foreach( $domain->programs as $program)    {
            $descendants = $program->descendants()->breadthFirst()->get();
            // pr($descendants->toArray());
            if( !$descendants->isEmpty() ) {
                foreach( $descendants as $child)    {
                    if(in_array($child->id, $loadedFor)) continue;
                    $programRoles = $this->getProgramRolesByProgram($child);
                    if( !$programRoles->isEmpty() ) {
                        // pr($programRoles);
                        $allRoles = $allRoles->merge($programRoles);
                        // return $programRoles;
                        array_push($loadedFor, $child->id);
                    }
                }
            }
        }
        return $allRoles;
        // pr("Here");
    }

    public function getProgramRoles( $program = null, $domain = null )
    {

        $programRoles = null;

        if( !$program ) {
            if( $domain ) {
                // $programRoles = $this->getProgramRolesByDomain( $domain );
                $programRoles = $this->getAllProgramRolesByDomain( $domain );
            }
            else {
                $programRoles = $this->getAllProgramRoles();
            }
        }
        else
        {
            if( $domain ) {
                $programRoles = $this->getProgramRolesByDomainAndProgram($program, $domain);
            }
            else
            {
                $programRoles = $this->getProgramRolesByProgram($program);
            }
        }

        return $programRoles;
    }

    public function getAllProgramRoles()
    {
        return $this->roles()->wherePivot( 'program_id', '!=', 0)->withPivot('program_id')->get();
    }

    private function getProgramRolesByProgram( $program )
    {
        $programId = self::extractId($program);
        return $this->roles()
        ->wherePivot( 'program_id', '=', $programId)
        ->withPivot('program_id')
        ->get();
    }
    public function hasProgramRolesByDomain( $domain )
    {
        return $this->roles()
        ->join('programs', 'programs.id', '=', 'model_has_roles.program_id')
        ->join('domain_program', 'domain_program.program_id', '=', 'programs.id')
        ->join('domains', 'domains.id', '=', 'domain_program.domain_id')
        ->where('domains.id', $domain->id)
        ->count() > 0;
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
        return $roles;
    }

    private function getProgramRolesByDomainAndProgram( mixed $domain, mixed $program )
    {
        $programId = self::extractId($program);
        $domainId = self::extractId($domain);
        $roles = $this->roles()
        ->join('domains', 'domains.name', '=', $domainId)
        ->join('domain_program', 'domain_program.domain_id', '=', 'domains.id')
        ->wherePivot( 'program_id', '=', $programId)
        ->withPivot('program_id')
        ->get();
        return $roles;
    }

    public function getCompiledProgramRoles($program = null, $domain = null)
    {
        $roles = $this->getProgramRoles($program, $domain);
        $programRoles = $this->compileProgramRoles($roles);
        return $programRoles;
    }

    public function compileProgramRoles($_roles)
    {
        if( !$_roles ) return null;
        $programs = [];
        $roles = [];
        $programRoles = [];
        foreach( $_roles as $_role )  {
            $roleId = $_role->id;
            $programId = $_role->pivot->program_id;
            if( !isset( $programs[$programId] ) )   {
                $program = Program::where( 'id', $programId )->select('id', 'name', 'organization_id')->with('organization')->first();
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
        if( !$program ) return false;
        $program = Program::getModelByMixed($program);
        $isManager = $this->hasAnyRoleInProgram([
            config('roles.manager'),
            config('roles.read_only_manager'),
            config('roles.limited_manager'),
        ], $program);
        if( $isManager )  return true;
        // Is not a manager in current program. Find manager role in anscestors!
        foreach( $program->ancestors()->get() as $ancestor )  {
            $isManager = $this->hasAnyRoleInProgram([
                config('roles.manager'),
                config('roles.read_only_manager'),
                config('roles.limited_manager'),
            ], $ancestor);
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
            config('roles.read_only_manager'),
            config('roles.limited_manager'),
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
    public function syncProgramRoles($programId, array $roles, bool $allowEmpty = false ) {
        if( !$programId ) return;
        if( !$allowEmpty && !$roles ) return;

        $this->roles()->wherePivot('program_id', '=', $programId)->detach();

        if( $roles )
        {
            $newRoles = [];
            $columns = ['program_id' => $programId];
            foreach($roles as $role_id)    {
                $newRoles[$role_id] = $columns;
            }
            $this->roles()->attach( $newRoles );
        }
    }

    public function syncAwardLevelsHasUsers($programId, $awardLevelId)
    {
       $res = DB::table('award_levels_has_users')
            ->join('award_levels', 'award_levels_has_users.award_levels_id', '=', 'award_levels.id')
            ->where('award_levels_has_users.users_id', $this->id)
            ->where('award_levels.program_id', $programId)
            ->get();

        foreach ($res->toArray() as $item){
            AwardLevelHasUser::where('award_levels_id', $item->award_levels_id)
                ->where('users_id', $item->users_id)
                ->delete();
        }

        $newRecord = new AwardLevelHasUser();
        $newRecord->award_levels_id = $awardLevelId;
        $newRecord->users_id = $this->id;

        return $newRecord->save();
    }

    public function getManagers()   {
        die;
        if( $this->id ) {
            pr($this->id);
            return $this->id;
        }
    }
}
