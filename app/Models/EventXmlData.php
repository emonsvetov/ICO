<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventXmlData extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = true;

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_template_id');
    }
}
