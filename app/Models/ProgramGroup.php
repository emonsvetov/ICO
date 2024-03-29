<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function programs()
    {
        return $this->belongsToMany(Program::class);
    }

}
