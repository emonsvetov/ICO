<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionPermission extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function position_levels()
    {
        return $this->belongsToMany(PositionLevel::class, 'position_permission_assignments')
        ->withTimestamps();
    }
}
