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
        return $this->hasMany(Program::class);
    }

    public function childrenPrograms()
    {
        return $this->hasMany(Program::class)->with('childrenPrograms');
    }
}
