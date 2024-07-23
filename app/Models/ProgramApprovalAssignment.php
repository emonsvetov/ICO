<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramApprovalAssignment extends Model
{
    use HasFactory;

    public $table = 'program_approval_assignment';
    protected $guarded = [];
    public $timestamp = true;

    public function position_level()
    {
        return $this->belongsTo(PositionLevel::class, 'position_level_id');
    }
}
