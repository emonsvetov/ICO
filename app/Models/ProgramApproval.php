<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramApproval extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamp = true;

    public function position_levels()
    {
        return $this->belongsToMany(PositionLevel::class, 'program_approval_assignment')
            ->withTimestamps();
    }
}
