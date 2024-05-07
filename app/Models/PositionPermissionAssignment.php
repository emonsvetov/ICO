<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionPermissionAssignment extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function positionLevel()
    {
        return $this->belongsTo(PositionLevel::class, 'position_level_id');
    }
}
