<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotificationToken extends Model
{
    protected $table = 'users_push_notification_tokens';
    protected $guarded = [];
}
