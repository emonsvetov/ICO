<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

use App\Models\Traits\OrgHasUserViaProgram;


class Organization extends BaseModel
{
    use HasFactory, Notifiable;
    use OrgHasUserViaProgram;

    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function users()
    {
        // return $this->hasMany(User::class);
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function hasUser( User $user )
    {
        if( $this->users->contains($user->id) ) {
            return true;
        }
        return $this->orgHasUserViaProgram($this, $user, true);
    }

    public function roles()
    {
        return $this->hasMany(User::class);
    }

    public function scopeAuthorized(\Illuminate\Database\Eloquent\Builder $builder): void
    {
        $user = auth()->user();
        if( $user->isSuperAdmin )
        {
            return;
        }
        $orgFilter = $user->getOrganizationFilter();
        if( $orgFilter ) {
            $builder->whereIn('id', $orgFilter);
        }
    }
}
