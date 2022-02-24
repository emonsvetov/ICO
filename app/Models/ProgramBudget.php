<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramBudget extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'program_budget';
    public function month()
    {
        return $this->hasOne(Month::class);
    }
}
