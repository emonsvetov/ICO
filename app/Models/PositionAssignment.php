<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionAssignment extends Model
{
    use HasFactory;

    public $table = 'position_assignments';
    protected $guarded = [];
    public $timestamp = true;

    // public static function assignPositions($data)
    // {
    //     if (!empty($data)) {
    //         $newPositionAssignment = self::create($data);
    //        // return $newPositionAssignment;
    //     } else {
    //         return false;
    //     }
    // }
}
