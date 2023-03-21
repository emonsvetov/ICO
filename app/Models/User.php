<?php

namespace App\Models;

use App\Models\interfaces\ImageInterface;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\WithOrganizationScope;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Traits\HasProgramRoles;
use App\Models\Traits\GetModelByMixed;
use App\Models\Traits\IdExtractor;
use Laravel\Passport\HasApiTokens;
use App\Models\AccountHolder;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;

use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail, ImageInterface
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, IdExtractor, HasProgramRoles, WithOrganizationScope, GetModelByMixed;
    use SoftDeletes;

    const IMAGE_FIELDS = ['avatar'];
    const IMAGE_PATH = 'users';

    const STATUS_ACTIVE = 'Active';
    const STATUS_PENDING_ACTIVATION = 'Pending Activation';
    const STATUS_DELETED = 'Deleted';
    const STATUS_PENDING_DEACTIVATION = 'Pending Deactivation';
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

    protected $appends = ['name', 'isSuperAdmin', 'isAdmin'];
    protected function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    protected function getIsSuperAdminAttribute()
    {
        return $this->hasRole(config('roles.super_admin'));
    }
    protected function getIsAdminAttribute()
    {
        return $this->hasRole(config('roles.admin'));
    }
    protected function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }
    public function isAdmin()
    {
        return $this->hasRole(config('roles.admin'));
    }
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
        return self::_read_balance( $user->account_holder_id, $account_type, $journal_event_types );
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
}
