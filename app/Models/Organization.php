<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Organization extends BaseModel
{
    use HasFactory, Notifiable;

    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function users()
    {
        // return $this->hasMany(User::class);
        return $this->belongsToMany(User::class);
    }

    public function hasUser( User $user )
    {
        return true;
        return $this->users->contains($user->id);
    }

    public function roles()
    {
        return $this->hasMany(User::class);
    }
}
