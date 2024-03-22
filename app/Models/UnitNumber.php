<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class UnitNumber extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
    public $timestamp = true;

    public function program()   {
        $this->belongsTo(Program::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'unit_number_has_users')
        ->withTimestamps();
    }
}
