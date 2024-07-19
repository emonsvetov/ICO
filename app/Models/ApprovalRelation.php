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
}
