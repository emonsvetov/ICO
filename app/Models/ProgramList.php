<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramList extends Model
{
    protected $table = 'program_list';

    protected $fillable = [
        'name', 'url',
    ];

}
