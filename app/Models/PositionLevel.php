<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PositionLevel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
    public $timestamp = true;

    public function program()
    {
        $this->belongsTo(Program::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'position_assignments')
            ->withTimestamps();
    }

    public function position_permissions()
    {
        return $this->belongsToMany(PositionPermission::class, 'position_permission_assignments')
            ->withTimestamps();
    }
}
