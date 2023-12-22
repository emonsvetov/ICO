<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public static function getFirstAccountHolderId()
    {
        return Owner::findOrFail(1)->account_holder_id;
    }
}
