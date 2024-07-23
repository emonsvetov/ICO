<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRelation extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamp = true;

    public function program_approval()
    {
        return $this->belongsTo(ProgramApproval::class);
    }

    public function awarder_position_level()
    {
        return $this->belongsTo(PositionLevel::class, 'awarder_position_id');
    }

    public function approver_position_level()
    {
        return $this->belongsTo(PositionLevel::class, 'approver_position_id');
    }
}
