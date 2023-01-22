<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Team extends BaseModel
{
    protected $guarded = [];
    use HasFactory;
    use SoftDeletes;
    protected $table = 'team';
    
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /*public function program()
    {
        return $this->belongsTo(Program::class);
    }*/
}