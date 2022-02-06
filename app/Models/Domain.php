<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Program;
use Organization;
use App\Models\DomainIP;
class Domain extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function programs()
    {
        return $this->hasMany(Program::class);
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
