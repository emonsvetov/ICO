<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function participant_groups()
    {
        return $this->belongsToMany(ParticipantGroup::class);
    }
}
