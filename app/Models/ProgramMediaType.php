<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramMediaType extends Model
{
    use HasFactory;
    protected $table = 'program_media_type';
    protected $guarded = [];
    protected $hidden = [
        'deleted'
    ];
}
