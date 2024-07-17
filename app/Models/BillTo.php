<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillTo extends Model
{
    use HasFactory;
    protected $guarded = [];

    public static function saveLastUsed( $organization_id, $program_id, $details )
    {
        $billTo['first_name'] = array_key_exists('first_name', $details) ? $details['first_name'] : null;
        $billTo['last_name'] = array_key_exists('last_name', $details) ? $details['last_name'] : null;
        $billTo['company']  = array_key_exists('company', $details) ? $details['company'] : null;
        $billTo['address']  = array_key_exists('address', $details) ? $details['address'] : null;
        $billTo['city']     = array_key_exists('city', $details) ? $details['city'] : null;
        $billTo['state']    = array_key_exists('state', $details) ? $details['state'] : null;
        $billTo['zip']      = array_key_exists('zip', $details) ? $details['zip'] : null;
        $billTo['country']  = array_key_exists('country', $details) ? $details['country'] : null;

        $previousBillTo = self::where('organization_id', $organization_id)
                              ->where('program_id', $program_id)
                              ->where('first_name', $billTo['first_name'])
                              ->where('last_name', $billTo['last_name']);

        $previousBillTo = is_null($billTo['company']) ? $previousBillTo->whereNull('company') : $previousBillTo->where('company', $billTo['company']);
        $previousBillTo = is_null($billTo['address']) ? $previousBillTo->whereNull('address') : $previousBillTo->where('address', $billTo['address']);
        $previousBillTo = is_null($billTo['city']) ? $previousBillTo->whereNull('city') : $previousBillTo->where('city', $billTo['city']);
        $previousBillTo = is_null($billTo['state']) ? $previousBillTo->whereNull('state') : $previousBillTo->where('state', $billTo['state']);
        $previousBillTo = is_null($billTo['zip']) ? $previousBillTo->whereNull('zip') : $previousBillTo->where('zip', $billTo['zip']);
        $previousBillTo = is_null($billTo['country']) ? $previousBillTo->whereNull('country') : $previousBillTo->where('country', $billTo['country']);

        $previousBillTo = $previousBillTo->first();

        if ( !is_null($previousBillTo) )
        {
            $previousBillTo->save();

        } else {

            //Remove NULLs from array
            foreach ( $billTo as $key => $value )
            {
                if ( is_null($value) )
                {
                    unset( $billTo[$key] );
                }
            }

            self::create($billTo + ['organization_id' => $organization_id, 'program_id' => $program_id]);
        }
    }
}
