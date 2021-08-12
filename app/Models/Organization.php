<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Organization extends Model
{
    use HasFactory, Notifiable;

    protected $guarded = [];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
