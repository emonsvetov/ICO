<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Traits\IdExtractor;
use Laravel\Passport\HasApiTokens;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;

use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, IdExtractor;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'award_level',
        'user_status_id',
        'email_verified_at',
        'phone',
        'work_anniversary',
        'dob',
		'username',
        'employee_number',
		'division',
        'office_location',
        'position_title',
        'position_grade_level',
        'supervisor_employee_number',
        'organizational_head_employee_number',
        'deactivated',
        'activated',
        'state_updated',
        'last_location',
        'update_id',
        'role_id',
        'created_at',
        'updated_at'
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $visible = [
    ];

    protected $with = [
        'role'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public $allRoles = [];
    public $programRoles = [];
    public $isProgramManager = false;
    public $isParticipant = false;
    public $isAdmin = false;

    protected $appends = ['name', 'allRoles', 'programRoles', 'isProgramManager', 'isParticipant', 'isAdmin'];

    public function getIsProgramManagerAttribute()
    {
        return $this->isProgramManager;
    }
    public function getIsParticipantAttribute()
    {
        return $this->isParticipant;
    }
    public function getIsAdminAttribute()
    {
        return $this->isAdmin;
    }
    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    public function getAllRolesAttribute()
    {
        return $this->allRoles;
    }
    public function getProgramRolesAttribute()
    {
        return $this->programRoles;
    }
    public function setPasswordAttribute($password)
    {   
        $this->attributes['password'] = bcrypt($password);
    }
    
    public function participant_groups()
    {
        return $this->belongsToMany(ParticipantGroup::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function sendPasswordResetNotification($token)
    {
        
        $url = env('APP_URL', 'http://localhost') . '/reset-password?token=' . $token;

        $this->notify(new ResetPasswordNotification($url));
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_user')
        // ->withPivot('featured', 'cost_to_program')
        ->withTimestamps();
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

    public function getRoles( $byProgram = null) 
    {
        $this->allRoles = $this->getRoleNames()->toArray();
        $this->programRoles = $this->getProgramRoles( $byProgram );
        return ['roles' => $this->allRoles, 'programRoles' => $this->programRoles];
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
                        if( !in_array( $role->name, $this->allRoles ))    {
                            array_push( $this->allRoles, $role->name );
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
        return $this->programRoles;
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

    public function isManagerToProgram( $program ) {
        return $this->hasRoleInProgram( config('global.program_manager_role_name'), $program);
    }

    public function isParticipantToProgram( $program ) {
        return $this->hasRoleInProgram( config('global.participant_role_name'), $program);
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

    public function readAvailableBalance( $program, $user )  {
        $program_id = self::extractId($program);
        $user_id = self::extractId($user);
        if( !$program_id || !$user_id ) return;
        $journal_event_types = array (); // leave $journal_event_types empty to get all  - original comment 
        if( gettype($program)!='object' ) {
            $program = Program::where('id', $program_id)->select(['id'])->first();
        }
        if( gettype($user)!='object' ) {
            $user = self::where('id', $user_id)->select(['id'])->first();
        }
        if ($program->program_is_invoice_for_awards ()) {
			// use points
			$account_type = config('global.account_type_points_awarded');
		} else {
			// use monies
            $account_type = config('global.account_type_monies_awarded');
		}
        return self::_read_balance( $user_id, $account_type, $journal_event_types );
        // _read_balance
        // return [$program, $user, $account_type, $journal_event_types];
    }

	private function _read_balance($account_holder_id, $account_type, $journal_event_types = []) {
		$credits = JournalEvent::read_sum_postings_by_account_and_journal_events ( ( int ) $account_holder_id, $account_type, $journal_event_types, 1 );
		$debits = JournalEvent::read_sum_postings_by_account_and_journal_events ( ( int ) $account_holder_id, $account_type, $journal_event_types, 0 );
		$bal = ( float ) (number_format ( ($credits->total - $debits->total), 2, '.', '' ));
		return $bal;
	}
}
