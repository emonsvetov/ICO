<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Organization extends Model
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
        return $this->hasMany(User::class);
    }

    public function roles()
    {
        return $this->hasMany(User::class);
    }
}
