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

    public function getPartOfSecretKey()
    {
        $this->makeVisible(['secret_key']);
        $len = strlen($this->secret_key);
        $this->secret_key = substr($this->secret_key, 0, 2)
            . str_repeat('*', 6)
            . substr($this->secret_key,$len - 2, 2);
    }
}
