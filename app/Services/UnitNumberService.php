<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

use App\Models\UnitNumber;

class UnitNumberService
{
    public static function create($data)
    {
        return UnitNumber::create($data);
    }
    public static function update( UnitNumber $unitNumber, $data )
    {
        return $unitNumber->update( $data );
    }
    public static function delete( UnitNumber $unitNumber )
    {
        return $unitNumber->delete();
    }
    public static function assign( UnitNumber $unitNumber )
    {
        return $unitNumber->delete();
    }
}
