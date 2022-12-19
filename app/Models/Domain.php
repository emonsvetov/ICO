<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\WithOrganizationScope;
use App\Models\BaseModel;

class Domain extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use WithOrganizationScope;
    
    protected $guarded = [];
    protected $hidden = [
        'secret_key',
        'created_at', 
        'updated_at', 
        'deleted_at', 
        'deleted'
    ];

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
