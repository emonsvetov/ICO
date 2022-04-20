<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class JournalEventType extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function getIdByType( $type ) {
        return self::where('type', $type)->first()->id;
    }
}