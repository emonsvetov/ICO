<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitNumber extends Model
{
    use HasFactory;

    protected $table = null;

    public function program()   {
        $this->belongsTo(Program::class);
    }
}
