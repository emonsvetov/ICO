<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeelingSurvey extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'feeling_survey';
}
