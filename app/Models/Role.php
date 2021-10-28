<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function allowTo($permission)
    {
        return $this->permissions()->save($permission);
    }

    public function revoke($permission)
    {
        return $this->permissions()->detach($permission);
    }

    public function permissionNames()
    {
        return $this->permissions->flatten()->pluck('name')->unique();
    }
}
