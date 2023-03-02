<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    use HasFactory;
    protected $guarded = [];

    public static function getFirstAccountHolderId()
    {
        return Owner::findOrFail(1)->account_holder_id;
    }
}
