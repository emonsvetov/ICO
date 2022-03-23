<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptimalValue extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'merchant_optimal_values';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
