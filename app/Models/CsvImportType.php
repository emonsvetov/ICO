<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CsvImportType extends Model
{
    use HasFactory;
    protected $guarded = [];

    
    public static function getIdByType($type)
    {
        $first = self::where('type', $type)->first();
        if( $first )    {
            return $first->id;
        }
    }
}
