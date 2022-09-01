<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalPlanType extends Model
{
    use HasFactory;
    /*public static function getNameById( $id ) {
        $first = self::where('id', $id)->first();
        if( $first) return $first->name;
        return;
    }*/
}
