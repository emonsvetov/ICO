<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventIcon extends Model
{
    protected $table = 'event_icons';

    protected $guarded = [];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted',
    ];
}
