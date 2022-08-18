<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class ProgramTemplate extends BaseModel
{
    use HasFactory;

    const IMAGE_FIELDS = ['small_logo', 'big_logo', 'hero_banner'];

    protected $guarded = [];

}
