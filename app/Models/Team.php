<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}