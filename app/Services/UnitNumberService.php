<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

use App\Models\UnitNumber;
use App\Models\Program;

class UnitNumberService
{
    public function index(Program $program) {
        $ignore_uses_units = request()->get('ignore_uses_units', false);
        if( !$ignore_uses_units && !$program->uses_units )   {
            return [];
        }
        $query =  $program->unit_numbers()->withCount('users');
        $assignable = request()->get('assignable', false);
        if( $assignable && !$program->allow_multiple_participants_per_unit )    {
            $query =  $query->having('users_count', '=', 0);
        }
        return $query->get();
    }
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
    public static function assign( UnitNumber $unitNumber, array $data )
    {
        $userIds = $data['user_id'] ?: null;
        if( $userIds )
        {
            return $unitNumber->users()->sync( $userIds );
        }
    }
    public static function unassign( UnitNumber $unitNumber, array $data )
    {
        $userIds = $data['user_id'] ?: null;
        if( $userIds )
        {
            return $unitNumber->users()->detach( $userIds );
        }
    }
}
