<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'transaction_id', 'payment_status', 'amount', 'currency', 'payment_method',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
