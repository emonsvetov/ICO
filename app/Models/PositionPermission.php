<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionPermission extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function positionPermissionAssignments()
    {
        return $this->hasMany(PositionPermissionAssignment::class, 'position_permission_id');
    }

    /**
     * Get a list of all permissions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function PositionPermissionList()
    {
        return self::all();
    }
}
