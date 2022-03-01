<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Permission\Models\Permission;

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

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_user')
        // ->withPivot('featured', 'cost_to_program')
        ->withTimestamps();
    }

    public function syncPermissionsByProgram($programId, array $permissions)
    {
        print_r($permissions);
        $permissionIds = Permission::where('name', 'LIKE', "program.{$programId}.role.%")->get()->pluck('id'); //filter by program to narrow down 
        // $this is the User model for example
        $current = $this->permissions->filter(function($permission) use ($permissionIds) {
            return in_array($permission->pivot->permission_id, $permissionIds->toArray());
        })->pluck('id');

        print_r($current);
    
        $detach = $current->diff($permissions)->all();
        // print_r($detach);
        $attach_ids = collect($permissions)->diff($current)->all();
        // $attach_ids = array_diff($permissions, $current->toArray());
        // $attach_ids = $permissions;
        print_r($attach_ids);
        // return;
        // $atach_pivot = array_fill(0, count($attach_ids), ['permission_id' => 52]);

        $atach_pivot = [];

        foreach( $attach_ids as $permission_id )  {
            $atach_pivot[] = ['permission_id' => $permission_id];
        }
        // return $atach_pivot;
        $attach = array_combine($attach_ids, $atach_pivot);
        // print_r($attach);
        // return;
    
        $this->permissions()->detach($detach);
        $this->permissions()->attach($attach);
    
        return $this;
    }
}
