<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitNumber extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamp = true;

    public function program()   {
        $this->belongsTo(Program::class);
    }
}
