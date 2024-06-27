<?php

namespace App\Models;

use App\Models\interfaces\ImageInterface;
use App\Models\Role;
use App\Services\AccountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\WithOrganizationScope;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Traits\OrgHasUserViaProgram;
use App\Models\Traits\HasOrganizationRoles;
use App\Models\Traits\HasProgramRoles;
use App\Models\Traits\GetModelByMixed;
use App\Models\Traits\IdExtractor;
use Laravel\Passport\HasApiTokens;
use App\Models\AccountHolder;
use App\Models\Permission;
use App\Models\Program;

use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail, ImageInterface
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, IdExtractor, HasProgramRoles, WithOrganizationScope, GetModelByMixed, OrgHasUserViaProgram;
    use SoftDeletes;
    use HasOrganizationRoles;

    const IMAGE_FIELDS = ['avatar'];
    const IMAGE_PATH = 'users';

    const STATUS_ACTIVE = 'Active';
    const STATUS_PENDING_ACTIVATION = 'Pending Activation';
    const STATUS_DELETED = 'Deleted';
    const STATUS_PENDING_DEACTIVATION = 'Pending Deactivation';
    const TERMINATED = 'Terminated';
    const STATUS_DEACTIVATED = 'Deactivated';
    const STATUS_LOCKED = 'Locked';
    const STATUS_NEW = 'New';

    public $timestamps = true;

    private $isSuperAdmin = false; //user is super admin
    private $isAdmin = false; //user is admin

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'account_holder_id',
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
        'updated_at',
        'avatar',
        'join_reminder_at',
        'v2_parent_program_id',
        'v2_account_holder_id',
        'hire_date',
        'sso_token',
        'external_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        // 'isSuperAdmin',
        // 'isAdmin',
        // 'isManager',
        // 'isParticipant',
        //'created_at',
        'updated_at',
        'deleted_at',
        'roles'
    ];

    protected $visible = [
    ];

    // protected $with = [
    //     'roles'
    // ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['name', 'isSuperAdmin', 'isAdmin', 'unitNumber','positionLevel'];
    protected function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    protected function getIsSuperAdminAttribute()
    {
        return $this->hasRole(config('roles.super_admin'));
    }
    // protected function getIsAdminAttribute()
    // {
    //     return $this->hasRole(config('roles.admin'));
    // }
    protected function setPasswordAttribute($password)
    {
        // Save md5 password from v2 when run migrate users.
        $routeName = request()->route()->getName();
        $this->attributes['password'] = $routeName == 'runMigrations' ? $password : bcrypt($password);
    }
    // public function isAdmin()
    // {
    //     return $this->hasRole(config('roles.admin'));
    // }
    public function isSuperAdmin()
    {
        return $this->hasRole(config('roles.super_admin'));
    }

    public function acount_holder()
    {
        return $this->belongsTo(AccountHolder::class);
    }

    public function participant_groups()
    {
        return $this->belongsToMany(ParticipantGroup::class);
    }
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function organizations()
    {
        return $this->belongsToMany(Organization::class)->withTimestamps();
    }
    public function belongsToOrg( Organization $organization )
    {
        if( $this->organizations->contains($organization->id) ) {
            return true;
        }
        if( $this->isAdminInOrganization($organization) ) return true;
        return $this->orgHasUserViaProgram($organization, $this, true);
    }
    public function status()
    {
        return $this->belongsTo(Status::class, 'user_status_id');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token, $this->first_name));
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_user')
        ->withTimestamps();
    }

    public function unit_numbers()
    {
        return $this->belongsToMany(UnitNumber::class, 'unit_number_has_users')->withTimestamps();
    }

    public function position_levels()
    {
        return $this->belongsToMany(PositionLevel::class, 'position_assignments')->withTimestamps();
    }

    public function getUnitNumberAttribute()
    {
        return $this->unit_numbers()->where('user_id', $this->id)->first();
    }
    
    public function getPositionLevelAttribute()
    {
        return $this->position_levels()->where('user_id', $this->id)->first();
    }

    public function award_levels()
    {
        return $this->hasMany(AwardLevel::class);
    }

    public function readAvailableBalance( $program, $user = null )  {
        $program_id = self::extractId($program);
        if( $user ) {
            $user_id = self::extractId($user);
        } else if ($this->id) {
            $user_id = $this->id;
            $user = $this;
        } else $user_id = null;

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
			$account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
		} else {
			// use monies
            $account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
		}
        return (new AccountService)->readBalance($user->account_holder_id, $account_type, $journal_event_types);
    }

    // TODO: This function not only for "User", we should use AccountService->readBalance()
	private function _read_balance($account_holder_id, $account_type, $journal_event_types = []) {
		$credits = JournalEvent::read_sum_postings_by_account_and_journal_events ( ( int ) $account_holder_id, $account_type, $journal_event_types, 1 );
		$debits = JournalEvent::read_sum_postings_by_account_and_journal_events ( ( int ) $account_holder_id, $account_type, $journal_event_types, 0 );
		$bal = ( float ) (number_format ( ($credits->total - $debits->total), 2, '.', '' ));
		return $bal;
	}

    public static function createAccount( $data )    {
        $account_holder_id = AccountHolder::insertGetId(['context'=>'User', 'created_at' => now()]);
        if( empty($data['user_status_id']) )   {
            $user_status = self::getStatusByName( 'Pending Activation' );
            if( $user_status )
            {
                $data['user_status_id'] = $user_status->id;
            }
        }
        if( empty($data['password']))
        {
            $data['password'] = rand(); //to be regenerated by user
        }
        return parent::create($data + ['account_holder_id' => $account_holder_id]);
    }

    public static function getStatusByName( $status ) {
        return Status::getByNameAndContext($status, 'Users');
    }

    public static function getStatusIdByName( $status ) {
        return self::getStatusByName($status)->id;
    }

    public static function getIdStatusActive()
    {
        return self::getStatusActive()->id;
    }

    public static function getStatusActive()
    {
        return self::getStatusByName(self::STATUS_ACTIVE);
    }

    public static function getStatusNew()
    {
        return self::getStatusByName(self::STATUS_NEW);
    }

    public static function getIdStatusNew()
    {
        return self::getStatusNew()->id;
    }

    public static function getStatusPendingDeactivation()
    {
        return self::getStatusByName(self::STATUS_PENDING_DEACTIVATION);
    }

    public static function getIdStatusPendingDeactivation()
    {
        return self::getStatusPendingDeactivation()->id;
    }

    public static function getStatusPendingActivation()
    {
        return self::getStatusByName(self::STATUS_PENDING_ACTIVATION);
    }

    public static function getIdStatusPendingActivation()
    {
        return self::getStatusPendingActivation()->id;
    }

    /**
     * @inheritDoc
     */
    public function getImageFields(): array
    {
        return self::IMAGE_FIELDS;
    }

    /**
     * @inheritDoc
     */
    public function getImagePath(): string
    {
        return self::IMAGE_PATH;
    }

    /**
     * Verify that the user can be awarded
     *
     * @param Program $program
     * @return bool
     */
    public function canBeAwarded(Program $program): bool
    {
        $result = true;
        $availableStates = [
            config('global.user_status_deactivated'),
            config('global.user_status_deleted'),
        ];
        if($program->allow_awarding_pending_activation_participants){
            $availableStates[] = config('global.user_status_pending_activation');
        }

        if (in_array($this->status()->first()->status, $availableStates)){
            $result = false;
        }

        return $result;
    }


    public static function getAllByProgramsQuery(array $programs)
    {
        $userStatus = User::getStatusByName(User::STATUS_DELETED);
        return User::whereHas('roles', function (Builder $query) use ($programs) {
            $query->whereIn('model_has_roles.program_id', $programs);
        })
            ->where('user_status_id', '!=', $userStatus->id);
    }

    public static function getAllByPrograms(array $programs)
    {
        return self::getAllByProgramsQuery($programs)->get();
    }

    public static function getCountByPrograms(array $programs)
    {
        return self::getAllByProgramsQuery($programs)->count();
    }

    public static function getParticipantsByPrograms(array $programs)
    {
        $userStatus = User::getStatusByName(User::STATUS_DELETED);
        $query = User::whereHas('roles', function (Builder $query) use ($programs) {
            $query->where('name', 'LIKE', config('roles.participant'))
                ->where('name', 'NOT LIKE', config('roles.manager'))
                ->whereIn('model_has_roles.program_id', $programs);
        })
            ->where('user_status_id', '!=', $userStatus->id);
        return $query->get();
    }

    public static function getByExternalId(int $externalId)
    {
        return self::where('external_id', $externalId)->first();
    }

    public static function getByEmail(string $email)
    {
        return self::where('email', $email)->first();
    }

    public function changeStatus($userIds, $userStatusId){
        return self::whereIn('id', $userIds)->update(['user_status_id' => $userStatusId]);
    }

    public static function getByIdAndProgram(int $userId, int $programId)
    {
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        return self::whereHas('roles', function (Builder $query) use ($userClassForSql, $userId, $programId) {
            $query->where('model_has_roles.model_type', 'like',  DB::raw("'" . $userClassForSql . "'"));
            $query->where('model_has_roles.model_id', '=', $userId);
            $query->where('model_has_roles.program_id', '=', $programId);
        })->first();
    }

    public function getActiveOrNewUserByEmail( $email ) {
        return self::where('email', 'like', $email)
        ->whereHas('status', function($query) {
            $query->whereIn('status', ['Active', 'New', 'Pending Activation']);
            $query->where('context', 'LIKE', 'Users');
        })
        ->first();
    }

    public function forcePasswordChange()  {
        return $this->isPendingActivation() OR $this->isNew() OR ( $this->isActive() && $this->neverLoggedIn() );
    }

    public function isActive()    {
        return $this->status->status === self::STATUS_ACTIVE;
    }

    public function isPendingActivation()    {
        return $this->status->status === self::STATUS_PENDING_ACTIVATION;
    }

    public function isNew()    {
        return $this->status->status === self::STATUS_NEW;
    }

    public function neverLoggedIn()    {
        return is_null($this->last_login);
    }

    public function v2_users()
    {
        return $this->hasMany(UserV2User::class);
    }

    public function push_tokens()
    {
        return $this->hasMany(\App\Models\PushNotificationToken::class);
    }
}
