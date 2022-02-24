<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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
        'role'
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
        // '*',
        // 'programs'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    public function participant_groups()
    {
        return $this->belongsToMany(ParticipantGroup::class);
    }

    public function sendPasswordResetNotification($token)
    {
        
        $url = env('APP_URL', 'http://localhost') . '/reset-password?token=' . $token;

        $this->notify(new ResetPasswordNotification($url));
    }
<<<<<<< HEAD

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_user')
        // ->withPivot('featured', 'cost_to_program')
        ->withTimestamps();
    }
=======
>>>>>>> Development
}
