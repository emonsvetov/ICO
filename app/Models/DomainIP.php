<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Domain;
class DomainIP extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'domain_ips';

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
