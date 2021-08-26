<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
