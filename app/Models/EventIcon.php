<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventIcon extends Model
{
    protected $table = 'event_icons';

    const DEFAULT_ICONS = [
        [
            'id' => 1,
            'name' => 'Hands',
            'path' => '1.jpg',
        ],
        [
            'id' => 2,
            'name' => 'Hands',
            'path' => '2.jpg',
        ],
        [
            'id' => 3,
            'name' => 'Hands',
            'path' => '3.jpg',
        ],
        [
            'id' => 4,
            'name' => 'Hands',
            'path' => '4.jpg',
        ],
        [
            'id' => 5,
            'name' => 'Hands',
            'path' => '5.jpg',
        ],
        [
            'id' => 6,
            'name' => 'Hands',
            'path' => '6.jpg',
        ],
    ];

    protected $guarded = [];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted',
    ];
}
