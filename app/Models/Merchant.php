<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory;
    use SoftDeletes;
  
    protected $guarded = [];

    public function children()
    {
        return $this->hasMany(Merchant::class, 'parent_id')->with('children');
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_merchant');
    }
}
