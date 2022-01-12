<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    
    public function programs()
    {
        return $this->hasMany(Program::class, 'program_id');
    }

    public function children()
    {
        return $this->hasMany(Program::class, 'program_id')->with('children');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
