<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramMerchant extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'program_merchant';
    public $timestamps = true;
}
