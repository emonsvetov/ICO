<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\WithOrganizationScope;

class Domain extends Model
{
    use HasFactory;
    use SoftDeletes;
    use WithOrganizationScope;
    
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
