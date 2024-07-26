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

    public function position_approval_relations()
    {
        return $this->belongsToMany(PositionLevel::class, 'approval_relations')
            ->withPivot('approver_position_id', 'awarder_position_id', 'created_by');
    }

    public function approval_relations()
    {
        return $this->hasMany(ApprovalRelation::class, 'program_approval_id');
    }

    public function program_approval_assignment()
    {
        return $this->hasMany(ProgramApprovalAssignment::class, 'program_approval_id');
    }

    public function get_program_approval_id($program_id)
    {
        return ProgramApproval::where('program_id', $program_id)->where('status', 1)
            ->orderBy('id', 'asc')
            ->limit(1)
            ->value('program_id');
    }
}
