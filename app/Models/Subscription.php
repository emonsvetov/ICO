<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'subscription_id', 'plan_id', 'start_date', 'end_date',
        'billing_interval', 'trial_period', 'cancellation_date', 'is_active'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
