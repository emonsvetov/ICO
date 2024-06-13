<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAwardLevel extends Model
{
    use HasFactory;
    protected $table = 'event_award_level';

    protected $fillable = [
        'event_id',
        'award_level_id',
        'amount',
    ];

    public function event_award_level()
    {
        return $this->belongsTo(Program::class);
    }

    public function event_award_levels()
    {
        return $this->belongsTo(Event::class);
    }
}
