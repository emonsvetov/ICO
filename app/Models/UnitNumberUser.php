<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitNumberUser extends Model
{
    use HasFactory;

    public $table = 'unit_number_has_users';
    protected $guarded = [];
    public $timestamp = true;
}
