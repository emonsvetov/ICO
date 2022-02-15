<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function programs()
    {
        return $this->belongsToMany(Program::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function domain_ips()
    {
        return $this->hasMany(DomainIP::class);
    }
}
